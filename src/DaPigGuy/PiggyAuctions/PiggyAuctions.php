<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions;

use DaPigGuy\libPiggyEconomy\exceptions\MissingProviderDependencyException;
use DaPigGuy\libPiggyEconomy\exceptions\UnknownProviderException;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use DaPigGuy\PiggyAuctions\auction\AuctionManager;
use DaPigGuy\PiggyAuctions\commands\AuctionHouseCommand;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

/**
 * Class PiggyAuctions
 * @package DaPigGuy\PiggyAuctions
 */
class PiggyAuctions extends PluginBase
{
    /** @var self */
    public static $instance;

    /** @var DataConnector */
    private $database;
    /** @var EconomyProvider */
    private $economyProvider;
    /** @var AuctionManager */
    private $auctionManager;

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

        $this->saveDefaultConfig();
        $this->database = libasynql::create($this, $this->getConfig()->get("database"), [
            "sqlite" => "sqlite.sql",
            "mysql" => "mysql.sql"
        ]);

        libPiggyEconomy::init();
        $this->economyProvider = libPiggyEconomy::getProvider($this->getConfig()->get("economy"));

        $this->auctionManager = new AuctionManager($this);
        $this->auctionManager->init();

        for ($i = 0; $i < 25; $i++) {
            $this->auctionManager->addAuction("Aericio", Item::get(Item::PORKCHOP, 0, mt_rand(1, 64))->setCustomName("Pig"), time(), time() + mt_rand(60, 6000), mt_rand(50, 500));
        }

        $this->getServer()->getCommandMap()->register("piggyauctions", new AuctionHouseCommand($this, "auctionhouse", "Open the auction house", ["ah"]));
    }

    /**
     * @return PiggyAuctions
     */
    public static function getInstance(): PiggyAuctions
    {
        return self::$instance;
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
}