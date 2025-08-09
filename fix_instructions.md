# Fixes for AnimeElite

## 1. Fix Coupon System Issues

The warnings you're seeing in the coupon management system are due to database column name mismatches. The code is trying to access fields that don't match what's in your database.

### Run the SQL Script to Fix Coupon Table

1. Go to your database administration tool (phpMyAdmin)
2. Run the following SQL query to update the column names:

```sql
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
```

## 2. Fix Signup Issue

The signup page has a critical bug - it's trying to use the submitted username and password as database credentials!

1. Edit `signup.php` and replace line 42-43 with:

```php
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $GLOBALS['username'], $GLOBALS['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

## 3. User Premium Promotion System

I've created a new admin page that allows you to directly promote users to premium status without them needing to use coupon codes.

1. Upload the new file `admin/user_promotion.php` to your server
2. The admin sidebar has been updated to include a link to this new page

## 4. Season Part System Fix

If you're having issues with adding multiple parts to the same season, run the following SQL script:

```sql
-- Fix the unique constraint on the seasons table

-- First, drop the old unique constraint
ALTER TABLE seasons DROP INDEX unique_season;

-- Then, add the new unique constraint that includes part_number
ALTER TABLE seasons ADD CONSTRAINT unique_season_part UNIQUE (anime_id, season_number, part_number);
```

## Summary of Changes

1. **Fixed coupon management system** by updating column names to match what the code expects
2. **Fixed signup page** by correcting the database connection credentials
3. **Added user promotion system** to allow admins to directly give users premium access
4. **Fixed season part system** to allow multiple parts for the same season number

Let me know if you need any further assistance or clarification! 