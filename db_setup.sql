-- Database setup for AnimeElite
-- This script creates all necessary tables for the anime streaming website

-- Create tables for anime content
CREATE TABLE IF NOT EXISTS anime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    release_year VARCHAR(4),
    genres VARCHAR(255),
    status ENUM('ongoing', 'completed', 'upcoming') DEFAULT 'ongoing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create seasons table
CREATE TABLE IF NOT EXISTS seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anime_id INT NOT NULL,
    season_number INT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    cover_image VARCHAR(255),
    release_year VARCHAR(4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
    UNIQUE KEY unique_season (anime_id, season_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create episodes table
CREATE TABLE IF NOT EXISTS episodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    episode_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    video_url TEXT NOT NULL,
    duration VARCHAR(10),
    is_premium TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_episode (season_id, episode_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    avatar VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    status ENUM('active', 'cancelled', 'expired') DEFAULT 'active',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create subscription_plans table
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2),
    features TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default subscription plans
INSERT INTO subscription_plans (name, description, price_monthly, price_yearly, features, is_active) VALUES
('Free', 'Basic access to free content', 0.00, 0.00, 'Access to free content,Create watchlist', 1),
('Premium', 'Full access to all content and features', 9.99, 99.99, 'Access to all content,No ads,Early access to new episodes,HD streaming quality', 1),
('Ultimate', 'Premium features plus exclusive content', 14.99, 149.99, 'All Premium features,4K streaming quality,Offline downloads,Priority customer support', 0);

-- Create coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    discount_percent INT DEFAULT 0,
    duration_days INT DEFAULT 0,
    usage_limit INT DEFAULT 1,
    usage_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create watch history table
CREATE TABLE IF NOT EXISTS watch_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    episode_id INT NOT NULL,
    position_seconds INT DEFAULT 0,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_completed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_watch (user_id, episode_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create favorites table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    anime_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, anime_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
-- Sample animes
INSERT INTO anime (title, description, cover_image, release_year, genres, status) VALUES
('Attack on Titan', 'Humans are nearly exterminated by giant creatures called Titans. Titans are typically several stories tall, seem to have no intelligence and eat humans.', 'https://cdn.myanimelist.net/images/anime/1300/110853.jpg', '2013', 'Action, Dark Fantasy, Post-Apocalyptic', 'ongoing'),
('Demon Slayer', 'A boy raised in a family of demon slayers fights to cure his sister, who has been turned into a demon herself.', 'https://cdn.myanimelist.net/images/anime/1286/99889.jpg', '2019', 'Action, Fantasy, Historical', 'ongoing');

-- Sample seasons
INSERT INTO seasons (anime_id, season_number, title, release_year) VALUES
(1, 1, 'Attack on Titan Season 1', '2013'),
(1, 2, 'Attack on Titan Season 2', '2017'),
(2, 1, 'Demon Slayer: Kimetsu no Yaiba', '2019');

-- Sample episodes
INSERT INTO episodes (season_id, episode_number, title, video_url, is_premium) VALUES
-- Attack on Titan Season 1
(1, 1, 'To You, 2,000 Years From Now', 'https://iframe.example.com/embed/aot-s01e01', 0),
(1, 2, 'That Day', 'https://iframe.example.com/embed/aot-s01e02', 0),
(1, 3, 'A Dim Light Amid Despair', 'https://iframe.example.com/embed/aot-s01e03', 1),
-- Attack on Titan Season 2
(2, 1, 'Beast Titan', 'https://iframe.example.com/embed/aot-s02e01', 0),
-- Demon Slayer Season 1
(3, 1, 'Cruelty', 'https://iframe.example.com/embed/ds-s01e01', 0),
(3, 2, 'Trainer Sakonji Urokodaki', 'https://iframe.example.com/embed/ds-s01e02', 0),
(3, 3, 'Sabito and Makomo', 'https://iframe.example.com/embed/ds-s01e03', 1);

-- Create admin user (password: admin123)
INSERT INTO users (username, email, password, display_name, role) VALUES
('admin', 'admin@animeelite.com', '$2y$10$EJklasXxLt81hZ6vHXthIOsOu1TmDJG4UN.PgEhn.nTpTwtYM5wU.', 'Administrator', 'admin');

-- Sample coupons
INSERT INTO coupons (code, description, discount_percent, duration_days, usage_limit) VALUES
('WELCOME50', '50% off your first month', 50, 30, 100),
('PREMIUM365', 'One year free premium', 100, 365, 10); 