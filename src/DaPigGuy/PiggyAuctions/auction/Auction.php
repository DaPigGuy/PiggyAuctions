<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\auction;

use pocketmine\item\Item;

/**
 * Class Auction
 * @package DaPigGuy\PiggyAuctions\auction
 */
class Auction
{
    /** @var int */
    public $id;

    /** @var string */
    public $auctioneer;
    /** @var Item */
    public $item;
    /** @var int */
    public $startDate;
    /** @var int */
    public $endDate;
    /** @var bool */
    public $claimed;
    /** @var array|AuctionBid[] */
    public $unclaimedBids;
    /** @var array|AuctionBid[] */
    public $bids;

    /**
     * Auction constructor.
     * @param int $id
     * @param string $auctioneer
     * @param Item $item
     * @param int $startDate
     * @param int $endDate
     * @param bool $claimed
     * @param array $unclaimedBids
     * @param AuctionBid[] $bids
     */
    public function __construct(int $id, string $auctioneer, Item $item, int $startDate, int $endDate, bool $claimed, array $unclaimedBids, array $bids)
    {
        $this->id = $id;
        $this->auctioneer = $auctioneer;
        $this->item = $item;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->claimed = $claimed;
        $this->unclaimedBids = $unclaimedBids;
        $this->bids = $bids;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAuctioneer(): string
    {
        return $this->auctioneer;
    }

    /**
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * @return int
     */
    public function getStartDate(): int
    {
        return $this->startDate;
    }

    /**
     * @return int
     */
    public function getEndDate(): int
    {
        return $this->endDate;
    }

    /**
     * @return bool
     */
    public function isClaimed(): bool
    {
        return $this->claimed;
    }

    /**
     * @return array|AuctionBid[]
     */
    public function getUnclaimedBids()
    {
        return $this->unclaimedBids;
    }

    /**
     * @return AuctionBid[]
     */
    public function getBids(): array
    {
        return $this->bids;
    }

    /**
     * @return AuctionBid|null
     */
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
}