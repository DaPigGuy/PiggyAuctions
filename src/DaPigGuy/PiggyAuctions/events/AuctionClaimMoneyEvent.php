<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use DaPigGuy\PiggyAuctions\auction\Auction;
use pocketmine\event\Cancellable;
use pocketmine\Player;

/**
 * Class AuctionClaimMoneyEvent
 * @package DaPigGuy\PiggyAuctions\events
 */
class AuctionClaimMoneyEvent extends AuctionEvent implements Cancellable
{
    /** @var Player */
    private $player;
    /** @var int */
    private $amount;

    /**
     * AuctionEvent constructor.
     * @param Auction $auction
     * @param Player $player
     * @param int $amount
     */
    public function __construct(Auction $auction, Player $player, int $amount)
    {
        parent::__construct($auction);
        $this->player = $player;
        $this->amount = $amount;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }
}