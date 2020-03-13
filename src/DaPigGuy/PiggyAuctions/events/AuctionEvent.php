<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\Auction;
use pocketmine\event\Event;

class AuctionEvent extends Event
{
    /** @var Auction */
    protected $auction;

    public function __construct(Auction $auction)
    {
        $this->auction = $auction;
    }

    public function getAuction(): Auction
    {
        return $this->auction;
    }
}