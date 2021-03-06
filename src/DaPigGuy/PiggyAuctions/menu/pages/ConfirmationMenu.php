<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

class ConfirmationMenu extends Menu
{
    /** @var string */
    private $title;
    /** @var Item */
    private $item;
    /** @var string */
    private $confirm;
    /** @var string */
    private $deny;
    /** @var callable */
    private $callback;

    public function __construct(Player $player, string $title, Item $item, string $confirm, string $deny, callable $callback)
    {
        $this->title = $title;
        $this->item = $item;
        $this->confirm = $confirm;
        $this->deny = $deny;
        $this->callback = $callback;
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->setName($this->title);
        $this->getInventory()->setItem(11, ItemFactory::get(ItemIds::STAINED_CLAY, 13)->setCustomName($this->confirm));
        $this->getInventory()->setItem(13, $this->item);
        $this->getInventory()->setItem(15, ItemFactory::get(ItemIds::STAINED_CLAY, 14)->setCustomName($this->deny));
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action, InvMenuTransaction $transaction): InvMenuTransactionResult
    {
        if ($action->getSlot() === 11 || $action->getSlot() === 15) {
            $this->setInventoryCloseListener(null);
            ($this->callback)($action->getSlot() === 11);
        }
        return $transaction->discard();
    }

    public function close(): void
    {
        parent::close();
        PiggyAuctions::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            ($this->callback)(false);
        }), 1);
    }
}