<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions;

use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\menu\pages\AuctionCreatorMenu;
use muqsit\invmenu\session\PlayerManager;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

class EventListener implements Listener
{
    /** @var PiggyAuctions */
    private $plugin;

    public function __construct(PiggyAuctions $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();
        if ($packet instanceof ContainerClosePacket) {
            if (isset(Menu::$awaitingInventoryClose[$player->getName()])) {
                Menu::$awaitingInventoryClose[$player->getName()]->send($player);
                unset(Menu::$awaitingInventoryClose[$player->getName()]);
            }
        }
    }

    public function onPreLogin(PlayerLoginEvent $event): void
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
                    $player->getNetworkSession()->getInvManager()->syncSlot($action->getInventory(), $action->getSlot());
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