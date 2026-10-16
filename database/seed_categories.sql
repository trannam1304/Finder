USE food_finder;

-- Task DB-02: Nạp danh mục đầy đủ theo 3 nhóm lớn: food, tourism, entertainment
INSERT INTO categories (id, type_group, name, slug, icon, sort_order) VALUES
-- 1. Nhóm Ẩm thực (Food & Drink)
(1, 'food', 'Cơm', 'com', 'rice', 1),
(2, 'food', 'Bún, Phở, Hủ tiếu', 'bun-pho', 'noodles', 2),
(3, 'food', 'Bánh mì', 'banh-mi', 'bread', 3),
(4, 'food', 'Lẩu, Nướng', 'lau-nuong', 'grill', 4),
(5, 'food', 'Ăn vặt', 'an-vat', 'snack', 5),
(6, 'food', 'Chay', 'chay', 'leaf', 6),
(7, 'food', 'Cà phê, Trà sữa', 'ca-phe', 'coffee', 7),

-- 2. Nhóm Du lịch & Tham quan (Tourism & Sightseeing)
(8, 'tourism', 'Bảo tàng & Nghệ thuật', 'bao-tang', 'landmark', 8),
(9, 'tourism', 'Công viên & Cảnh quan', 'cong-vien', 'trees', 9),
(10, 'tourism', 'Di tích & Văn hóa', 'di-tich', 'monument', 10),
(11, 'tourism', 'Check-in & Hoàng hôn', 'check-in', 'camera', 11),

-- 3. Nhóm Vui chơi & Trải nghiệm (Entertainment & Activities)
(12, 'entertainment', 'Khu thể thao, vận động', 'van-dong', 'trophy', 12),
(13, 'entertainment', 'Rạp chiếu phim', 'rap-phim', 'film', 13),
(14, 'entertainment', 'Board Game & Workshop', 'board-game', 'gamepad', 14),
(15, 'entertainment', 'Mua sắm & Phức hợp', 'mua-sam', 'bag', 15)
ON DUPLICATE KEY UPDATE 
  type_group = VALUES(type_group),
  name = VALUES(name),
  slug = VALUES(slug),
  icon = VALUES(icon),
  sort_order = VALUES(sort_order);
