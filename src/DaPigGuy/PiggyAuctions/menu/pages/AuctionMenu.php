<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\pages;

use Closure;
use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\auction\AuctionBid;
use DaPigGuy\PiggyAuctions\events\AuctionBidEvent;
use DaPigGuy\PiggyAuctions\menu\Menu;
use DaPigGuy\PiggyAuctions\menu\utils\MenuUtils;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use DaPigGuy\PiggyAuctions\utils\Utils;
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

class AuctionMenu extends Menu
{
    protected string $inventoryIdentifier = InvMenuTypeIds::TYPE_DOUBLE_CHEST;
    private int $bidAmount;
    private TaskHandler $taskHandler;

    /**
     * @param callable $callback
     */
    public function __construct(Player $player, private Auction $auction, private $callback)
    {
        $this->bidAmount = $auction->getMinimumBidAmount();
        $this->taskHandler = PiggyAuctions::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->render();
        }), 20);
        parent::__construct($player);
    }

    public function render(): void
    {
        $this->setName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.title"));

        $this->getInventory()->setItem(13, MenuUtils::getDisplayItem($this->auction));
        $this->getInventory()->setItem(33, ItemFactory::getInstance()->get(ItemIds::MAP)->setCustomName(count($this->auction->getBids()) === 0 ? PiggyAuctions::getInstance()->getMessage("menus.auction-view.no-bids") : PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-history", ["{BIDS}" => count($this->auction->getBids()), "{HISTORY}" => implode("\n", array_map(static function (AuctionBid $auctionBid): string {
            return PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-history-entry", ["{MONEY}" => $auctionBid->getBidAmount(), "{PLAYER}" => $auctionBid->getBidder(), "{DURATION}" => Utils::formatDuration(time() - $auctionBid->getTimestamp())]);
        }, array_reverse($this->auction->getBids())))])));

        PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player, function (float|int $balance) {
            $bidItem = VanillaItems::POISONOUS_POTATO();
            if ($this->auction->hasExpired()) {
                $bidItem = VanillaItems::GOLD_NUGGET();
                if ($this->auction->getAuctioneer() === $this->player->getName()) {
                    $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-item"));
                    if (($overallTopBid = $this->auction->getTopBid()) !== null) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.auctioneer-money", ["{MONEY}" => $overallTopBid->getBidAmount(), "{PLAYER}" => $overallTopBid->getBidder()]));
                } else if (($topBid = $this->auction->getTopBidBy($this->player->getName())) !== null) {
                    /** @var AuctionBid $overallTopBid */
                    $overallTopBid = $this->auction->getTopBid();
                    $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-money", ["{BID}" => $topBid->getBidAmount(), "{TOPBID}" => $overallTopBid->getBidAmount(), "{TOPBIDDER}" => $overallTopBid->getBidder()]));
                    if ($topBid === $this->auction->getTopBid()) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.bidder-item", ["{MONEY}" => $topBid->getBidAmount()]));
                } else {
                    $bidItem = VanillaItems::POTATO();
                    $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("auction.claim.did-not-participate"));
                }
            } else if ($this->auction->getAuctioneer() === $this->player->getName()) {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.own-auction", ["{NEWBID}" => $this->bidAmount]));
            } else if (($topBid = $this->auction->getTopBidBy($this->player->getName())) === null) {
                $bidItem = VanillaItems::GOLD_NUGGET();
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-first", ["{NEWBID}" => $this->bidAmount]));
                if ($balance < $this->bidAmount) $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-first-cant-afford", ["{NEWBID}" => $this->bidAmount]));
            } else if ($balance < ($this->bidAmount - $topBid->getBidAmount())) {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit-cant-afford", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount(), "{DIFFERENCE}" => $this->bidAmount - $topBid->getBidAmount()]));
            } else if ($topBid === $this->auction->getTopBid()) {
                $bidItem = VanillaBlocks::GOLD()->asItem();
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.top-bid", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount()]));
            } else {
                $bidItem->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bidding.submit", ["{NEWBID}" => $this->bidAmount, "{PREVIOUSBID}" => $topBid->getBidAmount(), "{DIFFERENCE}" => $this->bidAmount - $topBid->getBidAmount()]));
            }
            $this->getInventory()->setItem(29, $bidItem);
            if (!$this->auction->hasExpired() && $this->auction->getAuctioneer() !== $this->player->getName()) $this->getInventory()->setItem(31, VanillaItems::GOLD_INGOT()->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.auction-view.bid-amount", ["{MONEY}" => $this->bidAmount])));
            $this->getInventory()->setItem(49, VanillaItems::ARROW()->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.back")));
            $this->player->getNetworkSession()->getInvManager()?->syncContents($this->getInventory());
        });
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action, InvMenuTransaction $transaction): InvMenuTransactionResult
    {
        switch ($action->getSlot()) {
            case 29:
                if (!$this->auction->hasExpired()) {
                    if ($this->auction->getAuctioneer() === $this->player->getName()) {
                        $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.cant-self-bid"));
                        return $transaction->discard();
                    }
                    if ($this->auction->getTopBid() !== null && $this->auction->getTopBid()->getBidder() === $this->player->getName()) {
                        $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.already-top-bid"));
                        return $transaction->discard();
                    }
                    PiggyAuctions::getInstance()->getEconomyProvider()->getMoney($this->player, function (float|int $balance) use ($transaction) {
                        if ($balance < $this->bidAmount) {
                            $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.cant-afford"));
                            return $transaction->discard();
                        }
                        $this->setInventoryCloseListener(null);
                        (new ConfirmationMenu(
                            $this->player,
                            PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.title"),
                            (clone $this->auction->getItem())->setCustomName(PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.bidding-on", ["{ITEM}" => ($this->auction->getItem())->getName()])),
                            PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.confirm", ["{ITEM}" => $this->auction->getItem()->getName(), "{MONEY}" => $this->bidAmount]),
                            PiggyAuctions::getInstance()->getMessage("menus.bid-confirmation.cancel"),
                            function (bool $confirmed) use ($balance): void {
                                if ($confirmed) {
                                    if ($this->bidAmount >= $this->auction->getMinimumBidAmount()) {
                                        if (($topBid = $this->auction->getTopBid()) === null || $topBid->getBidder() !== $this->player->getName()) {
                                            $cost = $this->bidAmount - ($this->auction->getTopBidBy($this->player->getName()) === null ? 0 : $this->auction->getTopBidBy($this->player->getName())->getBidAmount());
                                            if ($balance >= $cost) {
                                                $bid = new AuctionBid($this->auction->getId(), $this->player->getName(), $this->bidAmount, time());
                                                $ev = new AuctionBidEvent($this->auction, $bid);
                                                $ev->call();
                                                if (!$ev->isCancelled()) {
                                                    $cost = $ev->getBid()->getBidAmount() - ($this->auction->getTopBidBy($this->player->getName()) === null ? 0 : $this->auction->getTopBidBy($this->player->getName())->getBidAmount());
                                                    PiggyAuctions::getInstance()->getEconomyProvider()->takeMoney($this->player, $cost, function (bool $success) use ($ev, $bid, $cost) {
                                                        if (!$success) {
                                                            $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("generic-error"));
                                                            return;
                                                        }

                                                        $this->auction->addBid($bid);

                                                        if ($this->auction->getEndDate() - time() <= ($duration = PiggyAuctions::getInstance()->getConfig()->getNested("auctions.anti-snipe-duration", 120))) {
                                                            $this->auction->setEndDate($this->auction->getEndDate() + $duration - ($this->auction->getEndDate() - time()));
                                                        }

                                                        $stats = PiggyAuctions::getInstance()->getStatsManager()->getStatistics($this->player);
                                                        $stats->incrementStatistic("money_spent", $cost);
                                                        $stats->incrementStatistic("bids");
                                                        if ($stats->getStatistic("highest_bid") < $bid->getBidAmount()) $stats->setStatistic("highest_bid", $bid->getBidAmount());

                                                        $this->player->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.success", ["{MONEY}" => $ev->getBid()->getBidAmount(), "{ITEM}" => $this->auction->getItem()->getName()]));
                                                        if (($auctioneer = PiggyAuctions::getInstance()->getServer()->getPlayerExact($this->auction->getAuctioneer())) instanceof Player) {
                                                            $auctioneer->sendMessage(PiggyAuctions::getInstance()->getMessage("auction.bid.bidder", ["{PLAYER}" => $this->player->getName(), "{MONEY}" => $ev->getBid()->getBidAmount(), "{ITEM}" => $this->auction->getItem()->getName()]));
                                                        }
                                                    });
                                                }
                                            }
                                        }
                                    }
                                }
                                $this->setInventoryCloseListener(Closure::fromCallable([$this, "close"]));
                                $this->render();
                                $this->display();
                            }
                        ))->display();
                        return $transaction;
                    });
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
                if ($itemClicked->getId() === ItemIds::GOLD_INGOT) {
                    $this->setInventoryCloseListener(null);
                    $this->onClose($this->player);
                    $this->setInventoryCloseListener(Closure::fromCallable([$this, "close"]));
                    return $transaction->discard()->then(function (): void {
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
                    });
                }
                break;
            case 49:
                ($this->callback)();
                break;
        }
        return $transaction->discard();
    }

    public function close(): void
    {
        parent::close();
        $this->taskHandler->cancel();
    }
}