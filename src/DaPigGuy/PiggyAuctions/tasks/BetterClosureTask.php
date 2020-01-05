<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\tasks;

use pocketmine\scheduler\Task;
use pocketmine\utils\Utils;

/**
 * Class BetterClosureTask
 * @package DaPigGuy\PiggyAuctions\tasks
 */
class BetterClosureTask extends Task
{
    /** @var \Closure */
    protected $closure;

    /**
     * BetterClosureTask constructor.
     * @param \Closure $closure
     */
    public function __construct(\Closure $closure)
    {
        Utils::validateCallableSignature(function (int $currentTick): bool {
            return false; //STFU PHPStorm
        }, $closure);
        $this->closure = $closure;
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    public function getName(): string
    {
        return Utils::getNiceClosureName($this->closure);
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        $shouldContinue = ($this->closure)($currentTick);
        if (!$shouldContinue) {
            $this->getHandler()->cancel();
        }
    }
}