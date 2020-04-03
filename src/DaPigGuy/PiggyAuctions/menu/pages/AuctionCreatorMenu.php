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
    /** @var Item */
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

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        switch ($action->getSlot()) {
            case 13:
                $this->item = $itemClickedWith;
                $this->render();
                return true;
            case 29:
                if ($this->item->getId() !== ItemIds::AIR) {
                    $this->getInventory()->clear(13);
                    $ev = new AuctionStartEvent($this->player, $this->item, time(), time() + $this->duration, $this->startingBid);
                    $ev->call();
                    if (!$ev->isCancelled()) {
                        PiggyAuctions::getInstance()->getStatsManager()->getStatistics($this->player)->incrementStatistic("auctions_created");
                        PiggyAuctions::getInstance()->getAuctionManager()->addAuction(...$ev->getAuctionData());
                        new AuctionManagerMenu($this->player);
                    }
                }
                break;
            case 31:
                $this->setInventoryCloseListener(null);
                $this->player->removeWindow($action->getInventory());
                $this->setInventoryCloseListener([$this, "close"]);
                $form = new CustomForm(function (Player $player, ?array $data = null): void {
                    if (isset($data[0]) && is_numeric($data[0]) && (int)$data[0] > 0) {
                        $this->startingBid = (int)$data[0] > ($limit = PiggyAuctions::getInstance()->getConfig()->getNested("auctions.starting-bid-limit", 2147483647)) ? $limit : (int)$data[0];
                    }
                    $this->render();
                    $this->display();
                });
                $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.create-auction.title"));
                $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.create-auction.starting-bid"));
                $this->player->sendForm($form);
                break;
            case 33:
                $this->setInventoryCloseListener(null);
                $this->player->removeWindow($action->getInventory());
                $this->setInventoryCloseListener([$this, "close"]);
                $form = new CustomForm(function (Player $player, ?array $data = null): void {
                    if (isset($data[0]) && is_numeric($data[0]) && (int)$data[0] > 0) {
                        $this->duration = (int)$data[0] > ($limit = PiggyAuctions::getInstance()->getConfig()->getNested("auctions.duration-limit", 1209600)) ? $limit : (int)$data[0];
                    }
                    $this->render();
                    $this->display();
                });
                $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.create-auction.title"));
                $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.create-auction.duration"));
                $this->player->sendForm($form);
                break;
            case 49:
                if (count(PiggyAuctions::getInstance()->getAuctionManager()->getAuctionsHeldBy($this->player)) < 1) {
                    new MainMenu($this->player);
                    break;
                }
                new AuctionManagerMenu($this->player);
                break;
        }
        return false;
    }

    public function render(): void
    {
        $this->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.title"));
        for ($i = 0; $i < $this->getInventory()->getSize(); $i++) $this->getInventory()->setItem($i, ItemFactory::get(ItemIds::INVISIBLE_BEDROCK)->setCustomName(TextFormat::RESET));
        $this->getInventory()->setItem(13, $this->item);
        $this->getInventory()->setItem(29, ItemFactory::get(ItemIds::STAINED_CLAY, $this->item->getId() === Item::AIR ? 14 : 13)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.create-auction", ["{STATUS}" => $this->item->getId() === Item::AIR ? TextFormat::RED : TextFormat::GREEN])));
        $this->getInventory()->setItem(31, ItemFactory::get(ItemIds::GOLD_INGOT)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.starting-bid", ["{MONEY}" => $this->startingBid])));
        $this->getInventory()->setItem(33, ItemFactory::get(ItemIds::CLOCK)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-creator.duration", ["{DURATION}" => Utils::formatDuration($this->duration)])));
        $this->getInventory()->setItem(49, ItemFactory::get(ItemIds::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
    }

    public function close(): void
    {
        $this->player->getInventory()->addItem($this->getInventory()->getItem(13));
        parent::close();
    }
}