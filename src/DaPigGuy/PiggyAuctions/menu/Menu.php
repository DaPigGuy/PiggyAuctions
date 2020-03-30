<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\session\PlayerManager;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;

abstract class Menu
{
    /** @var InvMenu[] */
    public static $awaitingInventoryClose;

    /** @var Player|null */
    protected $player;
    /** @var SharedInvMenu|null */
    protected $menu;

    /** @var string */
    protected $inventoryIdentifier = InvMenu::TYPE_CHEST;

    public function __construct(Player $player)
    {
        $this->player = $player;

        $this->menu = InvMenu::create($this->inventoryIdentifier);
        $this->menu->setListener([$this, "handle"]);
        $this->menu->setInventoryCloseListener([$this, "close"]);

        $this->render();
        $this->display();
    }

    public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        return false;
    }

    public function close(): void
    {
        $this->player = $this->menu = null;
    }

    abstract public function render(): void;

    public function display(): void
    {
        if (PlayerManager::get($this->player) === null) return;
        $oldMenu = PlayerManager::get($this->player)->getCurrentMenu();
        if ($oldMenu !== null) {
            $this->player->removeWindow($oldMenu->getInventoryForPlayer($this->player));
            Menu::$awaitingInventoryClose[$this->player->getName()] = $this->menu;
        } else {
            $this->menu->send($this->player);
        }
    }
}