<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\statistics;

use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\Player;

/**
 * Class StatisticsManager
 * @package DaPigGuy\PiggyAuctions\statistics
 */
class StatisticsManager
{
    /** @var PiggyAuctions */
    private $plugin;
    /** @var PlayerStatistics[] */
    private $statistics;

    /**
     * StatisticsManager constructor.
     * @param PiggyAuctions $plugin
     */
    public function __construct(PiggyAuctions $plugin)
    {
        $this->plugin = $plugin;
        $this->plugin->getDatabase()->executeGeneric("piggyauctions.statistics.init");
    }

    /**
     * @param Player $player
     */
    public function loadStatistics(Player $player): void
    {
        $this->plugin->getDatabase()->executeSelect("piggyauctions.statistics.load", ["player" => $player->getName()], function (array $rows) use ($player): void {
            if (count($rows) === 0) {
                $this->plugin->getDatabase()->executeInsert("piggyauctions.statistics.add", ["player" => $player->getName(), "stats" => "{}"]);
                $this->statistics[$player->getName()] = new PlayerStatistics($player, []);
                return;
            }
            $this->statistics[$player->getName()] = new PlayerStatistics($player, json_decode($rows[0]["stats"], true));
        });
    }

    /**
     * @param Player $player
     */
    public function saveStatistics(Player $player): void
    {
        $this->plugin->getDatabase()->executeGeneric("piggyauctions.statistics.update", ["player" => $player->getName(), "stats" => json_encode($this->getStatistics($player))]);
    }

    /**
     * @param Player $player
     * @return PlayerStatistics
     */
    public function getStatistics(Player $player): PlayerStatistics
    {
        return $this->statistics[$player->getName()] ?? new PlayerStatistics($player, []);
    }

    /**
     * @param Player $player
     */
    public function unloadStatistics(Player $player): void
    {
        unset($this->statistics[$player->getName()]);
    }
}