<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\menu\utils\MenuSort;
use DaPigGuy\PiggyAuctions\menu\utils\MenuUtils;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use jojoe77777\FormAPI\CustomForm;
use muqsit\invmenu\InvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class AuctionBrowserMenu extends Menu
{
    const PAGE_LENGTH = self::PAGE_ROW_LENGTH * 4;
    const PAGE_ROW_LENGTH = 7;

    /** @var string */
    protected $inventoryIdentifier = InvMenu::TYPE_DOUBLE_CHEST;
    /** @var int */
    private $page;
    /** @var string */
    private $search;
    /** @var int */
    private $sortType;
    /** @var TaskHandler */
    private $taskHandler;

    public function __construct(Player $player, int $page = 1, string $search = "", int $sortType = MenuSort::TYPE_HIGHEST_BID)
    {
        $this->page = $page;
        $this->search = $search;
        $this->sortType = $sortType;
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->render();
        }), 20);
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.title"));
        $this->menu->getInventory()->clearAll(false);

        $activeAuctions = array_filter(PiggyAuctions::getInstance()->getAuctionManager()->getActiveAuctions(), function (Auction $auction): bool {
            if (empty($this->search)) return true;
            return stripos($auction->getItem()->getName(), $this->search) !== false;
        });
        MenuUtils::updateDisplayedItems($this->menu, $activeAuctions, ($this->page - 1) * self::PAGE_LENGTH, 0, self::PAGE_LENGTH, function (int $index): int {
            return (int)($index + 10 + floor($index / self::PAGE_ROW_LENGTH) * 2);
        }, MenuSort::closureFromType($this->sortType));

        $searchItem = Item::get(Item::SIGN)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.search.search", ["{FILTERED}" => empty($search) ? "" : PiggyAuctions::getInstance()->getMessage("menus.search.filter", ["{FILTERED}" => $search])]));
        $this->menu->getInventory()->setItem(48, $searchItem);

        $backArrow = Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back"));
        $this->menu->getInventory()->setItem(49, $backArrow);

        $types = ["highest-bid", "lowest-bid", "ending-soon", "most-bids"];
        $sort = Item::get(Item::HOPPER)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.sorting.sort-type", ["{TYPES}" => implode("\n", array_map(function (string $type, int $index): string {
            return ($index === $this->sortType ? PiggyAuctions::getInstance()->getMessage("menus.sorting.selected") : "") . PiggyAuctions::getInstance()->getMessage("menus.sorting." . $type);
        }, $types, array_keys($types)))]));
        $this->menu->getInventory()->setItem(50, $sort);

        if ($this->page > 1) {
            $previousPage = Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.previous-page", ["{PAGE}" => $this->page - 1, "{MAXPAGES}" => ceil(count($activeAuctions) / self::PAGE_LENGTH)]));
            $this->menu->getInventory()->setItem(45, $previousPage);
        }
        if ($this->page < ceil(count($activeAuctions) / self::PAGE_LENGTH)) {
            $nextPage = Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.next-page", ["{PAGE}" => $this->page + 1, "{MAXPAGES}" => ceil(count($activeAuctions) / self::PAGE_LENGTH)]));
            $this->menu->getInventory()->setItem(53, $nextPage);
        }
    }

    public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        if ($itemClicked->getNamedTagEntry("AuctionID") !== null) {
            $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
            if ($auction instanceof Auction) {
                $browserAttributes = [$player, $this->page, $this->search, $this->sortType];
                new AuctionMenu($player, $auction, function () use ($browserAttributes) {
                    new AuctionBrowserMenu(...$browserAttributes);
                });
            }
        }
        switch ($action->getSlot()) {
            case 45:
            case 53:
                $this->page += $action->getSlot() === 45 ? -1 : 1;
                $this->render();
                break;
            case 48:
                $this->menu->setInventoryCloseListener(null);
                $player->removeWindow($action->getInventory());
                $this->menu->setInventoryCloseListener([$this, "close"]);
                $form = new CustomForm(function (Player $player, ?array $data): void {
                    $this->search = $data[0] ?? "";
                    $this->render();
                    $this->display();
                });
                $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.search.title"));
                $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.search.search"));
                $player->sendForm($form);
                break;
            case 49:
                new MainMenu($player);
                break;
            case 50:
                $this->sortType = ($this->sortType + 1) % 4;
                $this->render();
                break;
        }
        return false;
    }

    public function close(): void
    {
        parent::close();
        $this->taskHandler->cancel();
    }
}