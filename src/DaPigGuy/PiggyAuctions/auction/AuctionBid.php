<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\auction;

use DaPigGuy\PiggyAuctions\PiggyAuctions;

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

    public function __construct(int $auctionID, string $bidder, int $bidAmount, int $timestamp)
    {
        $this->auctionID = $auctionID;
        $this->bidder = $bidder;
        $this->bidAmount = $bidAmount;
        $this->timestamp = $timestamp;
    }

    public function getAuction(): ?Auction
    {
        return PiggyAuctions::getInstance()->getAuctionManager()->getAuction($this->auctionID);
    }

    public function getBidder(): string
    {
        return $this->bidder;
    }

    public function getBidAmount(): int
    {
        return $this->bidAmount;
    }

    public function setBidAmount(int $bidAmount): void
    {
        $this->bidAmount = $bidAmount;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}