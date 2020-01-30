<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\events;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\item\Item;
use pocketmine\Player;

/**
 * Class AuctionStartEvent
 * @package DaPigGuy\PiggyAuctions\events
 */
class AuctionStartEvent extends Event implements Cancellable
{
    /*** @var Player */
    protected $player;
    /*** @var Item */
    protected $item;
    /*** @var int */
    protected $timestamp;
    /*** @var int */
    protected $endDate;
    /*** @var int */
    protected $startingBid;

    /**
     * AuctionStartEvent constructor.
     * @param Player $player
     * @param Item $item
     * @param int $timestamp
     * @param int $endDate
     * @param int $startingBid
     */
    public function __construct(Player $player, Item $item, int $timestamp, int $endDate, int $startingBid)
    {
        $this->player = $player;
        $this->item = $item;
        $this->timestamp = $timestamp;
        $this->endDate = $endDate;
        $this->startingBid = $startingBid;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @param Player $player
     */
    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    /**
     * @return Item
     */
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

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return int
     */
    public function getEndDate(): int
    {
        return $this->endDate;
    }

    /**
     * @param int $endDate
     */
    public function setEndDate(int $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * @return int
     */
    public function getStartingBid(): int
    {
        return $this->startingBid;
    }

    /**
     * @param int $startingBid
     */
    public function setStartingBid(int $startingBid): void
    {
        $this->startingBid = $startingBid;
    }

    /**
     * @return array
     */
    public function getAuctionData(): array
    {
        return [$this->player->getName(), $this->item, $this->timestamp, $this->endDate, $this->startingBid];
    }
}