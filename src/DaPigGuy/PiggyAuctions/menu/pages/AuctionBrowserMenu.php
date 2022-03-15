<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use Closure;
use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\menu\utils\MenuSort;
use DaPigGuy\PiggyAuctions\menu\utils\MenuUtils;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use jojoe77777\FormAPI\CustomForm;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class AuctionBrowserMenu extends Menu
{
    const PAGE_LENGTH = self::PAGE_ROW_LENGTH * 4;
    const PAGE_ROW_LENGTH = 7;

    protected string $inventoryIdentifier = InvMenuTypeIds::TYPE_DOUBLE_CHEST;
    private TaskHandler $taskHandler;

    public function __construct(Player $player, private int $page = 1, private string $search = "", private int $sortType = MenuSort::TYPE_HIGHEST_BID)
    {
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->render();
        }), 20);
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.title"));
        $this->getInventory()->clearAll();

        $activeAuctions = array_filter(PiggyAuctions::getInstance()->getAuctionManager()->getActiveAuctions(), function (Auction $auction): bool {
            if (empty($this->search)) return true;
            return stripos($auction->getItem()->getName(), $this->search) !== false;
        });
        MenuUtils::updateDisplayedItems($this, $activeAuctions, ($this->page - 1) * self::PAGE_LENGTH, 0, self::PAGE_LENGTH, static function (int $index): int {
            return (int)($index + 10 + floor($index / self::PAGE_ROW_LENGTH) * 2);
        }, MenuSort::closureFromType($this->sortType));

        $searchItem = VanillaBlocks::OAK_SIGN()->asItem()->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.search.search", ["{FILTERED}" => empty($this->search) ? "" : PiggyAuctions::getInstance()->getMessage("menus.search.filter", ["{FILTERED}" => $this->search])]));
        $this->getInventory()->setItem(48, $searchItem);

        $backArrow = VanillaItems::ARROW()->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back"));
        $this->getInventory()->setItem(49, $backArrow);

        $types = ["highest-bid", "lowest-bid", "ending-soon", "most-bids"];
        $sort = ItemFactory::getInstance()->get(ItemIds::HOPPER)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.sorting.sort-type", ["{TYPES}" => implode("\n", array_map(function (string $type, int $index): string {
            return ($index === $this->sortType ? PiggyAuctions::getInstance()->getMessage("menus.sorting.selected") : "") . PiggyAuctions::getInstance()->getMessage("menus.sorting." . $type);
        }, $types, array_keys($types)))]));
        $this->getInventory()->setItem(50, $sort);

        if ($this->page > 1) {
            $previousPage = VanillaItems::ARROW()->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.previous-page", ["{PAGE}" => $this->page - 1, "{MAXPAGES}" => ceil(count($activeAuctions) / self::PAGE_LENGTH)]));
            $this->getInventory()->setItem(45, $previousPage);
        }
        if ($this->page < ceil(count($activeAuctions) / self::PAGE_LENGTH)) {
            $nextPage = VanillaItems::ARROW()->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-browser.next-page", ["{PAGE}" => $this->page + 1, "{MAXPAGES}" => ceil(count($activeAuctions) / self::PAGE_LENGTH)]));
            $this->getInventory()->setItem(53, $nextPage);
        }
        $this->player->getNetworkSession()->getInvManager()?->syncContents($this->getInventory());
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action, InvMenuTransaction $transaction): InvMenuTransactionResult
    {
        $newMenu = null;
        if (($auctionTag = $itemClicked->getNamedTag()->getTag("AuctionID")) !== null) {
            $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction($auctionTag->getValue());
            if ($auction instanceof Auction) {
                $newMenu = new AuctionMenu($this->player, $auction, function () {
                    (new AuctionBrowserMenu($this->player, $this->page, $this->search, $this->sortType))->display();
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
                $this->setInventoryCloseListener(null);
                $this->onClose($this->player);
                $this->setInventoryCloseListener(Closure::fromCallable([$this, "close"]));
                return $transaction->discard()->then(function (): void {
                    $form = new CustomForm(function (Player $player, ?array $data): void {
                        $this->search = $data[0] ?? "";
                        $this->render();
                        $this->display();
                    });
                    $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.search.title"));
                    $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.search.search"));
                    $this->player->sendForm($form);
                });
            case 49:
                $newMenu = new MainMenu($this->player);
                break;
            case 50:
                $this->sortType = ($this->sortType + 1) % 4;
                $this->render();
                break;
        }
        if ($newMenu === null) return $transaction->discard();
        return $transaction->discard()->then(function () use ($newMenu): void {
            $newMenu->display();
        });
    }

    public function close(): void
    {
        parent::close();
        $this->taskHandler->cancel();
    }
}