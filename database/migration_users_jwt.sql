-- Migration: Tạo bảng Users, User Refresh Tokens và User Favorites cho hệ thống JWT
-- Bảng users: Quản lý tài khoản thành viên
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  avatar_url VARCHAR(255) NULL,
  role ENUM('member', 'admin') NOT NULL DEFAULT 'member',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng user_refresh_tokens: Lưu trữ và quản lý Refresh Token an toàn
-- Lưu ý: Chỉ lưu SHA-256 hash của Refresh Token, KHÔNG lưu token gốc
CREATE TABLE IF NOT EXISTS user_refresh_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_agent VARCHAR(255) NULL,
  ip_address VARBINARY(16) NULL,
  UNIQUE KEY uq_token_hash (token_hash),
  KEY idx_user_tokens (user_id, expires_at, revoked_at),
  CONSTRAINT fk_refresh_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng user_favorites: Lưu danh sách quán yêu thích của từng thành viên
CREATE TABLE IF NOT EXISTS user_favorites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  restaurant_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_resto (user_id, restaurant_id),
  KEY idx_user_fav (user_id),
  CONSTRAINT fk_fav_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_restos FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Bảng password_resets: Lưu mã OTP đặt lại mật khẩu của người dùng
CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  email VARCHAR(150) NOT NULL,
  reset_code VARCHAR(10) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_code (email, reset_code, expires_at),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Bảng user_saved_itineraries: Lưu các lộ trình yêu thích của từng thành viên
CREATE TABLE IF NOT EXISTS user_saved_itineraries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  itinerary_uid VARCHAR(64) NOT NULL,
  title VARCHAR(150) NULL,
  duration_type VARCHAR(30) NOT NULL DEFAULT 'half_day',
  vibe VARCHAR(30) NOT NULL DEFAULT 'chill',
  stops_count TINYINT UNSIGNED NOT NULL DEFAULT 3,
  total_distance_km DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  data_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_itin (user_id, itinerary_uid),
  KEY idx_user_itins (user_id, created_at),
  CONSTRAINT fk_itins_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
