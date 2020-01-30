<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\auction\AuctionBid;
use pocketmine\event\Cancellable;

/**
 * Class AuctionBidEvent
 * @package DaPigGuy\PiggyAuctions\events
 */
class AuctionBidEvent extends AuctionEvent implements Cancellable
{
    /** @var AuctionBid */
    protected $bid;

    /**
     * AuctionBidEvent constructor.
     * @param Auction $auction
     * @param AuctionBid $bid
     */
    public function __construct(Auction $auction, AuctionBid $bid)
    {
        parent::__construct($auction);
        $this->bid = $bid;
    }
}