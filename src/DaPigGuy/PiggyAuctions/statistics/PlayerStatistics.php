<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\statistics;

use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\Player;

/**
 * Class PlayerStatistics
 * @package DaPigGuy\PiggyAuctions\statistics
 */
class PlayerStatistics implements \JsonSerializable
{
    /** @var Player */
    private $player;
    /** @var int[] */
    private $statistics;

    /**
     * PlayerStatistics constructor.
     * @param Player $player
     * @param array $statistics
     */
    public function __construct(Player $player, array $statistics)
    {
        $this->player = $player;
        $this->statistics = $statistics;
    }

    /**
     * @param string $name
     * @return int
     */
    public function getStatistic(string $name): int
    {
        return $this->statistics[$name] ?? 0;
    }

    /**
     * @param string $name
     * @param int $value
     */
    public function setStatistic(string $name, int $value): void
    {
        $this->statistics[$name] = $value;
        PiggyAuctions::getInstance()->getStatsManager()->saveStatistics($this->player);
    }

    /**
     * @param string $name
     * @param int $amount
     */
    public function incrementStatistic(string $name, int $amount = 1): void
    {
        if (!isset($this->statistics[$name])) $this->statistics[$name] = 0;
        $this->statistics[$name] += $amount;
        PiggyAuctions::getInstance()->getStatsManager()->saveStatistics($this->player);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->statistics;
    }
}