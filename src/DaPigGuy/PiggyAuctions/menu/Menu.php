<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\session\PlayerManager;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class Menu extends SharedInvMenu
{
    /** @var SharedInvMenu[] */
    public static $awaitingInventoryClose;

    /** @var Player */
    protected $player;

    /** @var string */
    protected $inventoryIdentifier = InvMenu::TYPE_CHEST;

    public function __construct(Player $player)
    {
        parent::__construct(InvMenuHandler::getMenuType($this->inventoryIdentifier));
        $this->player = $player;
        $this->setInventoryCloseListener([$this, "close"]);

        $this->render();
        $this->display();
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
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
            $this->player->removeWindow($oldMenu->getInventoryForPlayer($this->player));
            Menu::$awaitingInventoryClose[$this->player->getName()] = $this;
        } else {
            $this->send($this->player);
        }
    }

    public function handleInventoryTransaction(Player $player, Item $in, Item $out, SlotChangeAction $action): bool
    {
        return parent::handleInventoryTransaction($player, $in, $out, $action) && $this->handle($in, $out, $action);
    }
}