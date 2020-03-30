<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\auction\AuctionBid;
use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\menu\utils\MenuUtils;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class BidsMenu extends Menu
{
    /** @var TaskHandler */
    private $taskHandler;

    public function __construct(Player $player)
    {
        parent::__construct($player);
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $currentTick): void {
            $this->render();
        }), 20);
    }

    public function render(): void
    {
        $this->menu->setName(PiggyAuctions::getInstance()->getMessage("menus.view-bids.title"));
        $auctions = array_filter(array_map(function (AuctionBid $bid): ?Auction {
            return $bid->getAuction();
        }, PiggyAuctions::getInstance()->getAuctionManager()->getBidsBy($this->player)), function (?Auction $auction): bool {
            return $auction !== null && count($auction->getUnclaimedBidsHeldBy($this->player->getName())) > 0;
        });
        MenuUtils::updateDisplayedItems($this->menu, $auctions, 0, 10, 7);
        $this->menu->getInventory()->setItem(22, Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
    }

    public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        switch ($action->getSlot()) {
            case 22:
                new MainMenu($player);
                break;
            default:
                $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
                if ($auction !== null) new AuctionMenu($player, $auction, function () use ($player) {
                    new BidsMenu($player);
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