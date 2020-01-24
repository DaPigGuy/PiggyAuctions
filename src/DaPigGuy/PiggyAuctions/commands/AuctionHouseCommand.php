<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\commands;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use DaPigGuy\PiggyAuctions\utils\MenuUtils;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

/**
 * Class AuctionHouseCommand
 * @package DaPigGuy\PiggyAuctions\commands
 */
class AuctionHouseCommand extends BaseCommand
{
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
        if (isset($args["player"])) {
            if (!is_string($args["player"])) return; //Shut PHPStorm up
            if (count($this->plugin->getAuctionManager()->getActiveAuctionsHeldBy($args["player"])) < 1) {
                $sender->sendMessage(PiggyAuctions::getInstance()->getMessage("commands.no-active-auctions", ["{PLAYER}" => $args["player"]]));
                return;
            }
            MenuUtils::displayAuctioneerPage($sender, $args["player"]);
            return;
        }
        MenuUtils::displayMainMenu($sender);
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