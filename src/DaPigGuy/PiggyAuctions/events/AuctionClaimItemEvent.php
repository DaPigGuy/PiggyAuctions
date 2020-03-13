<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\Auction;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;

class AuctionClaimItemEvent extends AuctionEvent implements Cancellable
{
    /** @var Player */
    private $player;
    /** @var Item */
    private $item;

    public function __construct(Auction $auction, Player $player, Item $item)
    {
        parent::__construct($auction);
        $this->player = $player;
        $this->item = $item;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): void
    {
        $this->item = $item;
    }
}