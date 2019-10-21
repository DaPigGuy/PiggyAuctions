<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions;

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
    /** @var DataConnector */
    private $database;

    /** @var AuctionManager */
    private $auctionManager;

    public function onEnable(): void
    {
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        $this->saveDefaultConfig();
        $this->database = libasynql::create($this, $this->getConfig()->get("database"), [
            "sqlite" => "sqlite.sql", //TODO: Add SQLite3 prepared statement file
            "mysql" => "mysql.sql"
        ]);

        $this->auctionManager = new AuctionManager($this);
        $this->auctionManager->init();

        $this->auctionManager->addAuction("Aericio", Item::get(Item::PORKCHOP, 0, 1)->setCustomName("Pig"), time() + 500);

        $this->getServer()->getCommandMap()->register("piggyauctions", new AuctionHouseCommand($this, "auctionhouse", "Open the auction house", ["ah"]));
    }

    /**
     * @return DataConnector
     */
    public function getDatabase(): DataConnector
    {
        return $this->database;
    }

    /**
     * @return AuctionManager
     */
    public function getAuctionManager(): AuctionManager
    {
        return $this->auctionManager;
    }
}