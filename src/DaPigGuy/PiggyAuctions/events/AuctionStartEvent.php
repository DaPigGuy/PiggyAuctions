<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\item\Item;
use pocketmine\player\Player;

class AuctionStartEvent extends Event implements Cancellable
{
    /** @var Player */
    protected $player;
    /** @var Item */
    protected $item;
    /** @var int */
    protected $timestamp;
    /** @var int */
    protected $endDate;
    /** @var int */
    protected $startingBid;

    public function __construct(Player $player, Item $item, int $timestamp, int $endDate, int $startingBid)
    {
        $this->player = $player;
        $this->item = $item;
        $this->timestamp = $timestamp;
        $this->endDate = $endDate;
        $this->startingBid = $startingBid;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * @param Item $item
     */
    public function setItem(Item $item): void
    {
        $this->item = $item;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getEndDate(): int
    {
        return $this->endDate;
    }

    public function setEndDate(int $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getStartingBid(): int
    {
        return $this->startingBid;
    }

    public function setStartingBid(int $startingBid): void
    {
        $this->startingBid = $startingBid;
    }

    public function getAuctionData(): array
    {
        return [$this->player->getName(), $this->item, $this->timestamp, $this->endDate, $this->startingBid];
    }
}