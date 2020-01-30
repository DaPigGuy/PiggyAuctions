<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\auction;

use DaPigGuy\PiggyAuctions\events\AuctionExpireEvent;
use DaPigGuy\PiggyAuctions\events\AuctionLoadEvent;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\item\Item;
use pocketmine\Player;

/**
 * Class AuctionManager
 * @package DaPigGuy\PiggyAuctions\auction
 */
class AuctionManager
{
    /** @var PiggyAuctions */
    private $plugin;

    /** @var Auction[] */
    private $auctions = [];
    /** @var bool */
    private $auctionsLoaded = false;

    /**
     * AuctionManager constructor.
     * @param PiggyAuctions $plugin
     */
    public function __construct(PiggyAuctions $plugin)
    {
        $this->plugin = $plugin;
    }

    public function init(): void
    {
        $this->plugin->getDatabase()->executeGeneric("piggyauctions.init");
        $this->plugin->getDatabase()->executeSelect("piggyauctions.load", [], function (array $rows): void {
            $this->auctionsLoaded = true;
            foreach ($rows as $row) {
                $this->auctions[$row["id"]] = new Auction(
                    $row["id"],
                    $row["auctioneer"],
                    Item::jsonDeserialize(json_decode($row["item"], true)),
                    $row["startdate"],
                    $row["enddate"],
                    (bool)$row["claimed"],
                    array_map(function (array $bidData) use ($row) {
                        return new AuctionBid($row["id"], $bidData["bidder"], $bidData["bidamount"], $bidData["timestamp"]);
                    }, json_decode($row["claimed_bids"], true)),
                    $row["starting_bid"],
                    array_map(function (array $bidData) use ($row) {
                        return new AuctionBid($row["id"], $bidData["bidder"], $bidData["bidamount"], $bidData["timestamp"]);
                    }, json_decode($row["bids"], true))
                );
            }
        });
    }

    /**
     * @return Auction[]
     */
    public function getAuctions(): array
    {
        return $this->auctions;
    }

    /**
     * @return bool
     */
    public function areAuctionsLoaded(): bool
    {
        return $this->auctionsLoaded;
    }

    /**
     * @param int $id
     * @return Auction|null
     */
    public function getAuction(int $id): ?Auction
    {
        return $this->auctions[$id] ?? null;
    }

    /**
     * @param Player|string $player
     * @return Auction[]
     */
    public function getAuctionsHeldBy($player): array
    {
        if ($player instanceof Player) $player = $player->getName();
        return array_filter($this->auctions, function (Auction $auction) use ($player): bool {
            return strtolower($auction->getAuctioneer()) === strtolower($player);
        });
    }

    /**
     * @param Player|string $player
     * @return Auction[]
     */
    public function getActiveAuctionsHeldBy($player): array
    {
        if ($player instanceof Player) $player = $player->getName();
        return array_filter($this->auctions, function (Auction $auction) use ($player): bool {
            return strtolower($auction->getAuctioneer()) === strtolower($player) && !$auction->hasExpired();
        });
    }


    /**
     * @return array
     */
    public function getActiveAuctions(): array
    {
        return array_filter($this->auctions, function (Auction $auction): bool {
            return !$auction->hasExpired();
        });
    }

    /**
     * @return AuctionBid[]
     */
    public function getBids(): array
    {
        $bids = [];
        foreach ($this->auctions as $auction) $bids = array_merge($bids, $auction->getBids());
        return $bids;
    }

    /**
     * @param string|Player $player
     * @return AuctionBid[]
     */
    public function getBidsBy($player): array
    {
        if ($player instanceof Player) $player = $player->getName();
        return array_filter($this->getBids(), function (AuctionBid $bid) use ($player): bool {
            return strtolower($bid->getBidder()) === strtolower($player);
        });
    }

    /**
     * @param string $auctioneer
     * @param Item $item
     * @param int $startDate
     * @param int $endDate
     * @param int $startingBid
     */
    public function addAuction(string $auctioneer, Item $item, int $startDate, int $endDate, int $startingBid): void
    {
        $this->plugin->getDatabase()->executeInsert("piggyauctions.add", [
            "auctioneer" => $auctioneer,
            "item" => json_encode($item->jsonSerialize()),
            "startdate" => $startDate,
            "enddate" => $endDate,
            "claimed" => 0,
            "claimed_bids" => json_encode([]),
            "starting_bid" => $startingBid,
            "bids" => json_encode([])
        ], function (int $id) use ($auctioneer, $item, $startDate, $endDate, $startingBid) {
            $this->auctions[$id] = new Auction($id, $auctioneer, $item, $startDate, $endDate, false, [], $startingBid, []);
            (new AuctionLoadEvent($this->auctions[$id]))->call();
        });
    }

    /**
     * @param Auction $auction
     */
    public function updateAuction(Auction $auction): void
    {
        $this->plugin->getDatabase()->executeChange("piggyauctions.update", [
            "id" => $auction->getId(),
            "claimed" => (int)$auction->isClaimed(),
            "claimed_bids" => json_encode(array_map(function (AuctionBid $bid) {
                return ["bidder" => $bid->getBidder(), "bidamount" => $bid->getBidAmount(), "timestamp" => $bid->getTimestamp()];
            }, $auction->getclaimedBids())),
            "bids" => json_encode(array_map(function (AuctionBid $bid) {
                return ["bidder" => $bid->getBidder(), "bidamount" => $bid->getBidAmount(), "timestamp" => $bid->getTimestamp()];
            }, $auction->getBids()))
        ]);
    }

    /**
     * @param Auction $auction
     */
    public function removeAuction(Auction $auction): void
    {
        unset($this->auctions[$auction->getId()]);
        (new AuctionExpireEvent($auction))->call();
        $this->plugin->getDatabase()->executeChange("piggyauctions.remove", ["id" => $auction->getId()]);
    }
}