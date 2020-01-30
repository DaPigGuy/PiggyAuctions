<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\AuctionBid;
use pocketmine\event\Cancellable;

/**
 * Class AuctionBidEvent
 * @package DaPigGuy\PiggyAuctions\events
 */
class AuctionBidEvent extends AuctionEvent implements Cancellable
{
    /*** @var AuctionBid */
    protected $bid;

    /**
     * AuctionBidEvent constructor.
     * @param AuctionBid $bid
     */
    public function __construct(AuctionBid $bid)
    {
        parent::__construct($bid->getAuction());
        $this->bid = $bid;
    }
}