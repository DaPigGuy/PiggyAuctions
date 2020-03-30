<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;

class MainMenu extends Menu
{
    public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        switch ($action->getSlot()) {
            case 11:
                new AuctionBrowserMenu($player);
                break;
            case 13:
                new BidsMenu($player);
                break;
            case 15:
                if (count(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($player)) < 1) {
                    new AuctionCreatorMenu($player);
                    break;
                }
                new AuctionManagerMenu($player);
                break;
            case 26:
                new StatsMenu($player);
                break;
        }
        return false;
    }

    public function render(): void
    {
        $this->menu->setName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.title"));
        $this->menu->getInventory()->setContents([
            11 => Item::get(Item::GOLD_BLOCK)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.browse-auctions")),
            13 => Item::get(Item::GOLDEN_CARROT)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.view-bids")),
            15 => Item::get(Item::GOLDEN_HORSE_ARMOR)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.manage-auctions")),
            26 => Item::get(Item::MAP)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.auction-stats"))
        ]);
    }
}