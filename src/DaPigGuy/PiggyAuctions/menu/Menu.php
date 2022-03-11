<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu;

use Closure;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\session\PlayerManager;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class Menu extends InvMenu
{
    /** @var InvMenu[] */
    public static array $awaitingInventoryClose;

    protected string $inventoryIdentifier = InvMenu::TYPE_CHEST;

    public function __construct(protected Player $player)
    {
        parent::__construct(InvMenuHandler::getMenuType($this->inventoryIdentifier));
        $this->setInventoryCloseListener(Closure::fromCallable([$this, "close"]));

        $this->render();
        $this->display();
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action, InventoryTransaction $transaction): bool
    {
        return false;
    }

    public function close(): void
    {
    }

    abstract public function render(): void;

    public function display(): void
    {
        if (PlayerManager::get($this->player) === null) return;
        $oldMenu = PlayerManager::get($this->player)->getCurrentMenu();
        if ($oldMenu !== null) {
            $this->onClose($this->player);
            Menu::$awaitingInventoryClose[$this->player->getName()] = $this;
        } else {
            $this->send($this->player);
        }
    }

    public function handleInventoryTransaction(Player $player, Item $out, Item $in, SlotChangeAction $action, InventoryTransaction $transaction): InvMenuTransactionResult
    {
        if (!$this->handle($out, $in, $action, $transaction)) {
            return new InvMenuTransactionResult(true);
        }
        return parent::handleInventoryTransaction($player, $out, $in, $action, $transaction);
    }
}