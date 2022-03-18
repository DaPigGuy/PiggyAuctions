<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\Auction;
use pocketmine\event\Event;

class AuctionEvent extends Event
{
    public function __construct(protected Auction $auction)
    {
    }

    public function getAuction(): Auction
    {
        return $this->auction;
    }
}