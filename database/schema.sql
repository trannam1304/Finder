SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS food_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_finder;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS itinerary_logs;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS restaurant_images;
DROP TABLE IF EXISTS restaurants;
DROP TABLE IF EXISTS categories;
SET FOREIGN_KEY_CHECKS = 1;

-- Task DB-02: Bảng danh mục hỗ trợ 3 nhóm lớn: food, tourism, entertainment
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type_group ENUM('food', 'tourism', 'entertainment') NOT NULL DEFAULT 'food',
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(60) NOT NULL,
  icon VARCHAR(40) NOT NULL DEFAULT 'plate',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_type_group (type_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task DB-01: Bảng địa điểm mở rộng (Ăn uống, Du lịch, Vui chơi giải trí)
CREATE TABLE restaurants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  place_type ENUM('food', 'tourism', 'entertainment') NOT NULL DEFAULT 'food',
  name VARCHAR(150) NOT NULL,
  address VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULL,
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  price_min INT UNSIGNED NOT NULL DEFAULT 0,
  price_max INT UNSIGNED NOT NULL DEFAULT 0,
  ticket_price INT UNSIGNED NOT NULL DEFAULT 0,
  estimated_duration SMALLINT UNSIGNED NOT NULL DEFAULT 60, -- Thời lượng trải nghiệm (phút)
  best_time_slot ENUM('morning', 'afternoon', 'evening', 'any') NOT NULL DEFAULT 'any', -- Thời điểm lý tưởng ghé thăm
  price_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  rating DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  rating_count INT UNSIGNED NOT NULL DEFAULT 0,
  image_url VARCHAR(255) NULL,
  open_time TIME NULL,
  close_time TIME NULL,
  is_24h TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_restaurants_category FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  KEY idx_geo (latitude, longitude),
  KEY idx_place_type (place_type),
  KEY idx_active_cat (is_active, category_id),
  KEY idx_rating (rating),
  FULLTEXT KEY ft_name_address (name, address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE restaurant_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  url VARCHAR(255) NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  CONSTRAINT fk_images_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  KEY idx_restaurant (restaurant_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(80) NOT NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARBINARY(16) NOT NULL,
  username VARCHAR(50) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  author_name VARCHAR(80) NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  comment TEXT NULL,
  is_approved TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  KEY idx_restaurant_rating (restaurant_id, rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu nhật ký tạo lộ trình (Phục vụ phân tích KPI & Dashboard Admin)
CREATE TABLE itinerary_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_ip VARBINARY(16) NULL,
  start_lat DECIMAL(10,8) NOT NULL,
  start_lng DECIMAL(11,8) NOT NULL,
  duration_type VARCHAR(20) NOT NULL DEFAULT 'half_day', -- 'half_day', 'full_day', 'evening'
  vibe VARCHAR(30) NOT NULL DEFAULT 'chill',             -- 'chill', 'adventure', 'foodie', 'date'
  stops_count TINYINT UNSIGNED NOT NULL DEFAULT 3,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Bảng lưu đề xuất địa điểm từ người dùng (Chờ Admin phê duyệt)
CREATE TABLE IF NOT EXISTS place_suggestions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  place_type ENUM('food', 'tourism', 'entertainment') NOT NULL DEFAULT 'food',
  category_id INT UNSIGNED NOT NULL,
  address VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULL,
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  price_min INT UNSIGNED NOT NULL DEFAULT 0,
  price_max INT UNSIGNED NOT NULL DEFAULT 0,
  ticket_price INT UNSIGNED NOT NULL DEFAULT 0,
  estimated_duration SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  open_time TIME NULL,
  close_time TIME NULL,
  note TEXT NULL,
  submitter_ip VARBINARY(16) NULL,
  status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  KEY idx_status (status),
  KEY idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
