<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class Menu extends InvMenu
{
    protected string $inventoryIdentifier = InvMenuTypeIds::TYPE_CHEST;

    public function __construct(protected Player $player)
    {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get($this->inventoryIdentifier));
        $this->setInventoryCloseListener($this->close(...));
        $this->render();
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action, InvMenuTransaction $transaction): InvMenuTransactionResult
    {
        return $transaction->discard();
    }

    public function close(): void
    {
    }

    abstract public function render(): void;

    public function display(): void
    {
        $this->send($this->player);
    }

    public function handleInventoryTransaction(Player $player, Item $out, Item $in, SlotChangeAction $action, InventoryTransaction $transaction): InvMenuTransactionResult
    {
        if (!$this->handle($out, $in, $action, $transaction)) {
            return new InvMenuTransactionResult(true);
        }
        return parent::handleInventoryTransaction($player, $out, $in, $action, $transaction);
    }
}
