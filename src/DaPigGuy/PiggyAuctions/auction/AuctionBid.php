<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\auction;

use DaPigGuy\PiggyAuctions\PiggyAuctions;

/**
 * Class AuctionBid
 * @package DaPigGuy\PiggyAuctions\auction
 */
class AuctionBid
{
    /** @var int */
    public $auctionID;
    /** @var string */
    public $bidder;
    /** @var int */
    public $bidAmount;
    /** @var int */
    public $timestamp;

    /**
     * AuctionBid constructor.
     * @param int $auctionID
     * @param string $bidder
     * @param int $bidAmount
     * @param int $timestamp
     */
    public function __construct(int $auctionID, string $bidder, int $bidAmount, int $timestamp)
    {
        $this->auctionID = $auctionID;
        $this->bidder = $bidder;
        $this->bidAmount = $bidAmount;
        $this->timestamp = $timestamp;
    }

    /**
     * @return Auction
     */
    public function getAuction(): Auction
    {
        return PiggyAuctions::getInstance()->getAuctionManager()->getAuction($this->auctionID);
    }

    /**
     * @return string
     */
    public function getBidder(): string
    {
        return $this->bidder;
    }

    /**
     * @return int
     */
    public function getBidAmount(): int
    {
        return $this->bidAmount;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}