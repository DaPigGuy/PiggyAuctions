<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\auction;

use DaPigGuy\PiggyAuctions\PiggyAuctions;

class AuctionBid
{
    public function __construct(public int $auctionID, public string $bidder, public int $bidAmount, public int $timestamp)
    {
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