<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\auction;

use DaPigGuy\PiggyAuctions\events\AuctionClaimItemEvent;
use DaPigGuy\PiggyAuctions\events\AuctionClaimMoneyEvent;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\item\Item;
use pocketmine\player\Player;

class Auction
{
    /**
     * @param AuctionBid[] $claimedBids
     * @param AuctionBid[] $bids
     */
    public function __construct(public int $id, public string $auctioneer, public Item $item, public int $startDate, public int $endDate, public bool $claimed, public array $claimedBids, public int $startingBid, public array $bids)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }


    public function getAuctioneer(): string
    {
        return $this->auctioneer;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getStartDate(): int
    {
        return $this->startDate;
    }

    public function getEndDate(): int
    {
        return $this->endDate;
    }

    public function setEndDate(int $endDate): void
    {
        $this->endDate = $endDate;
        PiggyAuctions::getInstance()->getAuctionManager()->updateAuction($this);
    }

    public function hasExpired(): bool
    {
        $expired = time() > $this->endDate;
        if ($expired && count($this->getClaimedBids()) === count($this->getBids()) && $this->claimed) {
            PiggyAuctions::getInstance()->getAuctionManager()->removeAuction($this);
            return true;
        }
        return $expired;
    }

    public function isClaimed(): bool
    {
        return $this->claimed;
    }

    public function getStartingBid(): int
    {
        return $this->startingBid;
    }

    /**
     * @return AuctionBid[]
     */
    public function getClaimedBids(): array
    {
        return $this->claimedBids;
    }

    /**
     * @return AuctionBid[]
     */
    public function getUnclaimedBids(): array
    {
        return array_filter($this->bids, function (AuctionBid $bid): bool {
            return !in_array($bid, $this->claimedBids);
        });
    }

    /**
     * @return AuctionBid[]
     */
    public function getUnclaimedBidsHeldBy(string $player): array
    {
        return array_filter($this->getUnclaimedBids(), static function (AuctionBid $bid) use ($player): bool {
            return $bid->getBidder() === $player;
        });
    }

    public function bidderClaim(Player $player): void
    {
        $bids = $this->getUnclaimedBidsHeldBy($player->getName());
        if (count($bids) < 1) return;
        /** @var AuctionBid $topBid */
        $topBid = $this->getTopBidBy($player->getName());
        foreach ($bids as $bid) $this->claimedBids[] = $bid;
        $this->hasExpired();
        if (PiggyAuctions::getInstance()->getAuctionManager()->getAuction($this->getId()) === $this) PiggyAuctions::getInstance()->getAuctionManager()->updateAuction($this);
        if (in_array($this->getTopBid(), $bids)) {
            $ev = new AuctionClaimItemEvent($this, $player, clone $this->getItem());
            $ev->call();
            if (!$ev->isCancelled()) {
                PiggyAuctions::getInstance()->getStatsManager()->getStatistics($player)->incrementStatistic("auctions_won");
                $player->getInventory()->addItem($ev->getItem());
                $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-item-success", ["{PLAYER}" => $this->getAuctioneer(), "{ITEM}" => $this->getItem()->getName(), "{MONEY}" => $topBid->getBidAmount()]));
            }
            return;
        }
        $ev = new AuctionClaimMoneyEvent($this, $player, $topBid->getBidAmount());
        $ev->call();
        if (!$ev->isCancelled()) {
            PiggyAuctions::getInstance()->getEconomyProvider()->giveMoney($player, $ev->getAmount());
            $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-money-success", ["{PLAYER}" => $this->getAuctioneer(), "{ITEM}" => $this->getItem()->getName(), "{MONEY}" => $topBid->getBidAmount()]));
        }
    }

    public function claim(Player $player): void
    {
        if ($this->claimed) return;
        $stats = PiggyAuctions::getInstance()->getStatsManager()->getStatistics($player);
        if ($this->getTopBid() === null) {
            $ev = new AuctionClaimItemEvent($this, $player, clone $this->getItem());
            $ev->call();
            if (!$ev->isCancelled()) {
                $stats->incrementStatistic("auctions_no_bids");
                $player->getInventory()->addItem($ev->getItem());
                $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-item-success", ["{ITEM}" => $this->getItem()->getName()]));
            }
        } else {
            $ev = new AuctionClaimMoneyEvent($this, $player, $this->getTopBid()->getBidAmount());
            $ev->call();
            if (!$ev->isCancelled()) {
                $stats->incrementStatistic("auctions_with_bids");
                $stats->incrementStatistic("money_earned", $ev->getAmount());
                if ($stats->getStatistic("highest_held") < $ev->getAmount()) $stats->setStatistic("highest_held", $ev->getAmount());
                PiggyAuctions::getInstance()->getEconomyProvider()->giveMoney($player, $ev->getAmount());
                $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-money-success", ["{ITEM}" => $this->getItem()->getName(), "{TOPBIDDER}" => $this->getTopBid()->getBidder(), "{MONEY}" => $this->getTopBid()->getBidAmount()]));
            }
        }
        $this->claimed = true;
        $this->hasExpired();
        if (PiggyAuctions::getInstance()->getAuctionManager()->getAuction($this->getId()) === $this) PiggyAuctions::getInstance()->getAuctionManager()->updateAuction($this);
    }

    /**
     * @return AuctionBid[]
     */
    public function getBids(): array
    {
        return $this->bids;
    }

    public function getMinimumBidAmount(): int
    {
        return (int)(($topBid = $this->getTopBid()) === null ? $this->startingBid : $topBid->getBidAmount() * (1 + PiggyAuctions::getInstance()->getConfig()->getNested("auctions.bid-increment", 15) / 100));
    }

    public function getTopBid(): ?AuctionBid
    {
        $highestBid = null;
        $highestBidAmount = 0;
        foreach ($this->bids as $bid) {
            if ($bid->getBidAmount() > $highestBidAmount) {
                $highestBid = $bid;
                $highestBidAmount = $bid->getBidAmount();
            }
        }
        return $highestBid;
    }

    public function getTopBidBy(string $player): ?AuctionBid
    {
        $highestBid = null;
        $highestBidAmount = 0;
        foreach ($this->bids as $bid) {
            if ($player === $bid->getBidder() && $bid->getBidAmount() > $highestBidAmount) {
                $highestBid = $bid;
                $highestBidAmount = $bid->getBidAmount();
            }
        }
        return $highestBid;
    }

    public function addBid(AuctionBid $bid): void
    {
        $notified = [];
        foreach ($this->bids as $b) {
            if (!in_array($b->getBidder(), $notified) && $b->getBidder() !== $bid->getBidder() && $b->getBidAmount() < $bid->getBidAmount()) {
                $notified[] = $b->getBidder();
                if (($player = PiggyAuctions::getInstance()->getServer()->getPlayerExact($b->getBidder())) !== null) {
                    $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.outbid", ["{PLAYER}" => $bid->getBidder(), "{DIFFERENCE}" => $bid->getBidAmount() - $b->getBidAmount(), "{ITEM}" => $this->getItem()->getName()]));
                }
            }
        }

        $this->bids[] = $bid;
        PiggyAuctions::getInstance()->getAuctionManager()->updateAuction($this);
    }
}