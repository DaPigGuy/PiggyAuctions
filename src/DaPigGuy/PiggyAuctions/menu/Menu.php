<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\auction\AuctionBid;
use DaPigGuy\PiggyAuctions\events\AuctionBidEvent;
use DaPigGuy\PiggyAuctions\events\AuctionStartEvent;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use DaPigGuy\PiggyAuctions\tasks\InventoryClosureTask;
use DaPigGuy\PiggyAuctions\utils\Utils;
use jojoe77777\FormAPI\CustomForm;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\session\PlayerManager;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Menu
{
    const PAGE_LENGTH = 45;

    /** @var InvMenu[] */
    public static $displayQueue;

    public static function displayMenu(Player $player, InvMenu $menu): void
    {
        if (PlayerManager::get($player) === null) return;
        $oldMenu = PlayerManager::get($player)->getCurrentMenu();
        if ($oldMenu !== null) {
            $player->removeWindow($oldMenu->getInventoryForPlayer($player));
            self::$displayQueue[$player->getName()] = $menu;
        } else {
            $menu->send($player);
        }
    }

    public static function displayMainMenu(Player $player): void
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.title"));
        $menu->getInventory()->setContents([
            11 => Item::get(Item::GOLD_BLOCK)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.browse-auctions")),
            13 => Item::get(Item::GOLDEN_CARROT)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.view-bids")),
            15 => Item::get(Item::GOLDEN_HORSE_ARMOR)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.manage-auctions")),
            26 => Item::get(Item::MAP)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.main-menu.auction-stats"))
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            switch ($action->getSlot()) {
                case 11:
                    self::displayAuctionBrowser($player);
                    break;
                case 13:
                    self::displayBidsPage($player);
                    break;
                case 15:
                    if (count(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($player)) < 1) {
                        self::displayAuctionCreator($player);
                        break;
                    }
                    self::displayAuctionManager($player);
                    break;
                case 26:
                    self::displayAuctionStats($player);
                    break;
            }
            return false;
        });
        self::displayMenu($player, $menu);
    }

    public static function displayAuctionStats(Player $player): void
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.stats.title"));

        $sellerStats = PiggyAuctions::getInstance()->getMessage("menus.stats.seller-stats");
        preg_match_all("/{STAT_(.*?)}/", $sellerStats, $matches);
        foreach ($matches[0] as $index => $match) {
            $sellerStats = str_replace($match, (string)PiggyAuctions::getInstance()->getStatsManager()->getStatistics($player)->getStatistic($matches[1][$index]), $sellerStats);
        }

        $buyerStats = PiggyAuctions::getInstance()->getMessage("menus.stats.buyer-stats");
        preg_match_all("/{STAT_(.*?)}/", $buyerStats, $matches);
        foreach ($matches[0] as $index => $match) {
            $buyerStats = str_replace($match, (string)PiggyAuctions::getInstance()->getStatsManager()->getStatistics($player)->getStatistic($matches[1][$index]), $buyerStats);
        }

        $menu->getInventory()->setContents([
            11 => Item::get(Item::EMPTYMAP)->setCustomName($sellerStats),
            15 => Item::get(Item::MAP)->setCustomName($buyerStats),
            22 => Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back"))
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            if ($action->getSlot() === 22) self::displayMainMenu($player);
            return false;
        });
        self::displayMenu($player, $menu);
    }

    public static function displayAuctionBrowser(Player $player, int $page = 1, string $search = "", int $sortType = MenuSort::TYPE_HIGHEST_BID): void
    {
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.title"));
        self::displayPageAuctions($menu->getInventory(), $page, $search, $sortType);

        PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new InventoryClosureTask($player, $menu->getInventory(), function () use ($menu, $page) : void {
            foreach ($menu->getInventory()->getContents() as $slot => $content) {
                if ($content->getNamedTagEntry("AuctionID") !== null) {
                    $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($content->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
                    if ($auction === null || $auction->hasExpired()) {
                        self::displayPageAuctions($menu->getInventory(), $page, ($menu->getInventory()->getItem(48)->getNamedTagEntry("Search") ?? new StringTag())->getValue(), ($menu->getInventory()->getItem(50)->getNamedTagEntry("SortType") ?? new IntTag())->getValue());
                        break;
                    }
                    $menu->getInventory()->setItem($slot, $content);
                }
            }
        }), 20);

        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            $search = ($action->getInventory()->getItem(48)->getNamedTagEntry("Search") ?? new StringTag())->getValue();
            $sort = ($action->getInventory()->getItem(50)->getNamedTagEntry("SortType") ?? new IntTag())->getValue();
            if ($itemClicked->getNamedTagEntry("AuctionID") !== null) {
                $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
                if ($auction instanceof Auction) {
                    $returnPage = ($action->getInventory()->getItem(49)->getNamedTagEntry("CurrentPage") ?? new IntTag("", 1))->getValue();
                    self::displayItemPage($player, $auction, function (Player $player) use ($search, $returnPage, $sort) {
                        self::displayAuctionBrowser($player, $returnPage, $search, $sort);
                    });
                }
            }
            switch ($action->getSlot()) {
                case 45:
                case 53:
                self::displayPageAuctions($action->getInventory(), ($itemClicked->getNamedTagEntry("Page") ?? new IntTag("", 1))->getValue(), ($action->getInventory()->getItem(48)->getNamedTagEntry("Search") ?? new StringTag())->getValue(), ($action->getInventory()->getItem(50)->getNamedTagEntry("SortType") ?? new IntTag())->getValue());
                    break;
                case 48:
                    $player->removeWindow($action->getInventory());
                    $form = new CustomForm(function (Player $player, ?array $data) use ($sort): void {
                        self::displayAuctionBrowser($player, 1, $data[0] ?? "", $sort);
                    });
                    $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.search.title"));
                    $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.search.search"));
                    $player->sendForm($form);
                    break;
                case 49:
                    self::displayMainMenu($player);
                    break;
                case 50:
                    self::displayPageAuctions($action->getInventory(), 1, $search, ($sort + 1) % 4);
                    break;
            }
            return false;
        });
        self::displayMenu($player, $menu);
    }

    /**
     * @return Auction[]
     */
    public static function displayPageAuctions(Inventory $inventory, int $page, string $search = "", int $sortType = MenuSort::TYPE_HIGHEST_BID): array
    {
        $inventory->clearAll(false);
        $activeAuctions = array_filter(PiggyAuctions::getInstance()->getAuctionManager()->getActiveAuctions(), function (Auction $auction) use ($search): bool {
            if (empty($search)) return true;
            return stripos($auction->getItem()->getName(), $search) !== false;
        });
        $displayedAuctions = self::updateDisplayedItems($inventory, $activeAuctions, ($page - 1) * self::PAGE_LENGTH, 0, self::PAGE_LENGTH, null, MenuSort::closureFromType($sortType));

        $searchItem = Item::get(Item::SIGN)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.search.search", ["{FILTERED}" => empty($search) ? "" : PiggyAuctions::getInstance()->getMessage("menus.search.filter", ["{FILTERED}" => $search])]));
        $searchItem->setNamedTagEntry(new StringTag("Search", $search));
        $inventory->setItem(48, $searchItem);

        $backArrow = Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back"));
        $backArrow->setNamedTagEntry(new IntTag("CurrentPage", $page));
        $inventory->setItem(49, $backArrow);

        $types = ["highest-bid", "lowest-bid", "ending-soon", "most-bids"];
        $sort = Item::get(Item::HOPPER)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.sorting.sort-type", ["{TYPES}" => implode("\n", array_map(function (string $type, int $index) use ($sortType): string {
            return ($index === $sortType ? PiggyAuctions::getInstance()->getMessage("menus.sorting.selected") : "") . PiggyAuctions::getInstance()->getMessage("menus.sorting." . $type);
        }, $types, array_keys($types)))]));
        $sort->setNamedTagEntry(new IntTag("SortType", $sortType));
        $inventory->setItem(50, $sort);

        if ($page > 1) {
            $previousPage = Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.previous-page", ["{PAGE}" => $page - 1, "{MAXPAGES}" => ceil(count($activeAuctions) / self::PAGE_LENGTH)]));
            $previousPage->setNamedTagEntry(new IntTag("Page", $page - 1));
            $inventory->setItem(45, $previousPage);
        }
        if ($page < ceil(count($activeAuctions) / self::PAGE_LENGTH)) {
            $nextPage = Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.next-page", ["{PAGE}" => $page + 1, "{MAXPAGES}" => ceil(count($activeAuctions) / self::PAGE_LENGTH)]));
            $nextPage->setNamedTagEntry(new IntTag("Page", $page + 1));
            $inventory->setItem(53, $nextPage);
        }
        return $displayedAuctions;
    }

    public static function displayBidsPage(Player $player): void
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.view-bids.title"));
        PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new InventoryClosureTask($player, $menu->getInventory(), function () use ($menu, $player): void {
            $auctions = array_filter(array_map(function (AuctionBid $bid): ?Auction {
                return $bid->getAuction();
            }, PiggyAuctions::getInstance()->getAuctionManager()->getBidsBy($player)), function (?Auction $auction) use ($player): bool {
                return $auction !== null && count($auction->getUnclaimedBidsHeldBy($player->getName())) > 0;
            });
            self::updateDisplayedItems($menu->getInventory(), $auctions, 0, 10, 7);
            $menu->getInventory()->setItem(22, Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
        }), 20);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            switch ($action->getSlot()) {
                case 22:
                    self::displayMainMenu($player);
                    break;
                default:
                    $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
                    if ($auction !== null) self::displayItemPage($player, $auction, function (Player $player) {
                        self::displayBidsPage($player);
                    });
                    break;
            }
            return false;
        });
        self::displayMenu($player, $menu);
    }

    public static function displayAuctionCreator(Player $player): void
    {
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.title"));
        for ($i = 0; $i < $menu->getInventory()->getSize(); $i++) $menu->getInventory()->setItem($i, Item::get(Item::BLEACH)->setCustomName(TextFormat::RESET));
        $menu->getInventory()->setItem(13, Item::get(Item::AIR));
        $menu->getInventory()->setItem(29, Item::get(Item::STAINED_CLAY, 14)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.create-auction", ["{STATUS}" => TextFormat::RED])));
        $menu->getInventory()->setItem(31, Item::get(Item::GOLD_INGOT)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.starting-bid", ["{MONEY}" => 50])));
        $menu->getInventory()->setItem(33, Item::get(Item::CLOCK)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.duration", ["{DURATION}" => Utils::formatDuration(7200)])));
        $menu->getInventory()->setItem(49, Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($menu): bool {
            switch ($action->getSlot()) {
                case 13:
                    $action->getInventory()->setItem(13, $itemClickedWith);
                    $action->getInventory()->setItem(29, $action->getInventory()->getItem(29)->setDamage($itemClickedWith->getId() === Item::AIR ? 14 : 13)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.create-auction", ["{STATUS}" => $itemClickedWith->getId() === Item::AIR ? TextFormat::RED : TextFormat::GREEN])));
                    return true;
                case 29:
                    if ($itemClicked->getDamage() === 13) {
                        $ev = new AuctionStartEvent($player, $action->getInventory()->getItem(13), time(), time() + (($tag = $action->getInventory()->getItem(33)->getNamedTagEntry("Duration")) ? $tag->getValue() : 7200), ($tag = $action->getInventory()->getItem(31)->getNamedTagEntry("StartingBid")) ? $tag->getValue() : 50);
                        $ev->call();
                        if (!$ev->isCancelled()) {
                            PiggyAuctions::getInstance()->getStatsManager()->getStatistics($player)->incrementStatistic("auctions_created");
                            PiggyAuctions::getInstance()->getAuctionManager()->addAuction(...$ev->getAuctionData());
                            $action->getInventory()->clear(13);
                            self::displayAuctionManager($player);
                        }
                    }
                    break;
                case 31:
                    $item = $action->getInventory()->getItem(13);
                    $action->getInventory()->clear(13);
                    $player->removeWindow($action->getInventory());
                    $form = new CustomForm(function (Player $player, ?array $data = null) use ($menu, $item): void {
                        $menu->getInventory()->setItem(13, $item);
                        if ($data !== null && is_numeric($data[0])) {
                            $item = $menu->getInventory()->getItem(31);
                            $item->setNamedTagEntry(new IntTag("StartingBid", (int)$data[0]));
                            $menu->getInventory()->setItem(31, $item->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.starting-bid", ["{MONEY}" => (int)$data[0]])));
                        }
                        $menu->send($player);
                    });
                    $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.create-auction.title"));
                    $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.create-auction.starting-bid"));
                    $player->sendForm($form);
                    break;
                case 33:
                    $item = $action->getInventory()->getItem(13);
                    $action->getInventory()->clear(13);
                    $player->removeWindow($action->getInventory());
                    $form = new CustomForm(function (Player $player, ?array $data = null) use ($menu, $item): void {
                        $menu->getInventory()->setItem(13, $item);
                        if ($data !== null && is_numeric($data[0])) {
                            $item = $menu->getInventory()->getItem(33);
                            $item->setNamedTagEntry(new IntTag("Duration", (int)$data[0]));
                            $menu->getInventory()->setItem(33, $item->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.duration", ["{DURATION}" => Utils::formatDuration((int)$data[0])])));
                        }
                        $menu->send($player);
                    });
                    $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.create-auction.title"));
                    $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.create-auction.duration"));
                    $player->sendForm($form);
                    break;
                case 49:
                    if (count(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($player)) < 1) {
                        self::displayMainMenu($player);
                        break;
                    }
                    self::displayAuctionManager($player);
                    break;
            }
            return false;
        });
        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory): void {
            $player->getInventory()->addItem($inventory->getItem(13));
        });
        self::displayMenu($player, $menu);
    }

    public static function displayAuctionManager(Player $player, int $sortType = MenuSort::TYPE_RECENTLY_UPDATED): void
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-manager.title"));
        $types = [
            MenuSort::TYPE_RECENTLY_UPDATED => "recently-updated",
            MenuSort::TYPE_HIGHEST_BID => "highest-bid",
            MenuSort::TYPE_MOST_BIDS => "most-bids"
        ];
        PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new InventoryClosureTask($player, $menu->getInventory(), function () use ($menu, $player, $sortType, $types): void {
            $auctions = array_filter(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($player), function (Auction $auction): bool {
                return !$auction->isClaimed();
            });
            $sortType = ($menu->getInventory()->getItem(23)->getNamedTagEntry("SortType") ?? new IntTag("SortType", $sortType))->getValue();
            $sort = $menu->getInventory()->getItem(23)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.sorting.sort-type", ["{TYPES}" => implode("\n", array_map(function (string $type, int $index) use ($sortType): string {
                return ($index === $sortType ? PiggyAuctions::getInstance()->getMessage("menus.sorting.selected") : "") . PiggyAuctions::getInstance()->getMessage("menus.sorting." . $type);
            }, $types, array_keys($types)))]));
            $sort->setNamedTagEntry(new IntTag("SortType", $sortType));
            $menu->getInventory()->setItem(23, $sort);
            self::updateDisplayedItems($menu->getInventory(), $auctions, 0, 10, 7, null, MenuSort::closureFromType($sortType));
        }), 20);
        $menu->getInventory()->setItem(22, Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
        $menu->getInventory()->setItem(23, Item::get(Item::HOPPER));
        $menu->getInventory()->setItem(24, Item::get(Item::GOLDEN_HORSE_ARMOR)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-manager.create-auction")));
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($types): bool {
            switch ($action->getSlot()) {
                case 22:
                    self::displayMainMenu($player);
                    break;
                case 23:
                    /** @var int $key */
                    $key = array_search(($itemClicked->getNamedTagEntry("SortType") ?? new IntTag())->getValue(), array_keys($types));
                    $itemClicked->setNamedTagEntry(new IntTag("SortType", array_keys($types)[($key + 1) % 3]));
                    $action->getInventory()->setItem(23, $itemClicked);
                    break;
                case 24:
                    self::displayAuctionCreator($player);
                    break;
                default:
                    $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
                    $sortType = ($action->getInventory()->getItem(23)->getNamedTagEntry("SortType") ?? new IntTag())->getValue();
                    if ($auction !== null) self::displayItemPage($player, $auction, function (Player $player) use ($sortType) {
                        self::displayAuctionManager($player, $sortType);
                    });
                    break;
            }
            return false;
        });
        self::displayMenu($player, $menu);
    }

    public static function displayAuctioneerPage(Player $player, string $auctioneer): void
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auctioneer-page.title", ["{PLAYER}" => $auctioneer]));
        PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new InventoryClosureTask($player, $menu->getInventory(), function () use ($menu, $auctioneer): void {
            $auctions = PiggyAuctions::getInstance()->getAuctionManager()->getActiveAuctionsHeldBy($auctioneer);
            if (isset(array_values($auctions)[0])) $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auctioneer-page.title", ["{PLAYER}" => array_values($auctions)[0]->getAuctioneer()]));
            self::updateDisplayedItems($menu->getInventory(), $auctions, 0, 10, 7);
        }), 20);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($auctioneer): bool {
            $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
            if ($auction !== null) self::displayItemPage($player, $auction, function (Player $player) use ($auctioneer) {
                self::displayAuctioneerPage($player, $auctioneer);
            });
            return false;
        });
        self::displayMenu($player, $menu);
    }

    public static function displayItemPage(Player $player, Auction $auction, callable $callback, ?int $bidAmount = null): void
    {
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.title"));
        PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new InventoryClosureTask($player, $menu->getInventory(), function () use ($menu, $auction): void {
            $map = Item::get(Item::MAP);
            $menu->getInventory()->setItem(13, self::getDisplayItem($auction));
            $menu->getInventory()->setItem(33, $map->setCustomName(count($auction->getBids()) === 0 ? PiggyAuctions::getInstance()->getMessage("menus.auction-view.no-bids") : PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-history", ["{BIDS}" => count($auction->getBids()), "{HISTORY}" => implode("\n", array_map(function (AuctionBid $auctionBid): string {
                return PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-history-entry", ["{MONEY}" => $auctionBid->getBidAmount(), "{PLAYER}" => $auctionBid->getBidder(), "{DURATION}" => Utils::formatDuration(time() - $auctionBid->getTimestamp())]);
            }, array_reverse($auction->getBids())))])));
        }), 20);
        $bidAmount = $bidAmount ?? ($auction->getTopBid() === null ? $auction->getStartingBid() : (int)($auction->getTopBid()->getBidAmount() * 1.15));
        $bidItem = Item::get(Item::POISONOUS_POTATO);
        if ($auction->hasExpired()) {
            $bidItem = Item::get(Item::GOLD_NUGGET);
            if ($auction->getAuctioneer() === $player->getName()) {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-item"));
                if (($overallTopBid = $auction->getTopBid()) !== null) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-money", ["{MONEY}" => $overallTopBid->getBidAmount(), "{PLAYER}" => $overallTopBid->getBidder()]));
            } elseif (($topBid = $auction->getTopBidBy($player->getName())) !== null) {
                /** @var AuctionBid $overallTopBid */
                $overallTopBid = $auction->getTopBid();
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-money", ["{BID}" => $topBid->getBidAmount(), "{TOPBID}" => $overallTopBid->getBidAmount(), "{TOPBIDDER}" => $overallTopBid->getBidder()]));
                if ($topBid === $auction->getTopBid()) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-item", ["{MONEY}" => $topBid->getBidAmount()]));
            } else {
                $bidItem = Item::get(Item::POTATO);
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.did-not-participate"));
            }
        } else if ($auction->getAuctioneer() === $player->getName()) {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.own-auction", ["{NEWBID}" => $bidAmount]));
        } else if (($topBid = $auction->getTopBidBy($player->getName())) === null) {
            if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($player) < $bidAmount) {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-first-cant-afford", ["{NEWBID}" => $bidAmount]));
            }
            $bidItem = Item::get(Item::GOLD_NUGGET);
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-first", ["{NEWBID}" => $bidAmount]));
        } else if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($player) < ($bidAmount - $topBid->getBidAmount())) {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-cant-afford", ["{NEWBID}" => $bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount(), "{DIFFERENCE}" => $bidAmount - $topBid->getBidAmount()]));
        } else if ($topBid === $auction->getTopBid()) {
            $bidItem = Item::get(Item::GOLD_BLOCK);
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.top-bid", ["{NEWBID}" => $bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount()]));
        } else {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit", ["{NEWBID}" => $bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount(), "{DIFFERENCE}" => $bidAmount - $topBid->getBidAmount()]));
        }
        $menu->getInventory()->setItem(29, $bidItem);
        if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($player) >= $bidAmount && !$auction->hasExpired() && $auction->getAuctioneer() !== $player->getName()) $menu->getInventory()->setItem(31, Item::get(Item::GOLD_INGOT)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-amount", ["{MONEY}" => $bidAmount])));
        $menu->getInventory()->setItem(49, Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($bidAmount, $auction, $callback): bool {
            switch ($action->getSlot()) {
                case 29:
                    if (!$auction->hasExpired()) {
                        if ($auction->getAuctioneer() === $player->getName()) {
                            $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.cant-self-bid"));
                            return false;
                        }
                        if ($auction->getTopBid() !== null && $auction->getTopBid()->getBidder() === $player->getName()) {
                            $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.already-top-bid"));
                            return false;
                        }
                        if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($player) < $bidAmount) {
                            $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.cant-afford"));
                            return false;
                        }
                        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
                        $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.title"));
                        $menu->getInventory()->setItem(11, Item::get(Item::STAINED_CLAY, 13)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.confirm", ["{ITEM}" => $auction->getItem()->getName(), "{MONEY}" => $bidAmount])));
                        $menu->getInventory()->setItem(13, (clone $auction->getItem())->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.bidding-on", ["{ITEM}" => ($auction->getItem())->getName()])));
                        $menu->getInventory()->setItem(15, Item::get(Item::STAINED_CLAY, 14)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.cancel")));
                        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($bidAmount, $auction, $callback): bool {
                            switch ($action->getSlot()) {
                                case 11:
                                    if ((($topBid = $auction->getTopBid()) === null && $bidAmount >= $auction->getStartingBid()) || ($topBid !== null && $bidAmount >= (int)($topBid->getBidAmount() * 1.15))) {
                                        if ($topBid === null || $topBid->getBidder() !== $player->getName()) {
                                            $cost = $bidAmount - ($auction->getTopBidBy($player->getName()) === null ? 0 : $auction->getTopBidBy($player->getName())->getBidAmount());
                                            if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($player) >= $cost) {
                                                $bid = new AuctionBid($auction->getId(), $player->getName(), $bidAmount, time());
                                                $ev = new AuctionBidEvent($auction, $bid);
                                                $ev->call();
                                                if (!$ev->isCancelled()) {
                                                    $cost = $ev->getBid()->getBidAmount() - ($auction->getTopBidBy($player->getName()) === null ? 0 : $auction->getTopBidBy($player->getName())->getBidAmount());
                                                    PiggyAuctions::getInstance()->getEconomyProvider()->takeMoney($player, $cost);
                                                    $auction->addBid($bid);

                                                    $stats = PiggyAuctions::getInstance()->getStatsManager()->getStatistics($player);
                                                    $stats->incrementStatistic("money_spent", $cost);
                                                    $stats->incrementStatistic("bids");
                                                    if ($stats->getStatistic("highest_bid") < $bid->getBidAmount()) {
                                                        $stats->setStatistic("highest_bid", $bid->getBidAmount());
                                                    }

                                                    $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.success", ["{MONEY}" => $ev->getBid()->getBidAmount(), "{ITEM}" => $auction->getItem()->getName()]));
                                                    if (($auctioneer = PiggyAuctions::getInstance()->getServer()->getPlayerExact($auction->getAuctioneer())) instanceof Player) $auctioneer->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.bidder", ["{PLAYER}" => $player->getName(), "{MONEY}" => $ev->getBid()->getBidAmount(), "{ITEM}" => $auction->getItem()->getName()]));
                                                }
                                            }
                                        }
                                    }
                                    self::displayItemPage($player, $auction, $callback, $bidAmount);
                                    break;
                                case 15:
                                    self::displayItemPage($player, $auction, $callback, $bidAmount);
                                    break;
                            }
                            return false;
                        });
                        self::displayMenu($player, $menu);
                    } else {
                        if ($auction->getAuctioneer() === $player->getName()) {
                            $auction->claim($player);
                            ($callback)($player);
                        } else if ($auction->getTopBidBy($player->getName())) {
                            $auction->bidderClaim($player);
                            ($callback)($player);
                        } else {
                            $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.claim.didnt-participate-error"));
                        }
                    }
                    break;
                case 31:
                    if ($itemClicked->getId() === Item::GOLD_INGOT) {
                        $player->removeWindow($action->getInventory());
                        $form = new CustomForm(function (Player $player, ?array $data = null) use ($bidAmount, $callback, $auction): void {
                            if ($data !== null && isset($data[0]) && is_numeric($data[0])) {
                                self::displayItemPage($player, $auction, $callback, (int)$data[0]);
                                return;
                            }
                            self::displayItemPage($player, $auction, $callback, $bidAmount);
                        });
                        $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.bid-amount.title"));
                        $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.bid-amount.bid-amount"));
                        $player->sendForm($form);
                    }
                    break;
                case 49:
                    ($callback)($player);
                    break;
            }
            return false;
        });
        self::displayMenu($player, $menu);
    }

    /**
     * @param Auction[] $auctions
     * @return Auction[]
     */
    public static function updateDisplayedItems(Inventory $inventory, array $auctions, int $arrayOffset, int $offsetSlot, int $displayCount, ?callable $itemIndexFunction = null, ?callable $sortFunction = null): array
    {
        $itemIndexFunction = $itemIndexFunction ?? function ($index) use ($offsetSlot): int {
                return $index + $offsetSlot;
            };
        $sortFunction = $sortFunction ?? function (Auction $a, Auction $b): bool {
                return $a->getEndDate() > $b->getEndDate();
            };
        uasort($auctions, $sortFunction);
        foreach (array_slice($auctions, $arrayOffset, $displayCount) as $index => $auction) {
            $inventory->setItem(($itemIndexFunction)($index), self::getDisplayItem($auction));
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