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

    /** @var Player */
    private $player;
    /** @var int */
    private $amount;

    public function __construct(Auction $auction, Player $player, int $amount)
    {
        parent::__construct($auction);
        $this->player = $player;
        $this->amount = $amount;
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