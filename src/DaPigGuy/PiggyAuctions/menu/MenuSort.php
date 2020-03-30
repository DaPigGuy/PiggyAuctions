<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyAuctions\menu;

use DaPigGuy\PiggyAuctions\auction\Auction;

class MenuSort
{
    const TYPE_HIGHEST_BID = 0;
    const TYPE_LOWEST_BID = 1;
    const TYPE_ENDING_SOON = 2;
    const TYPE_MOST_BIDS = 3;
    const TYPE_RECENTLY_UPDATED = 5;

    public static function closureFromType(int $type): \Closure
    {
        switch ($type) {
            case self::TYPE_LOWEST_BID:
                return function (Auction $a, Auction $b): bool {
                    return ($a->getTopBid() === null ? $a->getStartingBid() : $a->getTopBid()->getBidAmount()) > ($b->getTopBid() === null ? $b->getStartingBid() : $b->getTopBid()->getBidAmount());
                };
            case self::TYPE_ENDING_SOON:
                return function (Auction $a, Auction $b): bool {
                    return $a->getEndDate() > $b->getEndDate();
                };
            case self::TYPE_MOST_BIDS:
                return function (Auction $a, Auction $b): bool {
                    return count($a->getBids()) < count($b->getBids());
                };
            case self::TYPE_RECENTLY_UPDATED:
                return function (Auction $a, Auction $b): bool {
                    return ($a->hasExpired() ? $a->getEndDate() : ($a->getTopBid() === null ? $a->getStartDate() : $a->getTopBid()->getTimestamp())) < ($b->hasExpired() ? $b->getEndDate() : ($b->getTopBid() === null ? $b->getStartDate() : $b->getTopBid()->getTimestamp()));
                };
            default:
                return function (Auction $a, Auction $b): bool {
                    return ($a->getTopBid() === null ? $a->getStartingBid() : $a->getTopBid()->getBidAmount()) < ($b->getTopBid() === null ? $b->getStartingBid() : $b->getTopBid()->getBidAmount());
                };
        }
    }
}