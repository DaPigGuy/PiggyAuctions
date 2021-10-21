<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use DaPigGuy\libPiggyEconomy\exceptions\MissingProviderDependencyException;
use DaPigGuy\libPiggyEconomy\exceptions\UnknownProviderException;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use DaPigGuy\PiggyAuctions\auction\AuctionManager;
use DaPigGuy\PiggyAuctions\commands\AuctionHouseCommand;
use DaPigGuy\PiggyAuctions\statistics\StatisticsManager;
use DaPigGuy\PiggyAuctions\tasks\CheckUpdatesTask;
use DaPigGuy\PiggyAuctions\utils\Utils;
use jojoe77777\FormAPI\Form;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

class PiggyAuctions extends PluginBase implements Listener
{
    /** @var self */
    public static $instance;

    /** @var Config */
    private $messages;

    /** @var DataConnector */
    private $database;
    /** @var EconomyProvider */
    private $economyProvider;
    /** @var AuctionManager */
    private $auctionManager;
    /** @var StatisticsManager */
    private $statsManager;

    /**
     * @throws MissingProviderDependencyException
     * @throws UnknownProviderException
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        foreach (
            [
                "libasynql" => libasynql::class,
                "libPiggyEconomy" => libPiggyEconomy::class,
                "InvMenu" => InvMenuHandler::class,
                "Commando" => BaseCommand::class,
                "libformapi" => Form::class
            ] as $virion => $class
        ) {
            if (!class_exists($class)) {
                $this->getLogger()->error($virion . " virion not found. Please download PiggyAuctions from Poggit-CI or use DEVirion (not recommended).");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }

        if ($this->getServer()->getPluginManager()->getPlugin("InvCrashFix") === null) {
            $this->getLogger()->error("Missing InvCrashFix plugin. Menus may not work as intended. Download: https://poggit.pmmp.io/r/94956/InvCrashFix_dev-3.phar");
        }

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        self::$instance = $this;

        $this->saveResource("messages.yml");
        $this->messages = new Config($this->getDataFolder() . "messages.yml");
        $this->saveDefaultConfig();
        $this->database = libasynql::create($this, $this->getConfig()->get("database"), [
            "sqlite" => "sqlite.sql",
            "mysql" => "mysql.sql"
        ]);

        libPiggyEconomy::init();
        $this->economyProvider = libPiggyEconomy::getProvider($this->getConfig()->get("economy"));

        $this->auctionManager = new AuctionManager($this);
        $this->auctionManager->init();

        $this->statsManager = new StatisticsManager($this);

        if (!PacketHooker::isRegistered()) PacketHooker::register($this);
        $this->getServer()->getCommandMap()->register("piggyauctions", new AuctionHouseCommand($this, "auctionhouse", "Open the auction house", ["ah"]));

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getAsyncPool()->submitTask(new CheckUpdatesTask());
    }

    public static function getInstance(): PiggyAuctions
    {
        return self::$instance;
    }

    public function getMessage(string $key, array $tags = []): string
    {
        return Utils::translateColorTags(str_replace(array_keys($tags), $tags, $this->messages->getNested($key, $key)));
    }

    public function getDatabase(): DataConnector
    {
        return $this->database;
    }

    public function getEconomyProvider(): EconomyProvider
    {
        return $this->economyProvider;
    }

    public function getAuctionManager(): AuctionManager
    {
        return $this->auctionManager;
    }

    public function getStatsManager(): StatisticsManager
    {
        return $this->statsManager;
    }
}
