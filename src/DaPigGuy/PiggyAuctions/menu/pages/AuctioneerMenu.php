<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\menu\utils\MenuUtils;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class AuctioneerMenu extends Menu
{
    /** @var string */
    private $auctioneer;

    /** @var TaskHandler */
    private $taskHandler;

    public function __construct(Player $player, string $auctioneer)
    {
        $this->auctioneer = $auctioneer;
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $currentTick): void {
            $this->render();
        }), 20);
        parent::__construct($player);
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        $auctioneer = $this->auctioneer;
        $auction = PiggyAuctions::getInstance()->getAuctionManager()->getAuction(($itemClicked->getNamedTagEntry("AuctionID") ?? new IntTag())->getValue());
        if ($auction !== null) new AuctionMenu($this->player, $auction, function () use ($auctioneer) {
            new AuctioneerMenu($this->player, $auctioneer);
        });
        return false;
    }

    public function render(): void
    {
        $this->setName(PiggyAuctions::getInstance()->getMessage("menus.auctioneer-page.title", ["{PLAYER}" => $this->auctioneer]));
        $this->getInventory()->clearAll(false);
        $auctions = PiggyAuctions::getInstance()->getAuctionManager()->getActiveAuctionsHeldBy($this->auctioneer);
        if (isset(array_values($auctions)[0])) $this->setName(PiggyAuctions::getInstance()->getMessage("menus.auctioneer-page.title", ["{PLAYER}" => array_values($auctions)[0]->getAuctioneer()]));
        MenuUtils::updateDisplayedItems($this, $auctions, 0, 10, 7);
        $this->getInventory()->sendContents($this->player);
    }

    public function close(): void
    {
        parent::close();
        $this->taskHandler->cancel();
    }
}