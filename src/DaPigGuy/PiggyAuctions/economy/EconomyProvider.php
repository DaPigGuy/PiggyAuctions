<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\economy;

use pocketmine\Player;

/**
 * Interface EconomyProvider
 * @package DaPigGuy\PiggyAuctions\economy
 */
interface EconomyProvider
{
    /**
     * @param Player $player
     * @return int
     */
    public function getMoney(Player $player): int;

    /**
     * @param Player $player
     * @param int $amount
     */
    public function giveMoney(Player $player, int $amount): void;

    /**
     * @param Player $player
     * @param int $amount
     */
    public function takeMoney(Player $player, int $amount): void;

    /**
     * @param Player $player
     * @param int $amount
     */
    public function setMoney(Player $player, int $amount): void;
}