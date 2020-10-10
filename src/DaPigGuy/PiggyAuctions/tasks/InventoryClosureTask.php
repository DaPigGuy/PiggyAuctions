<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\tasks;

use Closure;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class InventoryClosureTask extends ClosureTask
{
    /** @var Player */
    private $player;
    /** @var Inventory */
    private $inventory;
    /** @var Closure */
    protected $closure;

    /** @var bool */
    private $inventoryOpen = false;

    public function __construct(Player $player, Inventory $inventory, Closure $closure)
    {
        parent::__construct($closure);
        $this->player = $player;
        $this->inventory = $inventory;
    }

    public function onRun(): void
    {
        parent::onRun();
        if ($this->inventoryOpen && $this->player->getNetworkSession()->getInvManager()->getWindowId($this->inventory) === ContainerIds::NONE) {
            if (($handler = $this->getHandler()) !== null) $handler->cancel();
            return;
        }
        $this->inventoryOpen = $this->player->getNetworkSession()->getInvManager()->getWindowId($this->inventory) !== ContainerIds::NONE;
    }
}