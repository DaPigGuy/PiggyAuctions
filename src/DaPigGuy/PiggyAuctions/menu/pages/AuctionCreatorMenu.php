<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\events\AuctionStartEvent;
use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use DaPigGuy\PiggyAuctions\utils\Utils;
use jojoe77777\FormAPI\CustomForm;
use muqsit\invmenu\InvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AuctionCreatorMenu extends Menu
{
    /** @var string */
    protected $inventoryIdentifier = InvMenu::TYPE_DOUBLE_CHEST;
    /** @var Item|null */
    private $item;
    /** @var int */
    private $startingBid = 50;
    /** @var int */
    private $duration = 7200;

    public function __construct(Player $player)
    {
        $this->item = ItemFactory::get(ItemIds::AIR);
        parent::__construct($player);
    }

    public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        switch ($action->getSlot()) {
            case 13:
                $this->item = $itemClickedWith;
                $this->render();
                return true;
            case 29:
                if ($this->item->getId() !== ItemIds::AIR) {
                    $this->menu->getInventory()->clear(13);
                    $ev = new AuctionStartEvent($player, $this->item, time(), time() + $this->duration, $this->startingBid);
                    $ev->call();
                    if (!$ev->isCancelled()) {
                        PiggyAuctions::getInstance()->getStatsManager()->getStatistics($player)->incrementStatistic("auctions_created");
                        PiggyAuctions::getInstance()->getAuctionManager()->addAuction(...$ev->getAuctionData());
                        new AuctionManagerMenu($player);
                    }
                }
                break;
            case 31:
                $this->menu->setInventoryCloseListener(null);
                $player->removeWindow($action->getInventory());
                $this->menu->setInventoryCloseListener([$this, "close"]);
                $form = new CustomForm(function (Player $player, ?array $data = null): void {
                    if ($data !== null && is_numeric($data[0])) {
                        $this->startingBid = (int)$data[0];
                    }
                    $this->render();
                    $this->display();
                });
                $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.create-auction.title"));
                $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.create-auction.starting-bid"));
                $player->sendForm($form);
                break;
            case 33:
                $this->menu->setInventoryCloseListener(null);
                $player->removeWindow($action->getInventory());
                $this->menu->setInventoryCloseListener([$this, "close"]);
                $form = new CustomForm(function (Player $player, ?array $data = null): void {
                    if ($data !== null && is_numeric($data[0])) {
                        $this->duration = (int)$data[0];
                    }
                    $this->render();
                    $this->display();
                });
                $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.create-auction.title"));
                $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.create-auction.duration"));
                $player->sendForm($form);
                break;
            case 49:
                if (count(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($player)) < 1) {
                    new MainMenu($player);
                    break;
                }
                new AuctionManagerMenu($player);
                break;
        }
        return false;
    }

    public function render(): void
    {
        $this->menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.title"));
        for ($i = 0; $i < $this->menu->getInventory()->getSize(); $i++) $this->menu->getInventory()->setItem($i, Item::get(Item::BLEACH)->setCustomName(TextFormat::RESET));
        $this->menu->getInventory()->setItem(13, $this->item);
        $this->menu->getInventory()->setItem(29, Item::get(Item::STAINED_CLAY, $this->item->getId() === Item::AIR ? 14 : 13)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.create-auction", ["{STATUS}" => $this->item->getId() === Item::AIR ? TextFormat::RED : TextFormat::GREEN])));
        $this->menu->getInventory()->setItem(31, Item::get(Item::GOLD_INGOT)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.starting-bid", ["{MONEY}" => $this->startingBid])));
        $this->menu->getInventory()->setItem(33, Item::get(Item::CLOCK)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.duration", ["{DURATION}" => Utils::formatDuration($this->duration)])));
        $this->menu->getInventory()->setItem(49, Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
    }

    public function close(): void
    {
        $this->player->getInventory()->addItem($this->menu->getInventory()->getItem(13));
        $this->item = null;
        parent::close();
    }
}