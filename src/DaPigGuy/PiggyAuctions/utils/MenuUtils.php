<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\utils;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use muqsit\invmenu\inventories\BaseFakeInventory;
use muqsit\invmenu\inventories\ChestInventory;
use muqsit\invmenu\inventories\DoubleChestInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

/**
 * Class MenuUtils
 * @package DaPigGuy\PiggyAuctions\utils
 */
class MenuUtils
{
    const TF_RESET = TextFormat::RESET . TextFormat::GRAY;

    /**
     * @param Player $player
     */
    public static function displayMainMenu(Player $player): void
    {
        $menu = InvMenu::create(ChestInventory::class);
        $menu->setName("Auction House");
        //TODO: More detailed menu item lore/name
        $menu->getInventory()->setContents([
            11 => Item::get(Item::GOLD_BLOCK)->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Browse Auctions"),
            13 => Item::get(Item::GOLDEN_CARROT)->setCustomName(TextFormat::RESET . TextFormat::WHITE . "View Bids"),
            15 => Item::get(Item::GOLDEN_HORSE_ARMOR)->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Manage Auctions")
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            $player->removeWindow($action->getInventory());
            switch ($itemClicked->getId()) {
                case Item::GOLD_BLOCK:
                    self::displayAuctionBrowser($player);
                    break;
                case Item::GOLDEN_CARROT:
                    //TODO: Implement
                    break;
                case Item::GOLDEN_HORSE_ARMOR:
                    if (count(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($player)) < 1) {
                        self::displayAuctionCreator($player, true);
                        break;
                    }
                    self::displayAuctionManager($player);
                    break;
            }
            return false;
        });
        $menu->send($player);
    }

    /**
     * @param Player $player
     */
    public static function displayAuctionBrowser(Player $player): void
    {
        $menu = InvMenu::create(DoubleChestInventory::class);
        $menu->setName("Auction House");
        $page = $args["page"] ?? 1;
        $pageAuctions = self::displayPageAuctions($menu->getInventory(), $page);

        $updateTask = new ClosureTask(function (int $currentTick) use ($menu, $page) : void {
            foreach ($menu->getInventory()->getContents() as $slot => $content) {
                if ($content->getNamedTagEntry("AuctionID") !== null) {
                    $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction($content->getNamedTagEntry("AuctionID")->getValue());
                    if ($auction === null || $auction->hasExpired()) {
                        self::displayPageAuctions($menu->getInventory(), $page);
                        continue;
                    }
                    $lore = $content->getLore();
                    $lore[count($lore) - 1] = self::TF_RESET . "Ends in " . self::formatDuration($auction->getEndDate() - time());
                    $content->setLore($lore);
                    $menu->getInventory()->setItem($slot, $content);
                }
            }
        });
        PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask($updateTask, 1);

        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($page, $pageAuctions): bool {
            if (isset($pageAuctions[$action->getSlot()])) {
                $auction = $pageAuctions[$action->getSlot()];
                //TODO: Show auction page
            }
            if ($itemClicked->getId() === Item::ARROW && $itemClicked->getNamedTagEntry("Page") !== null) {
                $pageAuctions = self::displayPageAuctions($action->getInventory(), $itemClicked->getNamedTagEntry("Page")->getValue());
                $page = $itemClicked->getNamedTagEntry("Page")->getValue();
            }
            return false;
        });
        $menu->setInventoryCloseListener(function () use ($updateTask): void {
            if ($updateTask->getHandler() !== null) $updateTask->getHandler()->cancel();
        });
        $menu->send($player);
    }

    /**
     * @param Inventory $inventory
     * @param int $page
     * @return Auction[]
     */
    public static function displayPageAuctions(Inventory $inventory, int $page): array
    {
        $inventory->clearAll(false);

        $activeAuctions = PiggyAuctions::getInstance()->getAuctionManager()->getActiveAuctions();
        uasort($activeAuctions, function (Auction $a, Auction $b): bool {
            return $a->getEndDate() > $b->getEndDate();
        }); //TODO: Changeable sort type
        /** @var Auction $auction */
        foreach (array_slice($activeAuctions, ($page - 1) * 45, 45) as $slot => $auction) {
            $item = clone $auction->getItem();

            $lore = array_merge($item->getLore(), [
                "",
                self::TF_RESET . "Seller: " . $auction->getAuctioneer(),
                self::TF_RESET . "Bids: " . TextFormat::GREEN . count($auction->getBids()),
                ""
            ]);
            if ($auction->getTopBid() !== null) {
                $lore = array_merge($lore, [
                    self::TF_RESET . "Top Bid: " . TextFormat::GOLD . $auction->getTopBid()->getBidAmount(),
                    self::TF_RESET . "Bidder: " . TextFormat::GOLD . $auction->getTopBid()->getBidder(),
                ]);
            } else {
                $lore[] = self::TF_RESET . "Starting Bid: " . TextFormat::GOLD . $auction->getStartingBid();
            }
            $lore = array_merge($lore, [
                "",
                self::TF_RESET . "Ends in " . self::formatDuration($auction->getEndDate() - time())
            ]);

            $item->setNamedTagEntry(new IntTag("AuctionID", $auction->getId()));
            $inventory->setItem($slot, $item->setLore($lore), false);
        }
        if ($page > 1) {
            $previousPage = Item::get(Item::ARROW, 0, 1)->setCustomName("Previous Page\n(" . ($page - 1) . "/" . ceil(count($activeAuctions) / 45) . ")");
            $previousPage->setNamedTagEntry(new IntTag("Page", $page - 1));
            $inventory->setItem(45, $previousPage);
        }
        if ($page < ceil(count($activeAuctions) / 45)) {
            $nextPage = Item::get(Item::ARROW, 0, 1)->setCustomName("Next Page\n(" . ($page + 1) . "/" . ceil(count($activeAuctions) / 45) . ")");
            $nextPage->setNamedTagEntry(new IntTag("Page", $page + 1));
            $inventory->setItem(53, $nextPage);
        }
        return array_slice($activeAuctions, ($page - 1) * 45, 45);
    }

    /**
     * @param Player $player
     * @param bool $fromMainMenu
     */
    public static function displayAuctionCreator(Player $player, bool $fromMainMenu = false)
    {
        $menu = InvMenu::create(DoubleChestInventory::class);
        $menu->setName("Create Auction");
        for ($i = 0; $i < $menu->getInventory()->getSize(); $i++) $menu->getInventory()->setItem($i, Item::get(Item::BLEACH)->setCustomName(" "));
        $menu->getInventory()->setItem(13, Item::get(Item::AIR));
        $menu->getInventory()->setItem(29, Item::get(Item::STAINED_CLAY, 14));
        $menu->getInventory()->setItem(31, Item::get(Item::GOLD_INGOT));
        $menu->getInventory()->setItem(33, Item::get(Item::CLOCK));
        $menu->getInventory()->setItem(49, Item::get(Item::ARROW));
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use($fromMainMenu): bool {
            switch ($action->getSlot()) {
                case 13:
                    $action->getInventory()->setItem(13, $itemClickedWith);
                    $action->getInventory()->setItem(29, Item::get(Item::STAINED_CLAY, $itemClickedWith->getId() === Item::AIR ? 14 : 13));
                    return true;
                case 29:
                    if ($itemClicked->getDamage() === 13) {
                        //TODO: Customizable duration/start bid
                        PiggyAuctions::getInstance()->getAuctionManager()->addAuction($player->getName(), $action->getInventory()->getItem(13), time(), time() + 500, 50);
                        $action->getInventory()->clear(13);
                        $player->removeWindow($action->getInventory());
                        self::displayAuctionManager($player);
                    }
                    break;
                case 31: //TODO: Implement
                case 33:
                    break;
                case 49:
                    $player->removeWindow($action->getInventory());
                    PiggyAuctions::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $fromMainMenu): void {
                        if ($fromMainMenu) {
                            self::displayMainMenu($player);
                            return;
                        }
                        self::displayAuctionManager($player);
                    }), 1);
                    break;
            }
            return false;
        });
        $menu->setInventoryCloseListener(function (Player $player, BaseFakeInventory $inventory) {
            $player->getInventory()->addItem($inventory->getItem(13));
        });
        PiggyAuctions::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($menu, $player): void {
            $menu->send($player);
        }), 1);
    }

    /**
     * @param Player $player
     */
    public static function displayAuctionManager(Player $player): void
    {
        $menu = InvMenu::create(ChestInventory::class);
        $menu->setName("Auction Manager");
        for ($i = 0; $i < $menu->getInventory()->getSize(); $i++) $menu->getInventory()->setItem($i, Item::get(Item::BLEACH)->setCustomName(" "));
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            return false;
        });
        PiggyAuctions::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($menu, $player): void {
            $menu->send($player);
        }), 1);
    }

    /**
     * @param int $duration
     * @return string
     */
    public static function formatDuration(int $duration): string
    {
        $days = floor($duration / 86400);
        $hours = floor($duration / 3600 % 24);
        $minutes = floor($duration / 60 % 60);
        $seconds = floor($duration % 60);

        if ($days >= 1) {
            $dateString = $days . "d";
        } elseif ($hours > 6) {
            $dateString = $hours . "h";
        } elseif ($minutes >= 1) {
            $dateString = ($hours > 0 ? $hours . "h" : "") . $minutes . "m" . ($seconds == 0 ? "" : $seconds . "s");
        } else {
            $dateString = $seconds . "s";
        }

        return $dateString;
    }
}