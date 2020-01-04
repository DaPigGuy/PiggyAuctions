<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\commands;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\BaseCommand;
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
        MenuUtils::displayMainMenu($sender);
    }

    protected function prepare(): void
    {
        //TODO: Permissions & Subcommands
    }
}