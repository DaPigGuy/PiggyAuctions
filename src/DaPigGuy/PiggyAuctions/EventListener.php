<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;

/**
 * Class EventListener
 * @package DaPigGuy\PiggyAuctions
 */
class EventListener implements Listener
{
    /** @var PiggyAuctions */
    private $plugin;

    /**
     * EventListener constructor.
     * @param PiggyAuctions $plugin
     */
    public function __construct(PiggyAuctions $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerPreLoginEvent $event
     */
    public function onPreLogin(PlayerPreLoginEvent $event): void
    {
        $this->plugin->getStatsManager()->loadStatistics($event->getPlayer());
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        $this->plugin->getStatsManager()->unloadStatistics($event->getPlayer());
    }

    /**
     * @param InventoryTransactionEvent $event
     * @priority MONITOR
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        foreach ($transaction->getActions() as $action) {
            if ($action instanceof SlotChangeAction && $event->isCancelled()) $action->getInventory()->sendSlot($action->getSlot(), $player);
        }
    }
}