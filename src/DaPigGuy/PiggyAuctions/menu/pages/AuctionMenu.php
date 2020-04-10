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
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
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
    /** @var TaskHandler */
    private $taskHandler;

    public function __construct(Player $player, Auction $auction, callable $callback)
    {
        $this->auction = $auction;
        $this->bidAmount = $auction->getMinimumBidAmount();
        $this->callback = $callback;
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $currentTick): void {
            $this->render();
        }), 20);
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.title"));

        $this->getInventory()->setItem(13, MenuUtils::getDisplayItem($this->auction));
        $this->getInventory()->setItem(33, ItemFactory::get(ItemIds::MAP)->setCustomName(count($this->auction->getBids()) === 0 ? PiggyAuctions::getInstance()->getMessage("menus.auction-view.no-bids") : PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-history", ["{BIDS}" => count($this->auction->getBids()), "{HISTORY}" => implode("\n", array_map(static function (AuctionBid $auctionBid): string {
            return PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-history-entry", ["{MONEY}" => $auctionBid->getBidAmount(), "{PLAYER}" => $auctionBid->getBidder(), "{DURATION}" => Utils::formatDuration(time() - $auctionBid->getTimestamp())]);
        }, array_reverse($this->auction->getBids())))])));

        $bidItem = ItemFactory::get(ItemIds::POISONOUS_POTATO);
        if ($this->auction->hasExpired()) {
            $bidItem = ItemFactory::get(ItemIds::GOLD_NUGGET);
            if ($this->auction->getAuctioneer() === $this->player->getName()) {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-item"));
                if (($overallTopBid = $this->auction->getTopBid()) !== null) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-money", ["{MONEY}" => $overallTopBid->getBidAmount(), "{PLAYER}" => $overallTopBid->getBidder()]));
            } elseif (($topBid = $this->auction->getTopBidBy($this->player->getName())) !== null) {
                /** @var AuctionBid $overallTopBid */
                $overallTopBid = $this->auction->getTopBid();
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-money", ["{BID}" => $topBid->getBidAmount(), "{TOPBID}" => $overallTopBid->getBidAmount(), "{TOPBIDDER}" => $overallTopBid->getBidder()]));
                if ($topBid === $this->auction->getTopBid()) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-item", ["{MONEY}" => $topBid->getBidAmount()]));
            } else {
                $bidItem = ItemFactory::get(ItemIds::POTATO);
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.did-not-participate"));
            }
        } else if ($this->auction->getAuctioneer() === $this->player->getName()) {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.own-auction", ["{NEWBID}" => $this->bidAmount]));
        } else if (($topBid = $this->auction->getTopBidBy($this->player->getName())) === null) {
            $bidItem = ItemFactory::get(ItemIds::GOLD_NUGGET);
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-first", ["{NEWBID}" => $this->bidAmount]));
            if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player) < $this->bidAmount) {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-first-cant-afford", ["{NEWBID}" => $this->bidAmount]));
            }
        } else if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player) < ($this->bidAmount - $topBid->getBidAmount())) {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-cant-afford", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount(), "{DIFFERENCE}" => $this->bidAmount - $topBid->getBidAmount()]));
        } else if ($topBid === $this->auction->getTopBid()) {
            $bidItem = ItemFactory::get(ItemIds::GOLD_BLOCK);
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.top-bid", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount()]));
        } else {
            $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount(), "{DIFFERENCE}" => $this->bidAmount - $topBid->getBidAmount()]));
        }
        $this->getInventory()->setItem(29, $bidItem);
        if (!$this->auction->hasExpired() && $this->auction->getAuctioneer() !== $this->player->getName()) $this->getInventory()->setItem(31, ItemFactory::get(ItemIds::GOLD_INGOT)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-amount", ["{MONEY}" => $this->bidAmount])));
        $this->getInventory()->setItem(49, ItemFactory::get(ItemIds::ARROW)->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
        $this->getInventory()->sendContents($this->player);
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        switch ($action->getSlot()) {
            case 29:
                if (!$this->auction->hasExpired()) {
                    if ($this->auction->getAuctioneer() === $this->player->getName()) {
                        $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.cant-self-bid"));
                        return false;
                    }
                    if ($this->auction->getTopBid() !== null && $this->auction->getTopBid()->getBidder() === $this->player->getName()) {
                        $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.already-top-bid"));
                        return false;
                    }
                    if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player) < $this->bidAmount) {
                        $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.cant-afford"));
                        return false;
                    }
                    $this->setInventoryCloseListener(null);
                    new ConfirmationMenu(
                        $this->player,
                        PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.title"),
                        (clone $this->auction->getItem())->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.bidding-on", ["{ITEM}" => ($this->auction->getItem())->getName()])),
                        PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.confirm", ["{ITEM}" => $this->auction->getItem()->getName(), "{MONEY}" => $this->bidAmount]),
                        PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.cancel"),
                        function (bool $confirmed) {
                            if ($confirmed) {
                                if ($this->bidAmount >= $this->auction->getMinimumBidAmount()) {
                                    if (($topBid = $this->auction->getTopBid()) === null || $topBid->getBidder() !== $this->player->getName()) {
                                        $cost = $this->bidAmount - ($this->auction->getTopBidBy($this->player->getName()) === null ? 0 : $this->auction->getTopBidBy($this->player->getName())->getBidAmount());
                                        if (PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player) >= $cost) {
                                            $bid = new AuctionBid($this->auction->getId(), $this->player->getName(), $this->bidAmount, time());
                                            $ev = new AuctionBidEvent($this->auction, $bid);
                                            $ev->call();
                                            if (!$ev->isCancelled()) {
                                                $cost = $ev->getBid()->getBidAmount() - ($this->auction->getTopBidBy($this->player->getName()) === null ? 0 : $this->auction->getTopBidBy($this->player->getName())->getBidAmount());
                                                PiggyAuctions::getInstance()->getEconomyProvider()->takeMoney($this->player, $cost);
                                                $this->auction->addBid($bid);

                                                $stats = PiggyAuctions::getInstance()->getStatsManager()->getStatistics($this->player);
                                                $stats->incrementStatistic("money_spent", $cost);
                                                $stats->incrementStatistic("bids");
                                                if ($stats->getStatistic("highest_bid") < $bid->getBidAmount()) {
                                                    $stats->setStatistic("highest_bid", $bid->getBidAmount());
                                                }

                                                $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.success", ["{MONEY}" => $ev->getBid()->getBidAmount(), "{ITEM}" => $this->auction->getItem()->getName()]));
                                                if (($auctioneer = PiggyAuctions::getInstance()->getServer()->getPlayerExact($this->auction->getAuctioneer())) instanceof Player) $auctioneer->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.bidder", ["{PLAYER}" => $this->player->getName(), "{MONEY}" => $ev->getBid()->getBidAmount(), "{ITEM}" => $this->auction->getItem()->getName()]));
                                            }
                                        }
                                    }
                                }
                            }
                            $this->setInventoryCloseListener([$this, "close"]);
                            $this->render();
                            $this->display();
                        }
                    );
                } else {
                    if ($this->auction->getAuctioneer() === $this->player->getName()) {
                        $this->auction->claim($this->player);
                        ($this->callback)();
                    } else if ($this->auction->getTopBidBy($this->player->getName())) {
                        $this->auction->bidderClaim($this->player);
                        ($this->callback)();
                    } else {
                        $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.claim.didnt-participate-error"));
                    }
                }
                break;
            case 31:
                if ($itemClicked->getId() === Item::GOLD_INGOT) {
                    $this->setInventoryCloseListener(null);
                    $this->player->removeWindow($action->getInventory());
                    $this->setInventoryCloseListener([$this, "close"]);
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if (isset($data[0]) && is_numeric($data[0]) && (int)$data[0] > 0) {
                            if ((int)$data[0] > ($minimumBid = $this->auction->getMinimumBidAmount())) {
                                $this->bidAmount = (int)$data[0] > ($limit = PiggyAuctions::getInstance()->getConfig()->getNested("auctions.limits.bid", 2147483647)) ? $limit : (int)$data[0];
                            } else {
                                $player->sendMessage(PiggyAuctions::getInstance()->getMessage("forms.bid-amount.bid-too-low", ["{MINIMUMBID}" => $minimumBid]));
                            }
                        }
                        $this->render();
                        $this->display();
                    });
                    $form->setTitle(PiggyAuctions::getInstance()->getMessage("forms.bid-amount.title"));
                    $form->addInput(PiggyAuctions::getInstance()->getMessage("forms.bid-amount.bid-amount"));
                    $this->player->sendForm($form);
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
    }
}