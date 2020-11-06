<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions;

use DaPigGuy\PiggyAuctions\menu\pages\AuctionCreatorMenu;
use muqsit\invmenu\session\PlayerManager;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;

class EventListener implements Listener
{
    /** @var PiggyAuctions */
    private $plugin;

    public function __construct(PiggyAuctions $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onPreLogin(PlayerPreLoginEvent $event): void
    {
        $this->plugin->getStatsManager()->loadStatistics($event->getPlayer());
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $this->plugin->getStatsManager()->unloadStatistics($event->getPlayer());
    }

    /**
     * @priority MONITOR
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        $session = PlayerManager::get($player);
        foreach ($transaction->getActions() as $action) {
            if ($action instanceof SlotChangeAction) {
                if ($event->isCancelled()) {
                    $action->getInventory()->sendSlot($action->getSlot(), $player);
                } else {
                    if ($session !== null) {
                        $menu = $session->getCurrentMenu();
                        if ($action->getSlot() === 13 && $menu instanceof AuctionCreatorMenu && $menu->getInventory() === $action->getInventory()) $menu->setItem($action->getTargetItem());
                    }
                }
            }
        }
    }
}