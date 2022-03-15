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

    public static function closureFromType(int $type): Closure
    {
        return match ($type) {
            self::TYPE_LOWEST_BID => static function (Auction $a, Auction $b): bool {
                return ($a->getTopBid() === null ? $a->getStartingBid() : $a->getTopBid()->getBidAmount()) > ($b->getTopBid() === null ? $b->getStartingBid() : $b->getTopBid()->getBidAmount());
            },
            self::TYPE_ENDING_SOON => static function (Auction $a, Auction $b): bool {
                return $a->getEndDate() > $b->getEndDate();
            },
            self::TYPE_MOST_BIDS => static function (Auction $a, Auction $b): bool {
                return count($a->getBids()) < count($b->getBids());
            },
            self::TYPE_RECENTLY_UPDATED => static function (Auction $a, Auction $b): bool {
                return ($a->hasExpired() ? $a->getEndDate() : ($a->getTopBid() === null ? $a->getStartDate() : $a->getTopBid()->getTimestamp())) < ($b->hasExpired() ? $b->getEndDate() : ($b->getTopBid() === null ? $b->getStartDate() : $b->getTopBid()->getTimestamp()));
            },
            default => static function (Auction $a, Auction $b): bool {
                return ($a->getTopBid() === null ? $a->getStartingBid() : $a->getTopBid()->getBidAmount()) < ($b->getTopBid() === null ? $b->getStartingBid() : $b->getTopBid()->getBidAmount());
            },
        };
    }
}