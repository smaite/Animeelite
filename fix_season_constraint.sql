-- Fix the unique constraint on the seasons table

-- First, drop the old unique constraint
ALTER TABLE seasons DROP INDEX unique_season;

-- Then, add the new unique constraint that includes part_number
ALTER TABLE seasons ADD CONSTRAINT unique_season_part UNIQUE (anime_id, season_number, part_number);

-- If you get an error about duplicate entries, you'll need to find and fix those first
-- You can find duplicates with this query:
-- SELECT anime_id, season_number, part_number, COUNT(*) as count
-- FROM seasons
-- GROUP BY anime_id, season_number, part_number
-- HAVING COUNT(*) > 1; 