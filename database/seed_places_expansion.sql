USE food_finder;

-- Task DB-03: Seed 30+ Địa điểm Du lịch & Vui chơi thực tế quanh Quận 7 & TP.HCM
INSERT INTO restaurants (
  category_id, place_type, name, address, phone,
  latitude, longitude, price_min, price_max, ticket_price,
  estimated_duration, best_time_slot, price_level, rating,
  rating_count, image_url, open_time, close_time, is_24h, note
) VALUES
-- ========================================================
-- 1. NHÓM DU LỊCH & THAM QUAN (TOURISM)
-- ========================================================
(11, 'tourism', 'Cầu Ánh Sao (Starlight Bridge)', 'Khu đô thị Phú Mỹ Hưng, Phường Tân Phú, Quận 7', '02854118888',
 10.72550000, 106.71800000, 0, 0, 0, 45, 'evening', 1, 4.7, 1250, 'assets/img/highlands.jpg', '06:00', '23:00', 0, 'Cây cầu đi bộ lung linh ánh đèn LED và đài phun nước biểu tượng của Quận 7'),

(9, 'tourism', 'Hồ Bán Nguyệt (Crescent Lake)', 'Đường Tôn Dật Tiên, Phường Tân Phú, Quận 7', '02854118888',
 10.72640000, 106.71700000, 0, 0, 0, 60, 'afternoon', 1, 4.6, 980, 'assets/img/highlands.jpg', '05:00', '23:00', 0, 'Không gian dạo bộ lãng mạn, thoáng mát bậc nhất khu đô thị Nam Sài Gòn'),

(8, 'tourism', 'Bảo tàng tranh 3D Artinus', 'Số 2-4 Đường số 9, KDC Him Lam, Phường Tân Hưng, Quận 7', '02862983767',
 10.74410000, 106.70330000, 150000, 200000, 150000, 120, 'morning', 2, 4.5, 780, 'assets/img/highlands.jpg', '09:00', '18:00', 0, 'Thế giới tranh 3D ảo giác khổng lồ với nhiều chủ đề sống ảo độc đáo'),

(9, 'tourism', 'Công viên Cảnh Đồi Phú Mỹ Hưng', 'Đại lộ Nguyễn Văn Linh, Phường Tân Phong, Quận 7', NULL,
 10.72680000, 106.71500000, 0, 0, 0, 60, 'morning', 1, 4.4, 320, 'assets/img/highlands.jpg', '05:00', '22:00', 0, 'Khuôn viên cây xanh râm mát lý tưởng để picnic, dạo bộ gần ĐH Tôn Đức Thắng'),

(9, 'tourism', 'Công viên Hoa Anh Đào Sakura Park (Midtown)', 'Đường 16, Phường Tân Phú, Quận 7', NULL,
 10.72650000, 106.71950000, 0, 0, 0, 60, 'afternoon', 1, 4.6, 450, 'assets/img/highlands.jpg', '06:00', '22:00', 0, 'Công viên ven sông với hàng cây hoa anh đào phong cách Nhật Bản'),

(10, 'tourism', 'Bến Nhà Rồng - Bảo tàng Hồ Chí Minh', 'Số 1 Nguyễn Tất Thành, Phường 12, Quận 4', '02839401060',
 10.76820000, 106.70680000, 30000, 50000, 30000, 90, 'morning', 1, 4.6, 1420, 'assets/img/highlands.jpg', '07:30', '17:00', 0, 'Di tích lịch sử quan trọng nằm bên sông Sài Gòn, rất gần Quận 7'),

(11, 'tourism', 'Bến Bạch Đằng & Ga Tàu Thủy Saigon Waterbus', 'Đường Tôn Đức Thắng, Phường Bến Nghé, Quận 1', '1900636830',
 10.77150000, 106.70680000, 15000, 50000, 15000, 60, 'evening', 1, 4.7, 2100, 'assets/img/highlands.jpg', '07:00', '22:00', 0, 'Ngắm trọn sông Sài Gòn, trải nghiệm buýt đường sông và ngắm hoàng hôn rực rỡ'),

(11, 'tourism', 'Phố đi bộ Nguyễn Huệ', 'Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1', NULL,
 10.77350000, 106.70350000, 0, 0, 0, 90, 'evening', 1, 4.6, 5400, 'assets/img/highlands.jpg', '00:00', '23:59', 1, 'Trái tim sôi động của thành phố về đêm, biểu diễn nghệ thuật đường phố'),

(10, 'tourism', 'Dinh Độc Lập (Di tích lịch sử Quốc gia đặc biệt)', '135 Nam Kỳ Khởi Nghĩa, Phường Bến Thành, Quận 1', '02838223652',
 10.77700000, 106.69530000, 40000, 65000, 40000, 120, 'morning', 1, 4.6, 3800, 'assets/img/highlands.jpg', '08:00', '16:30', 0, 'Biểu tượng lịch sử hào hùng với khuôn viên rợp bóng cây cổ thụ'),

(8, 'tourism', 'Bảo tàng Chứng tích Chiến tranh', '28 Võ Văn Tần, Phường Võ Thị Sáu, Quận 3', '02839302180',
 10.77950000, 106.69210000, 40000, 40000, 40000, 90, 'morning', 1, 4.7, 4200, 'assets/img/highlands.jpg', '07:30', '17:30', 0, 'Bảo tàng thu hút đông đảo du khách trong nước và quốc tế tìm hiểu lịch sử'),

(8, 'tourism', 'Bảo tàng Mỹ thuật TP. Hồ Chí Minh', '97A Phó Đức Chính, Phường Nguyễn Thái Bình, Quận 1', '02838294441',
 10.76940000, 106.70000000, 30000, 30000, 30000, 90, 'afternoon', 1, 4.5, 1850, 'assets/img/highlands.jpg', '08:00', '17:00', 0, 'Kiến trúc Pháp cổ kính tráng lệ với vô vàn góc chụp ảnh nghệ thuật ấn tượng'),

(9, 'tourism', 'Thảo Cầm Viên Sài Gòn', 'Số 2 Nguyễn Bỉnh Khiêm, Phường Bến Nghé, Quận 1', '02838291425',
 10.78760000, 106.70520000, 60000, 60000, 60000, 150, 'morning', 1, 4.4, 2900, 'assets/img/highlands.jpg', '07:00', '18:30', 0, 'Một trong những vườn thú lâu đời nhất thế giới với mảng xanh nhiệt đới khổng lồ'),

(11, 'tourism', 'Landmark 81 SkyView & Công viên Central Park', '720A Điện Biên Phủ, Phường 22, Quận Bình Thạnh', '0982358181',
 10.79500000, 106.72190000, 0, 300000, 0, 120, 'evening', 3, 4.8, 3500, 'assets/img/highlands.jpg', '08:30', '22:00', 0, 'Tòa tháp cao nhất Việt Nam cùng công viên ven sông quy mô 14ha hiện đại'),

(11, 'tourism', 'Cầu Ba Son & Công viên bờ sông Thủ Thiêm', 'Bờ Đông Sông Sài Gòn, Phường An Khánh, TP. Thủ Đức', NULL,
 10.77950000, 106.71020000, 0, 0, 0, 60, 'afternoon', 1, 4.7, 1600, 'assets/img/highlands.jpg', '05:00', '23:00', 0, 'Điểm check-in ngắm hoàng hôn và view toàn cảnh trung tâm Quận 1 đẹp nhất'),

(10, 'tourism', 'Nhà thờ Đức Bà & Bưu điện Trung tâm TP', 'Số 01 Công xã Paris, Phường Bến Nghé, Quận 1', '02838220192',
 10.77980000, 106.69910000, 0, 0, 0, 60, 'morning', 1, 4.6, 4800, 'assets/img/highlands.jpg', '07:30', '18:00', 0, 'Cụm công trình kiến trúc gothic và phục hưng Pháp cổ điển biểu trưng của Sài Gòn'),

(10, 'tourism', 'Tu viện Khánh An (Ngôi chùa Nhật Bản)', 'Quốc lộ 1A, Phường An Phú Đông, Quận 12', '02837196276',
 10.86100000, 106.68540000, 0, 0, 0, 90, 'morning', 1, 4.6, 920, 'assets/img/highlands.jpg', '05:00', '20:30', 0, 'Kiến trúc mang đậm nét văn hóa Á Đông và xứ sở hoa anh đào thanh tịnh'),

-- ========================================================
-- 2. NHÓM VUI CHƠI & GIẢI TRÍ (ENTERTAINMENT)
-- ========================================================
(12, 'entertainment', 'Jump Arena Him Lam (Khu Bạt Nhún Vận Động)', 'Số 2-4 Đường số 9, KDC Him Lam, Phường Tân Hưng, Quận 7', '1900789978',
 10.74410000, 106.70330000, 120000, 200000, 120000, 120, 'afternoon', 2, 4.6, 850, 'assets/img/highlands.jpg', '09:00', '21:00', 0, 'Tổ hợp bạt nhún nhào lộn, leo núi đá nhân tạo, bóng rổ nhún cực đã cho giới trẻ'),

(15, 'entertainment', 'Trung tâm thương mại SC VivoCity', '1058 Nguyễn Văn Linh, Phường Tân Phong, Quận 7', '02837761018',
 10.73000000, 106.70430000, 0, 200000, 0, 120, 'afternoon', 2, 4.5, 2300, 'assets/img/highlands.jpg', '10:00', '22:00', 0, 'TTTM phức hợp sát cạnh ĐH Tôn Đức Thắng với rạp IMAX, hồ bơi sân thượng và ẩm thực'),

(15, 'entertainment', 'Crescent Mall (TTTM Phú Mỹ Hưng)', '101 Tôn Dật Tiên, Phường Tân Phú, Quận 7', '02854133333',
 10.72870000, 106.71680000, 0, 300000, 0, 120, 'afternoon', 2, 4.6, 3100, 'assets/img/highlands.jpg', '10:00', '22:00', 0, 'Khu phức hợp mua sắm, ẩm thực và giải trí đẳng cấp quốc tế ven Hồ Bán Nguyệt'),

(13, 'entertainment', 'CGV Cinemas SC VivoCity (Rạp IMAX)', 'Tầng 5 SC VivoCity, 1058 Nguyễn Văn Linh, Quận 7', '19006017',
 10.73000000, 106.70430000, 110000, 220000, 110000, 130, 'evening', 2, 4.7, 1420, 'assets/img/highlands.jpg', '08:30', '23:30', 0, 'Rạp chiếu phim màn hình cong IMAX khổng lồ cho trải nghiệm điện ảnh đỉnh cao'),

(13, 'entertainment', 'Lotte Cinema Nam Sài Gòn', 'Tầng 3 Lotte Mart, 469 Nguyễn Hữu Thọ, Phường Tân Hưng, Quận 7', '02837752524',
 10.74060000, 106.70100000, 80000, 150000, 80000, 120, 'evening', 2, 4.4, 980, 'assets/img/highlands.jpg', '08:00', '23:30', 0, 'Cụm rạp chiếu phim tiện nghi, giá vé sinh viên ưu đãi ngay ngã tư Nguyễn Hữu Thọ'),

(12, 'entertainment', 'Hello Park - Đường đua xe F1 Karting Him Lam', '582/12 Huỳnh Tấn Phát, Phường Tân Phú, Quận 7', '0909123456',
 10.73750000, 106.72800000, 150000, 270000, 150000, 60, 'afternoon', 2, 4.5, 620, 'assets/img/highlands.jpg', '14:00', '21:30', 0, 'Đường đua xe Go-Kart chuyên nghiệp ngoài trời thử thách tốc độ dành cho nhóm bạn'),

(14, 'entertainment', 'The Escape Hunt Saigon (Trò chơi giải đố nhập vai)', '169 Hai Bà Trưng, Phường Võ Thị Sáu, Quận 3', '02862908608',
 10.78510000, 106.69520000, 180000, 250000, 180000, 90, 'afternoon', 2, 4.6, 540, 'assets/img/highlands.jpg', '09:30', '22:00', 0, 'Chuỗi phòng giải mật thư trốn thoát Escape Room lôi cuốn, rèn luyện tư duy đồng đội'),

(12, 'entertainment', 'Snow Town Sài Gòn (Thị trấn Tuyết)', 'Tầng 3-4 The CBD Premium Home, 125 Đồng Văn Cống, TP. Thủ Đức', '1900588883',
 10.77580000, 106.77250000, 160000, 220000, 160000, 120, 'morning', 2, 4.3, 1100, 'assets/img/highlands.jpg', '09:00', '19:00', 0, 'Trượt tuyết nhân tạo công nghệ Nhật Bản giữa lòng Sài Gòn mát lạnh'),

(12, 'entertainment', 'tiNiWorld Crescent Mall (Khu Giải Trí Tuổi Thơ & Trải Nghiệm)', 'Tầng B1 Crescent Mall, 101 Tôn Dật Tiên, Quận 7', '0908040411',
 10.72870000, 106.71680000, 120000, 150000, 120000, 90, 'morning', 2, 4.4, 760, 'assets/img/highlands.jpg', '10:00', '21:30', 0, 'Khu vui chơi tương tác đa sắc màu với nhiều góc sáng tạo và máy game phong phú'),

(12, 'entertainment', 'Megabowl Bowling Crescent Mall', 'Tầng 5 Crescent Mall, 101 Tôn Dật Tiên, Quận 7', '02854133333',
 10.72870000, 106.71680000, 80000, 120000, 80000, 90, 'evening', 2, 4.5, 430, 'assets/img/highlands.jpg', '10:00', '22:00', 0, 'Sân chơi Bowling tiêu chuẩn quốc tế với hệ thống âm thanh ánh sáng hiện đại'),

(14, 'entertainment', 'Board Game Station Quận 7', 'Số 24 Đường số 10, Phường Tân Quy, Quận 7', '0943218765',
 10.74050000, 106.70550000, 45000, 80000, 45000, 120, 'evening', 1, 4.7, 390, 'assets/img/highlands.jpg', '09:00', '22:30', 0, 'Không gian cà phê board game hơn 200 tựa game giải trí cho sinh viên gần TDTU'),

(14, 'entertainment', 'Workshop Làm Gốm & Vẽ Tranh Tipsy Art Q7', '65 Nguyễn Thị Thập, Phường Tân Phú, Quận 7', '0912345678',
 10.73780000, 106.72480000, 250000, 350000, 250000, 150, 'morning', 2, 4.8, 280, 'assets/img/highlands.jpg', '09:00', '18:00', 0, 'Khóa học vẽ tranh thư giãn cuối tuần và tự tay xoay gốm mang về làm kỷ niệm'),

(12, 'entertainment', 'CLB Bắn Cung Trần Quan Brothers Quận 7', 'Khu thể thao Him Lam, Phường Tân Hưng, Quận 7', '0901234567',
 10.74350000, 106.69750000, 80000, 150000, 80000, 60, 'afternoon', 2, 4.5, 340, 'assets/img/highlands.jpg', '08:30', '20:30', 0, 'Trải nghiệm bắn cung thể thao có huấn luyện viên hướng dẫn tận tình'),

(12, 'entertainment', 'Sân Băng Tự Nhiên Vincom Ice Rink', 'Tầng 5 Vincom Mega Mall Thảo Điền, TP. Thủ Đức', '19006017',
 10.80240000, 106.74130000, 150000, 220000, 150000, 90, 'afternoon', 2, 4.6, 890, 'assets/img/highlands.jpg', '09:30', '22:00', 0, 'Sân trượt băng sân băng thật quy mô lớn đầu tiên tại TP.HCM'),

(12, 'entertainment', 'CLB Bida & Gaming Center TDTU', '486 Lê Văn Lương, Phường Tân Phong, Quận 7', '0934567890',
 10.73530000, 106.70240000, 50000, 90000, 50000, 90, 'evening', 1, 4.4, 410, 'assets/img/highlands.jpg', '08:00', '23:59', 0, 'Khu giải trí bida lỗ và máy tính cấu hình cao phục vụ sinh viên đối diện cổng TDTU'),

(15, 'entertainment', 'Phố Ẩm Thực & Giải Trí Đêm Panorama', 'Khu Panorama, Tôn Dật Tiên, Phường Tân Phong, Quận 7', NULL,
 10.72850000, 106.71680000, 50000, 200000, 0, 120, 'evening', 2, 4.6, 950, 'assets/img/highlands.jpg', '16:00', '23:30', 0, 'Tổ hợp ẩm thực đường phố, cafe ngoài trời và các hoạt động dạo phố đêm náo nhiệt');
