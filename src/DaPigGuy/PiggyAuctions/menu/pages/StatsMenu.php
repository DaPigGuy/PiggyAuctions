<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class StatsMenu extends Menu
{
    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        if ($action->getSlot() === 22) {
            new MainMenu($this->player);
        }
        return false;
    }

    public function render(): void
    {
        $this->setName(PiggyAuctions::getInstance()->getMessage("menus.stats.title"));

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

        $this->getInventory()->setContents([
            11 => ItemFactory::get(ItemIds::EMPTYMAP)->setCustomName($sellerStats),
            15 => ItemFactory::get(ItemIds::MAP)->setCustomName($buyerStats),
            22 => ItemFactory::get(ItemIds::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back"))
        ]);
    }
}