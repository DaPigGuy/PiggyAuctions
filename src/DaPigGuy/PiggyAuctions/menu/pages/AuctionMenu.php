<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\auction\AuctionBid;
use DaPigGuy\PiggyAuctions\events\AuctionBidEvent;
use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\menu\utils\MenuUtils;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use DaPigGuy\PiggyAuctions\utils\Utils;
use jojoe77777\FormAPI\CustomForm;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\session\PlayerManager;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class AuctionMenu extends Menu
{
    /** @var string */
    protected $inventoryIdentifier = InvMenu::TYPE_DOUBLE_CHEST;
    /** @var Auction */
    private $auction;
    /** @var int */
    private $bidAmount;
    /** @var callable */
    private $callback;
    /** @var TaskHandler|null */
    private $taskHandler;

    public function __construct(Player $player, Auction $auction, callable $callback)
    {
        $this->auction = $auction;
        $this->bidAmount = $auction->getTopBid() === null ? $auction->getStartingBid() : (int)($auction->getTopBid()->getBidAmount() * 1.15);
        $this->callback = $callback;
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $currentTick): void {
            $this->render();
        }), 20);
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->menu->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.title"));

        $this->menu->getInventory()->setItem(13, MenuUtils::getDisplayItem($this->auction));
        $this->menu->getInventory()->setItem(33, Item::get(Item::MAP)->setCustomName(count($this->auction->getBids()) === 0 ? PiggyAuctions::getInstance()->getMessage("menus.auction-view.no-bids") : PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-history", ["{BIDS}" => count($this->auction->getBids()), "{HISTORY}" => implode("\n", array_map(function (AuctionBid $auctionBid): string {
            return PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-history-entry", ["{MONEY}" => $auctionBid->getBidAmount(), "{PLAYER}" => $auctionBid->getBidder(), "{DURATION}" => Utils::formatDuration(time() - $auctionBid->getTimestamp())]);
        }, array_reverse($this->auction->getBids())))])));

        $bidItem = Item::get(Item::POISONOUS_POTATO);
        if ($this->auction->hasExpired()) {
            $bidItem = Item::get(Item::GOLD_NUGGET);
            if ($this->auction->getAuctioneer() === $this->player->getName()) {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-item"));
                if (($overallTopBid = $this->auction->getTopBid()) !== null) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-money", ["{MONEY}" => $overallTopBid->getBidAmount(), "{PLAYER}" => $overallTopBid->getBidder()]));
            } elseif (($topBid = $this->auction->getTopBidBy($this->player->getName())) !== null) {
                /** @var AuctionBid $overallTopBid */
                $overallTopBid = $this->auction->getTopBid();
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-money", ["{BID}" => $topBid->getBidAmount(), "{TOPBID}" => $overallTopBid->getBidAmount(), "{TOPBIDDER}" => $overallTopBid->getBidder()]));
                if ($topBid === $this->auction->getTopBid()) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-item", ["{MONEY}" => $topBid->getBidAmount()]));
            } else {
                $bidItem = Item::get(Item::POTATO);
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.did-not-participate"));
            }
        } else if ($this->auction->getAuctioneer() === $this->player->getName()) {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.own-auction", ["{NEWBID}" => $this->bidAmount]));
        } else if (($topBid = $this->auction->getTopBidBy($this->player->getName())) === null) {
            if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player) < $this->bidAmount) {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-first-cant-afford", ["{NEWBID}" => $this->bidAmount]));
            }
            $bidItem = Item::get(Item::GOLD_NUGGET);
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-first", ["{NEWBID}" => $this->bidAmount]));
        } else if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player) < ($this->bidAmount - $topBid->getBidAmount())) {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-cant-afford", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount(), "{DIFFERENCE}" => $this->bidAmount - $topBid->getBidAmount()]));
        } else if ($topBid === $this->auction->getTopBid()) {
            $bidItem = Item::get(Item::GOLD_BLOCK);
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.top-bid", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount()]));
        } else {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount(), "{DIFFERENCE}" => $this->bidAmount - $topBid->getBidAmount()]));
        }
        $this->menu->getInventory()->setItem(29, $bidItem);
        if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player) >= $this->bidAmount && !$this->auction->hasExpired() && $this->auction->getAuctioneer() !== $this->player->getName()) $this->menu->getInventory()->setItem(31, Item::get(Item::GOLD_INGOT)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-amount", ["{MONEY}" => $this->bidAmount])));
        $this->menu->getInventory()->setItem(49, Item::get(Item::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
    }

    public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        switch ($action->getSlot()) {
            case 29:
                if (!$this->auction->hasExpired()) {
                    if ($this->auction->getAuctioneer() === $player->getName()) {
                        $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.cant-self-bid"));
                        return false;
                    }
                    if ($this->auction->getTopBid() !== null && $this->auction->getTopBid()->getBidder() === $player->getName()) {
                        $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.already-top-bid"));
                        return false;
                    }
                    if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($player) < $this->bidAmount) {
                        $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.cant-afford"));
                        return false;
                    }
                    $menu = InvMenu::create(InvMenu::TYPE_CHEST);
                    $menu->setName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.title"));
                    $menu->getInventory()->setItem(11, Item::get(Item::STAINED_CLAY, 13)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.confirm", ["{ITEM}" => $this->auction->getItem()->getName(), "{MONEY}" => $this->bidAmount])));
                    $menu->getInventory()->setItem(13, (clone $this->auction->getItem())->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.bidding-on", ["{ITEM}" => ($this->auction->getItem())->getName()])));
                    $menu->getInventory()->setItem(15, Item::get(Item::STAINED_CLAY, 14)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.cancel")));
                    $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
                        switch ($action->getSlot()) {
                            case 11:
                                if ((($topBid = $this->auction->getTopBid()) === null && $this->bidAmount >= $this->auction->getStartingBid()) || ($topBid !== null && $this->bidAmount >= (int)($topBid->getBidAmount() * 1.15))) {
                                    if ($topBid === null || $topBid->getBidder() !== $player->getName()) {
                                        $cost = $this->bidAmount - ($this->auction->getTopBidBy($player->getName()) === null ? 0 : $this->auction->getTopBidBy($player->getName())->getBidAmount());
                                        if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($player) >= $cost) {
                                            $bid = new AuctionBid($this->auction->getId(), $player->getName(), $this->bidAmount, time());
                                            $ev = new AuctionBidEvent($this->auction, $bid);
                                            $ev->call();
                                            if (!$ev->isCancelled()) {
                                                $cost = $ev->getBid()->getBidAmount() - ($this->auction->getTopBidBy($player->getName()) === null ? 0 : $this->auction->getTopBidBy($player->getName())->getBidAmount());
                                                PiggyAuctions::getInstance()->getEconomyProvider()->takeMoney($player, $cost);
                                                $this->auction->addBid($bid);

                                                $stats = PiggyAuctions::getInstance()->getStatsManager()->getStatistics($player);
                                                $stats->incrementStatistic("money_spent", $cost);
                                                $stats->incrementStatistic("bids");
                                                if ($stats->getStatistic("highest_bid") < $bid->getBidAmount()) {
                                                    $stats->setStatistic("highest_bid", $bid->getBidAmount());
                                                }

                                                $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.success", ["{MONEY}" => $ev->getBid()->getBidAmount(), "{ITEM}" => $this->auction->getItem()->getName()]));
                                                if (($auctioneer = PiggyAuctions::getInstance()->getServer()->getPlayerExact($this->auction->getAuctioneer())) instanceof Player) $auctioneer->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.bidder", ["{PLAYER}" => $player->getName(), "{MONEY}" => $ev->getBid()->getBidAmount(), "{ITEM}" => $this->auction->getItem()->getName()]));
                                            }
                                        }
                                    }
                                }
                                break;
                        }
                        $this->render();
                        $this->display();
                        return false;
                    });
                    if (PlayerManager::get($this->player) !== null) {
                        $oldMenu = PlayerManager::get($this->player)->getCurrentMenu();
                        if ($oldMenu !== null) {
                            $this->player->removeWindow($oldMenu->getInventoryForPlayer($this->player));
                            Menu::$awaitingInventoryClose[$this->player->getName()] = $this->menu;
                        } else {
                            $this->menu->send($this->player);
                        }
                    }
                } else {
                    if ($this->auction->getAuctioneer() === $player->getName()) {
                        $this->auction->claim($player);
                        ($this->callback)();
                    } else if ($this->auction->getTopBidBy($player->getName())) {
                        $this->auction->bidderClaim($player);
                        ($this->callback)();
                    } else {
                        $player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.claim.didnt-participate-error"));
                    }
                }
                break;
            case 31:
                if ($itemClicked->getId() === Item::GOLD_INGOT) {
                    $player->removeWindow($action->getInventory());
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null && isset($data[0]) && is_numeric($data[0])) {
                            $this->bidAmount = (int)$data[0];
                            return;
                        }
                        $this->render();
                        $this->display();
                    });
                    $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.bid-amount.title"));
                    $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.bid-amount.bid-amount"));
                    $player->sendForm($form);
                }
                break;
            case 49:
                ($this->callback)();
                break;
        }
        return false;
    }

    public function close(): void
    {
        parent::close();
        $this->taskHandler->cancel();
        $this->taskHandler = null;
    }
}