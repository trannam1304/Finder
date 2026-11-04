<?php
declare(strict_types=1);

/* Copy this file to config.php and fill in the local credentials. */
const APP_ENV = 'development';
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'food_finder';
const DB_USER = 'root';
const DB_PASS = '';
const APP_URL = 'http://localhost:8000';
const NOMINATIM_USER_AGENT = 'FoodFinder/1.0 (admin@example.com)';

// Cấu hình JWT & Cookie Authentication (Không dùng Session)
const JWT_SECRET = 'change_this_to_a_random_secret_in_production';
const JWT_ACCESS_TTL = 900;       // Access Token: 15 phút
const JWT_REFRESH_TTL = 604800;   // Refresh Token: 7 ngày
const COOKIE_SECURE = false;      // false khi dev localhost, true khi chạy HTTPS production
const COOKIE_SAMESITE = 'Lax';
