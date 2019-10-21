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

    /**
     * AuctionBid constructor.
     * @param string $bidder
     * @param int $bidAmount
     */
    public function __construct(string $bidder, int $bidAmount)
    {
        $this->bidder = $bidder;
        $this->bidAmount = $bidAmount;
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
}