-- Update coupons table to add missing fields for coupon_management.php

-- First, rename the old column names to match the new naming convention
ALTER TABLE coupons CHANGE duration_days duration_months INT DEFAULT 1;
ALTER TABLE coupons CHANGE usage_limit max_uses INT DEFAULT 100;
ALTER TABLE coupons CHANGE usage_count used_count INT DEFAULT 0;
ALTER TABLE coupons CHANGE expires_at expiry_date DATE NULL;

-- Set default expiry date for existing coupons (1 year from now)
UPDATE coupons 
SET expiry_date = DATE_ADD(CURRENT_DATE(), INTERVAL 1 YEAR)
WHERE expiry_date IS NULL;

-- Ensure the used_count doesn't exceed max_uses
UPDATE coupons
SET used_count = max_uses
WHERE used_count > max_uses; 