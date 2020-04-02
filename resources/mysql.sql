-- #! mysql
-- #{ piggyauctions

-- # { init
CREATE TABLE IF NOT EXISTS auctions
(
    id           INTEGER PRIMARY KEY AUTO_INCREMENT,
    auctioneer   VARCHAR(15),
    item         JSON,
    startdate    BIGINT,
    enddate      BIGINT,
    claimed      INTEGER,
    claimed_bids JSON,
    starting_bid BIGINT,
    bids         JSON
);
-- # }

-- # { load
SELECT *
FROM auctions;
-- # }

-- # { add
-- #    :auctioneer string
-- #    :item string
-- #    :startdate int
-- #    :enddate int
-- #    :claimed int
-- #    :claimed_bids string
-- #    :starting_bid int
-- #    :bids string
INSERT INTO auctions (auctioneer, item, startdate, enddate, claimed, claimed_bids, starting_bid, bids)
VALUES (:auctioneer, :item, :startdate, :enddate, :claimed, :claimed_bids, :starting_bid, :bids);
-- # }

-- # { update
-- #    :id int
-- #    :claimed int
-- #    :claimed_bids string
-- #    :bids string
UPDATE auctions
SET claimed      = :claimed,
    claimed_bids = :claimed_bids,
    bids         = :bids
WHERE id = :id;
-- # }

-- # { remove
-- #    :id int
DELETE
FROM auctions
WHERE id = :id;
-- # }

-- # { statistics

-- # { init
CREATE TABLE IF NOT EXISTS statistics
(
    player VARCHAR(15),
    stats  JSON
);
-- # }

-- # { load
-- #    :player string
SELECT *
FROM statistics
WHERE player = :player;
-- # }

-- # { add
-- #    :player string
-- #    :stats string
INSERT INTO statistics (player, stats)
VALUES (:player, :stats);
-- # }

-- # { update
-- #    :player string
-- #    :stats string
UPDATE statistics
SET stats = :stats
WHERE player = :player
-- # }

-- # }

-- #}