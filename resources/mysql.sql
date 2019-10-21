-- #! mysql
-- #{ piggyauctions

-- # { init
CREATE TABLE IF NOT EXISTS auctions
(
    id         INTEGER PRIMARY KEY AUTO_INCREMENT,
    auctioneer VARCHAR(15),
    item       JSON,
    enddate    INTEGER,
    bids       JSON
);
-- # }

-- # { load
SELECT *
FROM auctions;
-- # }

-- # { add
-- #    :auctioneer string
-- #    :item string
-- #    :enddate int
-- #    :bids string
INSERT INTO auctions (auctioneer, item, enddate, bids)
VALUES (:auctioneer, :item, :enddate, :bids);
-- # }

-- # { update
-- #    :id int
-- #    :bids string
UPDATE auctions SET bids = :bids WHERE id = :id;
-- # }

-- # { remove
-- #    :id int
DELETE
FROM auctions
WHERE id = :id;
-- # }

-- #}