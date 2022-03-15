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
use pocketmine\player\Player;

class StatsMenu extends Menu
{
    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action, InvMenuTransaction $transaction): InvMenuTransactionResult
    {
        if ($action->getSlot() === 22) {
            return $transaction->discard()->then(function (Player $player): void {
                (new MainMenu($player))->display();
            });
        }
        return $transaction->discard();
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
            11 => ItemFactory::getInstance()->get(ItemIds::EMPTYMAP)->setCustomName($sellerStats),
            15 => ItemFactory::getInstance()->get(ItemIds::MAP)->setCustomName($buyerStats),
            22 => ItemFactory::getInstance()->get(ItemIds::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back"))
        ]);
    }
}