<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyAuctions\menu\pages\AuctioneerMenu;
use DaPigGuy\PiggyAuctions\menu\pages\MainMenu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class AuctionHouseCommand extends BaseCommand
{
    /** @var PiggyAuctions */
    protected $plugin;

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Please use this in-game.");
            return;
        }
        if (isset($args["player"])) {
            if (!is_string($args["player"])) return; //Shut PHPStorm up
            if (count($this->plugin->getAuctionManager()->getActiveAuctionsHeldBy($args["player"])) < 1) {
                $sender->sendMessage(PiggyAuctions::getInstance()->getMessage("commands.no-active-auctions", ["{PLAYER}" => $args["player"]]));
                return;
            }
            (new AuctioneerMenu($sender, $args["player"]))->display();
            return;
        }
        (new MainMenu($sender))->display();
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermission("piggyauctions.command.auctionhouse");
        $this->registerArgument(0, new RawStringArgument("player", true));
    }
}