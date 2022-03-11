<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\Auction;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class AuctionClaimMoneyEvent extends AuctionEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(Auction $auction, private Player $player, private int $amount)
    {
        parent::__construct($auction);
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }
}