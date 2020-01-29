<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\tasks;

use Closure;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

/**
 * Class InventoryClosureTask
 * @package DaPigGuy\PiggyAuctions\tasks
 */
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

    /**
     * InventoryClosureTask constructor.
     * @param Player $player
     * @param Inventory $inventory
     * @param Closure $closure
     */
    public function __construct(Player $player, Inventory $inventory, Closure $closure)
    {
        parent::__construct($closure);
        $this->player = $player;
        $this->inventory = $inventory;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick): void
    {
        parent::onRun($currentTick);
        if ($this->inventoryOpen && $this->player->getWindowId($this->inventory) === ContainerIds::NONE) {
            if (($handler = $this->getHandler()) !== null) $handler->cancel();
            return;
        }
        $this->inventoryOpen = $this->player->getWindowId($this->inventory) !== ContainerIds::NONE;
    }
}