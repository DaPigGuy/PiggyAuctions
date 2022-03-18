<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu\utils;

use Closure;
use DaPigGuy\PiggyAuctions\auction\Auction;

class MenuSort
{
    const TYPE_HIGHEST_BID = 0;
    const TYPE_LOWEST_BID = 1;
    const TYPE_ENDING_SOON = 2;
    const TYPE_MOST_BIDS = 3;
    const TYPE_RECENTLY_UPDATED = 5;

    /** @var Closure[] */
    private static array $closures;

    public static function closureFromType(int $type): Closure
    {
        if (!isset(self::$closures)) {
            self::$closures = [
                self::TYPE_LOWEST_BID => self::sortFunction(fn(Auction $a) => $a->getTopBid()?->getBidAmount() ?? $a->getStartingBid()),
                self::TYPE_HIGHEST_BID => self::sortFunction(fn(Auction $a) => $a->getTopBid()?->getBidAmount() ?? $a->getStartingBid(), true),
                self::TYPE_ENDING_SOON => self::sortFunction(fn(Auction $a) => $a->getEndDate()),
                self::TYPE_MOST_BIDS => self::sortFunction(fn(Auction $a) => count($a->getBids()), true),
                self::TYPE_RECENTLY_UPDATED => self::sortFunction(fn(Auction $a) => $a->hasExpired() ? $a->getEndDate() : ($a->getTopBid()?->getTimestamp() ?? $a->getStartDate()), true)
            ];
        }
        return self::$closures[$type];
    }

    private static function sortFunction(Closure $closure, bool $descending = false): Closure
    {
        return static function (Auction $a, Auction $b) use ($closure, $descending): int {
            $valueA = $closure($a);
            $valueB = $closure($b);
            if ($valueA === $valueB) return 0;
            return ($valueA < $valueB ? -1 : 1) * ($descending ? -1 : 1);
        };
    }
}