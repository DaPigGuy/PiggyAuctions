<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\Auction;
use pocketmine\event\Event;

/**
 * Class AuctionEvent
 * @package DaPigGuy\PiggyAuctions\events
 */
class AuctionEvent extends Event
{
    /** @var Auction */
    protected $auction;

    /**
     * AuctionEvent constructor.
     * @param Auction $auction
     */
    public function __construct(Auction $auction)
    {
        $this->auction = $auction;
    }

    /**
     * @return Auction
     */
    public function getAuction(): Auction
    {
        return $this->auction;
    }
}