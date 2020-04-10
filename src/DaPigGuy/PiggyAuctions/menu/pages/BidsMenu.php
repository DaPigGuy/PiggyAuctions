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
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class BidsMenu extends Menu
{
    /** @var TaskHandler */
    private $taskHandler;

    public function __construct(Player $player)
    {
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $currentTick): void {
            $this->render();
        }), 20);
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->setName(PiggyAuctions::getInstance()->getMessage("menus.view-bids.title"));
        $this->getInventory()->clearAll(false);

        $auctions = array_filter(array_map(static function (AuctionBid $bid): ?Auction {
            return $bid->getAuction();
        }, PiggyAuctions::getInstance()->getAuctionManager()->getBidsBy($this->player)), function (?Auction $auction): bool {
            return $auction !== null && count($auction->getUnclaimedBidsHeldBy($this->player->getName())) > 0;
        });
        $claimable = array_filter($auctions, function (Auction $auction): bool {
            return $auction->hasExpired();
        });

        MenuUtils::updateDisplayedItems($this, $auctions, 0, 10, 7);
        if (count($claimable) > 1) $this->getInventory()->setItem(21, ItemFactory::get(ItemIds::CAULDRON)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.claim-all")));
        $this->getInventory()->setItem(22, ItemFactory::get(ItemIds::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
        $this->getInventory()->sendContents($this->player);
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        switch ($action->getSlot()) {
            case 21:
                foreach (PiggyAuctions::getInstance()->getAuctionManager()->getBidsBy($this->player) as $bid) {
                    $auction = $bid->getAuction();
                    if ($auction !== null && $auction->hasExpired()) {
                        $auction->bidderClaim($this->player);
                    }
                }
                $this->render();
                break;
            case 22:
                new MainMenu($this->player);
                break;
            default:
                $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
                if ($auction !== null) new AuctionMenu($this->player, $auction, function () {
                    new BidsMenu($this->player);
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