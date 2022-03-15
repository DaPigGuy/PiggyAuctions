<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\auction\AuctionBid;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class AuctionBidEvent extends AuctionEvent implements Cancellable
{
    use CancellableTrait;

    /** @var AuctionBid */
    protected $bid;

    public function __construct(Auction $auction, AuctionBid $bid)
    {
        parent::__construct($auction);
        $this->bid = $bid;
    }

    public function getBid(): AuctionBid
    {
        return $this->bid;
    }
}