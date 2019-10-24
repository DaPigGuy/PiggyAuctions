<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\commands;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\BaseCommand;
use DaPigGuy\PiggyAuctions\auction\Auction;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use muqsit\invmenu\inventories\DoubleChestInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\command\CommandSender;
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
        $page = 1;
        $pageAuctions = array_slice($this->plugin->getAuctionManager()->getActiveAuctions(), ($page - 1) * 45, 45);

        $menu = InvMenu::create(DoubleChestInventory::class);
        $menu->setName("Auction House");
        /** @var Auction $auction */
        foreach ($pageAuctions as $auction) {
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
            $menu->getInventory()->addItem($item->setLore($lore));
        }
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($page, $pageAuctions): bool {
            if (isset($pageAuctions[$action->getSlot()])) {
                $auction = $pageAuctions[$action->getSlot()];
                //TODO: Show auction page
            }
            //TODO: Page arrows
            return false;
        });
        $menu->send($sender);
    }

    protected function prepare(): void
    {
        //TODO: Permissions & Subcommands
    }
}