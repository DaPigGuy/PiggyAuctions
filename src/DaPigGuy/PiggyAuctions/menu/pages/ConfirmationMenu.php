<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class ConfirmationMenu extends Menu
{
    /**
     * @param callable $callback
     */
    public function __construct(Player $player, private string $title, private Item $item, private string $confirm, private string $deny, private $callback)
    {
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->setName($this->title);
        $this->getInventory()->setItem(11, ItemFactory::getInstance()->get(ItemIds::STAINED_CLAY, 13)->setCustomName($this->confirm));
        $this->getInventory()->setItem(13, $this->item);
        $this->getInventory()->setItem(15, ItemFactory::getInstance()->get(ItemIds::STAINED_CLAY, 14)->setCustomName($this->deny));
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action, InventoryTransaction $transaction): bool
    {
        if ($action->getSlot() === 11 || $action->getSlot() === 15) {
            $this->setInventoryCloseListener(null);
            ($this->callback)($action->getSlot() === 11);
        }
        return false;
    }

    public function close(): void
    {
        parent::close();
        PiggyAuctions::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            ($this->callback)(false);
        }), 1);
    }
}