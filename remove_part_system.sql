-- Remove part_number column from seasons table
ALTER TABLE seasons DROP COLUMN IF EXISTS part_number;

-- Remove any unique constraints that include part_number
ALTER TABLE seasons DROP INDEX IF EXISTS unique_season_part;
 
-- Add back simple unique constraint on anime_id and season_number
ALTER TABLE seasons ADD CONSTRAINT unique_season UNIQUE (anime_id, season_number); 