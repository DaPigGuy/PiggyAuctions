<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\commands;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyAuctions\menu\pages\AuctioneerMenu;
use DaPigGuy\PiggyAuctions\menu\pages\MainMenu;
use DaPigGuy\PiggyAuctions\PiggyAuctions;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class AuctionHouseCommand extends BaseCommand implements PluginIdentifiableCommand
{
    /** @var PiggyAuctions */
    private $plugin;

    /**
     * @param string[] $aliases
     */
    public function __construct(PiggyAuctions $plugin, string $name, string $description = "", array $aliases = [])
    {
        $this->plugin = $plugin;
        parent::__construct($name, $description, $aliases);
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    /**
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
            new AuctioneerMenu($sender, $args["player"]);
            return;
        }
        new MainMenu($sender);
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