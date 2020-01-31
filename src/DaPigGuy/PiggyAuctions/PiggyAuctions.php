<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions;

use DaPigGuy\libPiggyEconomy\exceptions\MissingProviderDependencyException;
use DaPigGuy\libPiggyEconomy\exceptions\UnknownProviderException;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use DaPigGuy\PiggyAuctions\auction\AuctionManager;
use DaPigGuy\PiggyAuctions\commands\AuctionHouseCommand;
use DaPigGuy\PiggyAuctions\statistics\StatisticsManager;
use DaPigGuy\PiggyAuctions\utils\Utils;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

/**
 * Class PiggyAuctions
 * @package DaPigGuy\PiggyAuctions
 */
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
     */
    public function onEnable(): void
    {
        self::$instance = $this;

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

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

        $this->getServer()->getCommandMap()->register("piggyauctions", new AuctionHouseCommand($this, "auctionhouse", "Open the auction house", ["ah"]));

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    /**
     * @return PiggyAuctions
     */
    public static function getInstance(): PiggyAuctions
    {
        return self::$instance;
    }

    /**
     * @param string $key
     * @param array $tags
     * @return string
     */
    public function getMessage(string $key, array $tags = []): string
    {
        return Utils::translateColorTags(str_replace(array_keys($tags), $tags, $this->messages->getNested($key, $key)));
    }

    /**
     * @return DataConnector
     */
    public function getDatabase(): DataConnector
    {
        return $this->database;
    }

    /**
     * @return EconomyProvider
     */
    public function getEconomyProvider(): EconomyProvider
    {
        return $this->economyProvider;
    }

    /**
     * @return AuctionManager
     */
    public function getAuctionManager(): AuctionManager
    {
        return $this->auctionManager;
    }

    /**
     * @return StatisticsManager
     */
    public function getStatsManager(): StatisticsManager
    {
        return $this->statsManager;
    }
}