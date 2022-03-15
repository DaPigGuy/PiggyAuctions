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
    private bool $inventoryOpen = false;

    public function __construct(private Player $player, private Inventory $inventory, Closure $closure)
    {
        parent::__construct($closure);
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