<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\menu\utils\MenuSort;
use DaPigGuy\PiggyAuctions\menu\utils\MenuUtils;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class AuctionManagerMenu extends Menu
{
    const SORT_TYPES = [
        MenuSort::TYPE_RECENTLY_UPDATED => "recently-updated",
        MenuSort::TYPE_HIGHEST_BID => "highest-bid",
        MenuSort::TYPE_MOST_BIDS => "most-bids"
    ];

    /** @var int */
    private $auctionLimit = -1;
    /** @var int */
    private $sortType;

    /** @var TaskHandler */
    private $taskHandler;

    public function __construct(Player $player, int $sortType = MenuSort::TYPE_RECENTLY_UPDATED)
    {
        foreach ($player->getEffectivePermissions() as $permission) {
            $basePermission = "piggyauctions.limits.";
            if (substr($permission->getPermission(), 0, strlen($basePermission)) === $basePermission) {
                $possibleLimit = substr($permission->getPermission(), strlen($basePermission));
                if (is_numeric($possibleLimit)) {
                    if ((int)$possibleLimit > $this->auctionLimit) $this->auctionLimit = (int)$possibleLimit;
                }
            }
        }

        $this->sortType = $sortType;
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->render();
        }), 20);
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-manager.title"));
        $this->getInventory()->clearAll(false);

        $auctions = array_filter(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($this->player), static function (Auction $auction): bool {
            return !$auction->isClaimed();
        });

        $this->getInventory()->setItem(22, ItemFactory::get(ItemIds::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
        $this->getInventory()->setItem(24, ItemFactory::get(ItemIds::GOLDEN_HORSE_ARMOR)->setCustomName(PiggyAuctions::getInstance()->getMessage($this->auctionLimit !== -1 && count($auctions) > $this->auctionLimit ? "menus.auction-manager.create-auction-maxed" : "menus.auction-manager.create-auction")));

        $sort = ItemFactory::get(ItemIds::HOPPER)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.sorting.sort-type", ["{TYPES}" => implode("\n", array_map(function (string $type, int $index): string {
            return ($index === $this->sortType ? PiggyAuctions::getInstance()->getMessage("menus.sorting.selected") : "") . PiggyAuctions::getInstance()->getMessage("menus.sorting." . $type);
        }, self::SORT_TYPES, array_keys(self::SORT_TYPES)))]));
        $this->getInventory()->setItem(23, $sort);

        MenuUtils::updateDisplayedItems($this, $auctions, 0, 10, 7, null, MenuSort::closureFromType($this->sortType));
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        switch ($action->getSlot()) {
            case 22:
                new MainMenu($this->player);
                break;
            case 23:
                /** @var int $key */
                $key = array_search($this->sortType, array_keys(self::SORT_TYPES));
                $this->sortType = array_keys(self::SORT_TYPES)[($key + 1) % 3];
                $this->render();
                break;
            case 24:
                if ($this->auctionLimit !== -1 && count(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($this->player)) >= $this->auctionLimit) break;
                new AuctionCreatorMenu($this->player);
                break;
            default:
                $managerAttributes = [$this->player, $this->sortType];
                $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
                if ($auction !== null) new AuctionMenu($this->player, $auction, static function () use ($managerAttributes) {
                    new AuctionManagerMenu(...$managerAttributes);
                });
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