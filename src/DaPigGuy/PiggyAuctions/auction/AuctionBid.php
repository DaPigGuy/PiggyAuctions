<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\auction;

/**
 * Class AuctionBid
 * @package DaPigGuy\PiggyAuctions\auction
 */
class AuctionBid
{
    /** @var string */
    public $bidder;
    /** @var int */
    public $bidAmount;
    /** @var int */
    public $timestamp;

    /**
     * AuctionBid constructor.
     * @param string $bidder
     * @param int $bidAmount
     * @param int $timestamp
     */
    public function __construct(string $bidder, int $bidAmount, int $timestamp)
    {
        $this->bidder = $bidder;
        $this->bidAmount = $bidAmount;
        $this->timestamp = $timestamp;
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