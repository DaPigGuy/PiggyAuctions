<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\utils;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use DaPigGuy\PiggyAuctions\utils\Utils;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;

class MenuUtils
{
    /**
     * @param Auction[] $auctions
     * @return Auction[]
     */
    public static function updateDisplayedItems(SharedInvMenu $menu, array $auctions, int $arrayOffset, int $offsetSlot, int $displayCount, ?callable $itemIndexFunction = null, ?callable $sortFunction = null): array
    {
        $itemIndexFunction = $itemIndexFunction ?? static function ($index) use ($offsetSlot): int {
                return $index + $offsetSlot;
            };
        $sortFunction = $sortFunction ?? static function (Auction $a, Auction $b): bool {
                return $a->getEndDate() > $b->getEndDate();
            };
        uasort($auctions, $sortFunction);
        foreach (array_slice($auctions, $arrayOffset, $displayCount) as $index => $auction) {
            $menu->getInventory()->setItem(($itemIndexFunction)($index), self::getDisplayItem($auction));
        }
        return array_slice($auctions, $arrayOffset, $displayCount);
    }

    public static function getDisplayItem(Auction $auction): Item
    {
        $item = clone $auction->getItem();

        $status = PiggyAuctions::getInstance()->getMessage("menus.auction-view.status-ongoing");
        if ($auction->hasExpired()) $status = PiggyAuctions::getInstance()->getMessage("menus.auction-view.status-ended");
        $lore = PiggyAuctions::getInstance()->getMessage("menus.auction-view.item-description-no-bid", ["{PLAYER}" => $auction->getAuctioneer(), "{BIDS}" => 0, "{STARTINGBID}" => $auction->getStartingBid(), "{STATUS}" => $status]);
        if ($auction->getTopBid() !== null) $lore = PiggyAuctions::getInstance()->getMessage("menus.auction-view.item-description", ["{PLAYER}" => $auction->getAuctioneer(), "{BIDS}" => count($auction->getBids()), "{TOPBID}" => $auction->getTopBid()->getBidAmount(), "{TOPBIDDER}" => $auction->getTopBid()->getBidder(), "{STATUS}" => $status]);
        $lore = str_replace("{DURATION}", Utils::formatDetailedDuration($auction->getEndDate() - time()), $lore);

        $item->setNamedTagEntry(new IntTag("AuctionID", $auction->getId()));
        return $item->setLore(array_merge($item->getLore(), explode("\n", $lore)));
    }
}