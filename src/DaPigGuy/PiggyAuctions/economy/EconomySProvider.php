<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\economy;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

/**
 * Class EconomySProvider
 * @package DaPigGuy\PiggyAuctions\economy
 */
class EconomySProvider implements EconomyProvider
{
    /** @var EconomyAPI */
    public $economyAPI;

    public function __construct()
    {
        $this->economyAPI = EconomyAPI::getInstance();
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getMoney(Player $player): int
    {
        return (int)$this->economyAPI->myMoney($player);
    }

    /**
     * @param Player $player
     * @param int $amount
     */
    public function giveMoney(Player $player, int $amount): void
    {
        $this->economyAPI->addMoney($player, $amount);
    }

    /**
     * @param Player $player
     * @param int $amount
     */
    public function takeMoney(Player $player, int $amount): void
    {
        $this->economyAPI->reduceMoney($player, $amount);
    }

    /**
     * @param Player $player
     * @param int $amount
     */
    public function setMoney(Player $player, int $amount): void
    {
        $this->economyAPI->setMoney($player, $amount);
    }
}