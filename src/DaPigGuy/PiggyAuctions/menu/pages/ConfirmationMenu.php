<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
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
        $this->getInventory()->setItem(11, VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::GREEN())->asItem()->setCustomName($this->confirm));
        $this->getInventory()->setItem(13, $this->item);
        $this->getInventory()->setItem(15, VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::RED())->asItem()->setCustomName($this->deny));
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