<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;

class StatsMenu extends Menu
{
    public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        if ($action->getSlot() === 22) {
            new MainMenu($player);
        }
        return false;
    }

    public function render(): void
    {
        $this->menu->setName(PiggyAuctions::getInstance()->getMessage("menus.stats.title"));

        $sellerStats = PiggyAuctions::getInstance()->getMessage("menus.stats.seller-stats");
        preg_match_all("/{STAT_(.*?)}/", $sellerStats, $matches);
        foreach ($matches[0] as $index => $match) {
            $sellerStats = str_replace($match, (string)PiggyAuctions::getInstance()->getStatsManager()->getStatistics($this->player)->getStatistic($matches[1][$index]), $sellerStats);
        }

        $buyerStats = PiggyAuctions::getInstance()->getMessage("menus.stats.buyer-stats");
        preg_match_all("/{STAT_(.*?)}/", $buyerStats, $matches);
        foreach ($matches[0] as $index => $match) {
            $buyerStats = str_replace($match, (string)PiggyAuctions::getInstance()->getStatsManager()->getStatistics($this->player)->getStatistic($matches[1][$index]), $buyerStats);
        }

        $this->menu->getInventory()->setContents([
            11 => Item::get(Item::EMPTYMAP)->setCustomName($sellerStats),
            15 => Item::get(Item::MAP)->setCustomName($buyerStats),
            22 => Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back"))
        ]);
    }
}