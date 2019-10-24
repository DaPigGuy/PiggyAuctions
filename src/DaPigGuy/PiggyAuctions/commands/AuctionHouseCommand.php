<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\commands;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use muqsit\invmenu\inventories\DoubleChestInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

/**
 * Class AuctionHouseCommand
 * @package DaPigGuy\PiggyAuctions\commands
 */
class AuctionHouseCommand extends BaseCommand
{
    const TF_RESET = TextFormat::RESET . TextFormat::GRAY;

    /** @var PiggyAuctions */
    private $plugin;

    /**
     * @param PiggyAuctions $plugin
     * @param string $name
     * @param string $description
     * @param string[] $aliases
     */
    public function __construct(PiggyAuctions $plugin, string $name, string $description = "", array $aliases = [])
    {
        $this->plugin = $plugin;
        parent::__construct($name, $description, $aliases);
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param BaseArgument[] $args
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Please use this in-game.");
            return;
        }

        $menu = InvMenu::create(DoubleChestInventory::class);
        $menu->setName("Auction House");
        $page = $args["page"] ?? 1;
        $pageAuctions = $this->displayPageAuctions($menu->getInventory(), $page);

        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($page, $pageAuctions): bool {
            if (isset($pageAuctions[$action->getSlot()])) {
                $auction = $pageAuctions[$action->getSlot()];
                //TODO: Show auction page
            }
            if ($itemClicked->getId() === Item::ARROW && $itemClicked->getNamedTagEntry("Page") !== null) {
                $pageAuctions = $this->displayPageAuctions($action->getInventory(), $itemClicked->getNamedTagEntry("Page")->getValue());
                $page = $itemClicked->getNamedTagEntry("Page")->getValue();
            }
            return false;
        });
        $menu->send($sender);
    }

    /**
     * @param Inventory $inventory
     * @param int $page
     * @return Auction[]
     */
    public function displayPageAuctions(Inventory $inventory, int $page): array
    {
        $inventory->clearAll(false);

        $activeAuctions = $this->plugin->getAuctionManager()->getActiveAuctions();
        /** @var Auction $auction */
        foreach (array_slice($activeAuctions, ($page - 1) * 45, 45) as $slot => $auction) {
            $item = clone $auction->getItem();

            $lore = array_merge($item->getLore(), [
                "",
                self::TF_RESET . "Seller: " . $auction->getAuctioneer(),
                self::TF_RESET . "Bids: " . TextFormat::GREEN . count($auction->getBids()),
            ]);
            if ($auction->getTopBid() !== null) $lore = array_merge($lore, [
                "",
                self::TF_RESET . "Top Bid: " . TextFormat::GOLD . $auction->getTopBid()->getBidAmount(),
                self::TF_RESET . "Bidder: " . TextFormat::GOLD . $auction->getTopBid()->getBidder()
            ]);

            $item->setNamedTagEntry(new IntTag("AuctionID", $auction->getId()));
            $inventory->setItem($slot, $item->setLore($lore), false);
        }
        if ($page > 1) {
            $previousPage = Item::get(Item::ARROW, 0, 1)->setCustomName("Previous Page\n(" . ($page - 1) . "/" . ceil(count($activeAuctions) / 45) . ")");
            $previousPage->setNamedTagEntry(new IntTag("Page", $page - 1));
            $inventory->setItem(45, $previousPage);
        }
        if ($page < ceil(count($activeAuctions) / 45)) {
            $nextPage = Item::get(Item::ARROW, 0, 1)->setCustomName("Next Page\n(" . ($page + 1) . "/" . ceil(count($activeAuctions) / 45) . ")");
            $nextPage->setNamedTagEntry(new IntTag("Page", $page + 1));
            $inventory->setItem(53, $nextPage);
        }
        return array_slice($activeAuctions, ($page - 1) * 45, 45);
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("page", true));
        //TODO: Permissions & Subcommands
    }
}