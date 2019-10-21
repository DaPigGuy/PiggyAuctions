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
    public $endDate;
    /** @var array|AuctionBid[] */
    public $bids;

    /**
     * Auction constructor.
     * @param int $id
     * @param string $auctioneer
     * @param Item $item
     * @param int $endDate
     * @param AuctionBid[] $bids
     */
    public function __construct(int $id, string $auctioneer, Item $item, int $endDate, array $bids)
    {
        $this->id = $id;
        $this->auctioneer = $auctioneer;
        $this->item = $item;
        $this->endDate = $endDate;
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
    public function getEndDate(): int
    {
        return $this->endDate;
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