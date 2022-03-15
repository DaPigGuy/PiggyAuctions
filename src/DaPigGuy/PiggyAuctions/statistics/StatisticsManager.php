<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\statistics;

use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\player\Player;

class StatisticsManager
{
    /** @var PlayerStatistics[] */
    private array $statistics;

    public function __construct(private PiggyAuctions $plugin)
    {
        $this->plugin->getDatabase()->executeGeneric("piggyauctions.statistics.init");
    }

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

    public function saveStatistics(Player $player): void
    {
        $this->plugin->getDatabase()->executeGeneric("piggyauctions.statistics.update", ["player" => $player->getName(), "stats" => json_encode($this->getStatistics($player))]);
    }

    public function getStatistics(Player $player): PlayerStatistics
    {
        return $this->statistics[$player->getName()] ?? new PlayerStatistics($player, []);
    }

    public function unloadStatistics(Player $player): void
    {
        unset($this->statistics[$player->getName()]);
    }
}