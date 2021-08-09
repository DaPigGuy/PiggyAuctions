<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu;

use Closure;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\Player;

abstract class Menu extends InvMenu
{
    /** @var Player */
    protected $player;

    /** @var string */
    protected $inventoryIdentifier = InvMenu::TYPE_CHEST;

    public function __construct(Player $player)
    {
        /** @phpstan-ignore-next-line */
        parent::__construct(InvMenuHandler::getMenuType($this->inventoryIdentifier));
        $this->player = $player;
        $this->setInventoryCloseListener(Closure::fromCallable([$this, "close"]));
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
        $invMenuTransaction = new InvMenuTransaction($player, $out, $in, $action, $transaction);
        return $this->handle($out, $in, $action, $invMenuTransaction);
    }
}
