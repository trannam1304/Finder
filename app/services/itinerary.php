<?php
declare(strict_types=1);

require_once __DIR__ . '/geo.php';
require_once __DIR__ . '/restaurant_query.php';

/**
 * Cấu hình ma trận các chặng dừng (Slot templates) phân rã sâu sắc
 * theo Khung thời gian (duration) và Phong cách trải nghiệm (experience_style / vibe).
 *
 * Đảm bảo tính đa dạng chuyên biệt 100% về hoạt động, nhịp độ và không gian:
 * - Foodie: Food tour ăn sập từ khai vị, món chính đến tráng miệng/trà sữa đêm.
 * - Date: Hẹn hò lãng mạn, không gian riêng tư, view đẹp, nhịp độ thư thái.
 * - Chill: Thảnh thơi, công viên xanh, cà phê sách/sân vườn, ẩm thực thanh tịnh.
 * - Adventure: Vui chơi thể thao vận động (leo núi, bạt nhún, bowling), game tương tác, nạp năng lượng.
 */
function itinerary_template(string $duration, string $vibe): array
{
    static $templateCache = [];
    $cacheKey = $duration . ':' . $vibe;
    if (isset($templateCache[$cacheKey])) {
        return $templateCache[$cacheKey];
    }
    // =========================================================================
    // 1. KHUNG THỜI GIAN: BUỔI TỐI (EVENING ~3.5 TIẾNG - 3 CHẶNG GẦN NHAU)
    // =========================================================================
    if ($duration === 'evening') {
        switch ($vibe) {
            case 'foodie': // Food Tour Ăn Sập Đêm
                return [
                    [
                        'step' => 1,
                        'title' => 'Chặng 1: Khai vị đường phố & Ăn vặt chiều tối',
                        'time_slot' => '17:30 - 18:30',
                        'place_type' => 'food',
                        'preferred_cats' => [5, 3], // Ăn vặt, Bánh mì
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'street_snack',
                        'activity_note' => 'Khởi động chiếc bụng đói với các món ăn vặt đường phố hoặc bánh mì giòn rụm.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Chặng 2: Bữa tối ẩm thực đỉnh cao — Lẩu nướng no say',
                        'time_slot' => '18:45 - 20:15',
                        'place_type' => 'food',
                        'preferred_cats' => [4, 1, 2], // Lẩu nướng, Cơm, Bún phở đặc sản
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'heavy_dinner',
                        'activity_note' => 'Thưởng thức bữa tối hoành tráng với lẩu nướng nghi ngút khói hoặc đặc sản đậm đà.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Chặng 3: Tráng miệng ngọt ngào & Trà sữa / Chè khuya',
                        'time_slot' => '20:30 - 22:00',
                        'place_type' => 'food',
                        'preferred_cats' => [7, 5], // Trà sữa/Cà phê, Ăn vặt tráng miệng
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'dessert_night',
                        'activity_note' => 'Khép lại tour ăn uống với trà sữa đậm vị, chè bưởi hoặc cà phê tán gẫu đêm.',
                    ],
                ];

            case 'date': // Hẹn Hò Lãng Mạn Đêm
                return [
                    [
                        'step' => 1,
                        'title' => 'Chặng 1: Đón hoàng hôn & Dạo bước bên sông / Cầu biểu tượng',
                        'time_slot' => '17:30 - 18:30',
                        'place_type' => 'tourism',
                        'preferred_cats' => [11, 9], // Check-in hoàng hôn, Công viên cảnh quan
                        'fallback_types' => ['tourism', 'food'],
                        'vibe_intent' => 'sunset_walk',
                        'activity_note' => 'Cùng ngắm ánh hoàng hôn buông lãng mạn, tận hưởng làn gió mát và chụp ảnh kỷ niệm.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Chặng 2: Bữa tối lãng mạn & Không gian ấm cúng 2 người',
                        'time_slot' => '18:45 - 20:15',
                        'place_type' => 'food',
                        'preferred_cats' => [4, 1, 7], // Lẩu nướng cao cấp, Cơm sang trọng, Cà phê ẩm thực
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'romantic_dinner',
                        'activity_note' => 'Bữa tối riêng tư với ánh đèn vàng ấm áp, ẩm thực tinh tế và những câu chuyện ngọt ngào.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Chặng 3: Cà phê ngắm phố đêm / Rạp phim không gian riêng tư',
                        'time_slot' => '20:30 - 22:00',
                        'place_type' => 'food',
                        'preferred_cats' => [7, 13, 14], // Cà phê chill, Rạp phim, Boardgame nhẹ
                        'fallback_types' => ['food', 'entertainment'],
                        'vibe_intent' => 'romantic_nightcap',
                        'activity_note' => 'Nhâm nhi đồ uống trên rooftop ngắm cảnh đêm lung linh hoặc xem suất chiếu phim tình cảm.',
                    ],
                ];

            case 'adventure': // Vui Chơi Năng Động & Đêm Sôi Nổi
                return [
                    [
                        'step' => 1,
                        'title' => 'Chặng 1: Bữa tối tiếp năng lượng — BBQ / Món ngon đường phố',
                        'time_slot' => '17:45 - 19:00',
                        'place_type' => 'food',
                        'preferred_cats' => [4, 1, 5], // Lẩu nướng, Cơm, Ăn vặt
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'power_fuel',
                        'activity_note' => 'Nạp đầy đủ calo và protein với bữa tiệc nướng rực rỡ để sẵn sàng cho đêm vui chơi.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Chặng 2: Tranh tài thử thách — Bowling, Game Center hoặc Board Game',
                        'time_slot' => '19:15 - 21:00',
                        'place_type' => 'entertainment',
                        'preferred_cats' => [12, 14, 15], // Thể thao/Bowling, Boardgame đối kháng, Khu giải trí
                        'fallback_types' => ['entertainment'],
                        'vibe_intent' => 'thrill_play',
                        'activity_note' => 'Bung xõa tiếng cười cùng hội bạn với những ván bowling kịch tính hoặc board game gay cấn.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Chặng 3: Ăn khuya & Cà phê đêm chém gió rôm rả',
                        'time_slot' => '21:15 - 22:30',
                        'place_type' => 'food',
                        'preferred_cats' => [7, 2, 5], // Cà phê đêm, Hủ tiếu khuya, Ăn vặt
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'late_night_hub',
                        'activity_note' => 'Tổng kết chuyến đi với ly cà phê đá mát lạnh hoặc tô hủ tiếu gõ nóng hổi về đêm.',
                    ],
                ];

            case 'chill':
            default: // Thư Thái & Check-in Đêm
                return [
                    [
                        'step' => 1,
                        'title' => 'Chặng 1: Dạo mát ngắm hoàng hôn ven sông & Thư giãn tinh thần',
                        'time_slot' => '17:30 - 18:30',
                        'place_type' => 'tourism',
                        'preferred_cats' => [11, 9], // Check-in hoàng hôn, Công viên cảnh quan
                        'fallback_types' => ['tourism'],
                        'vibe_intent' => 'relax_sunset',
                        'activity_note' => 'Tận hưởng làn gió sông mát lành, xua tan căng thẳng sau ngày làm việc/học tập.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Chặng 2: Bữa tối thanh đạm & Ẩm thực ấm áp',
                        'time_slot' => '18:45 - 20:00',
                        'place_type' => 'food',
                        'preferred_cats' => [2, 6, 1], // Bún phở thanh nhẹ, Món chay thanh tịnh, Cơm
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'gentle_dinner',
                        'activity_note' => 'Thưởng thức bữa tối nhẹ nhàng, dễ tiêu hóa trong không gian quán mộc mạc yên bình.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Chặng 3: Cà phê tĩnh lặng, nghe nhạc êm dịu & Ngắm phố',
                        'time_slot' => '20:15 - 22:00',
                        'place_type' => 'food',
                        'preferred_cats' => [7], // Cà phê chill
                        'fallback_types' => ['food', 'tourism'],
                        'vibe_intent' => 'cozy_lounge',
                        'activity_note' => 'Chọn một góc quán yên tĩnh, nhâm nhi tách trà/cà phê ấm và lắng nghe giai điệu thư giãn.',
                    ],
                ];
        }
    }

    // =========================================================================
    // 2. KHUNG THỜI GIAN: CẢ NGÀY (FULL_DAY ~8 TIẾNG - 5 CHẶNG PHÂN BỔ NHỊP SINH HỌC)
    // =========================================================================
    if ($duration === 'full_day') {
        switch ($vibe) {
            case 'foodie': // Food Tour Ăn Sập 5 Chặng Trọn Vẹn
                return [
                    [
                        'step' => 1,
                        'title' => 'Chặng 1 (Sáng): Điểm tâm truyền thống — Phở / Bún bò nóng hổi',
                        'time_slot' => '08:00 - 09:00',
                        'place_type' => 'food',
                        'preferred_cats' => [2, 3], // Bún phở, Bánh mì
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'breakfast_comfort',
                        'activity_note' => 'Bắt đầu ngày mới với bát bún/phở nước dùng đậm đà thơm ngát sực nức.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Chặng 2 (Sáng muộn): Cà phê Specialty & Khám phá món ăn vặt trứ danh',
                        'time_slot' => '09:30 - 11:00',
                        'place_type' => 'food',
                        'preferred_cats' => [7, 5], // Cà phê ngon, Ăn vặt
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'specialty_coffee',
                        'activity_note' => 'Thưởng thức cà phê pha máy chuẩn vị kết hợp nhâm nhi đồ ăn nhẹ độc đáo.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Chặng 3 (Trưa): Đại tiệc cơm tấm / Món chính đậm đà bản sắc',
                        'time_slot' => '11:30 - 13:15',
                        'place_type' => 'food',
                        'preferred_cats' => [1, 4], // Cơm tấm, Lẩu nướng
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'lunch_feast',
                        'activity_note' => 'Ăn trưa no nê với đĩa cơm sườn nướng thơm phức và nghỉ ngơi lấy sức.',
                    ],
                    [
                        'step' => 4,
                        'title' => 'Chặng 4 (Xế chiều): Oanh tạc thiên đường ăn vặt & Trà sữa giải nhiệt',
                        'time_slot' => '14:30 - 16:30',
                        'place_type' => 'food',
                        'preferred_cats' => [5, 7, 3], // Ăn vặt, Trà sữa, Bánh mì
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'afternoon_street_eats',
                        'activity_note' => 'Khám phá thế giới ăn vặt: bánh tráng, xiên que, chè mát lành giữa buổi xế.',
                    ],
                    [
                        'step' => 5,
                        'title' => 'Chặng 5 (Tối): Đại tiệc nướng lẩu hoàng hôn & Khép lại Food Tour',
                        'time_slot' => '17:30 - 20:00',
                        'place_type' => 'food',
                        'preferred_cats' => [4, 1], // Lẩu nướng
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'dinner_grand_finale',
                        'activity_note' => 'Bữa tiệc lẩu nướng kết ngày cùng bạn bè, rôm rả ôn lại trải nghiệm ẩm thực.',
                    ],
                ];

            case 'date': // Ngày Kỷ Niệm / Hẹn Hò Lãng Mạn Trọn Vẹn
                return [
                    [
                        'step' => 1,
                        'title' => 'Chặng 1 (Sáng): Cà phê sáng phong cách & Bánh mì tao nhã',
                        'time_slot' => '08:30 - 09:45',
                        'place_type' => 'food',
                        'preferred_cats' => [7, 3], // Cà phê sân vườn, Bánh mì
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'morning_rendezvous',
                        'activity_note' => 'Gặp nhau tại quán cafe đẹp, cùng thưởng thức bữa sáng nhẹ nhàng và trò chuyện.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Chặng 2 (Sáng muộn): Thưởng lãm nghệ thuật & Check-in Bảo tàng 3D',
                        'time_slot' => '10:00 - 12:00',
                        'place_type' => 'tourism',
                        'preferred_cats' => [8, 11], // Bảo tàng tranh/nghệ thuật, Check-in
                        'fallback_types' => ['tourism'],
                        'vibe_intent' => 'art_walk',
                        'activity_note' => 'Cùng nhau lưu lại những khung hình đẹp lung linh tại không gian triển lãm nghệ thuật.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Chặng 3 (Trưa): Bữa trưa ấm cúng & Nghỉ ngơi riêng tư',
                        'time_slot' => '12:15 - 13:45',
                        'place_type' => 'food',
                        'preferred_cats' => [4, 1, 6], // Lẩu nướng ấm áp, Cơm gia đình, Chay thanh lịch
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'intimate_lunch',
                        'activity_note' => 'Bữa trưa ngon miệng với không gian lịch sự, yên tĩnh để hai người chia sẻ câu chuyện.',
                    ],
                    [
                        'step' => 4,
                        'title' => 'Chặng 4 (Chiều): Workshop làm đồ thủ công, Board Game hoặc Rạp phim',
                        'time_slot' => '14:15 - 16:45',
                        'place_type' => 'entertainment',
                        'preferred_cats' => [14, 13, 15], // Workshop/Boardgame, Rạp phim, Mua sắm
                        'fallback_types' => ['entertainment'],
                        'vibe_intent' => 'couple_activity',
                        'activity_note' => 'Trải nghiệm hoạt động chung tạo sự gắn kết, cùng xem phim hoặc làm sản phẩm kỷ niệm.',
                    ],
                    [
                        'step' => 5,
                        'title' => 'Chặng 5 (Tối): Dạo bước Cầu Ánh Sao ngắm hoàng hôn & Ăn tối lãng mạn',
                        'time_slot' => '17:15 - 19:30',
                        'place_type' => 'tourism',
                        'preferred_cats' => [11, 9, 4], // Hoàng hôn hồ Bán Nguyệt/Cầu Ánh Sao, Công viên, Lẩu nướng
                        'fallback_types' => ['tourism', 'food'],
                        'vibe_intent' => 'sunset_climax',
                        'activity_note' => 'Khoảnh khắc lãng mạn nhất trong ngày khi đèn cầu bật sáng rực rỡ bên mặt hồ phẳng lặng.',
                    ],
                ];

            case 'adventure': // Một Ngày Bùng Nổ Vận Động & Khám Phá
                return [
                    [
                        'step' => 1,
                        'title' => 'Chặng 1 (Sáng): Nạp đầy năng lượng với điểm tâm chắc bụng',
                        'time_slot' => '08:00 - 09:00',
                        'place_type' => 'food',
                        'preferred_cats' => [1, 2, 3], // Cơm, Bún phở, Bánh mì
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'fuel_up',
                        'activity_note' => 'Ăn sáng no nê, giàu protein để chuẩn bị thể lực cho chuỗi hoạt động vận động.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Chặng 2 (Sáng muộn): Thể thao bùng nổ — Trampoline Jump Arena / Leo núi',
                        'time_slot' => '09:30 - 12:00',
                        'place_type' => 'entertainment',
                        'preferred_cats' => [12, 14], // Vận động, Trò chơi thể thao
                        'fallback_types' => ['entertainment'],
                        'vibe_intent' => 'extreme_sport',
                        'activity_note' => 'Thỏa sức nhảy bạt nhún, thử thách vượt chướng ngại vật và bung tỏa năng lượng.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Chặng 3 (Trưa): Bữa trưa thịnh soạn — Cơm gà xối mỡ / Lẩu bổ sung calo',
                        'time_slot' => '12:15 - 13:45',
                        'place_type' => 'food',
                        'preferred_cats' => [1, 4], // Cơm gà xối mỡ, Lẩu nướng
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'recharge_feast',
                        'activity_note' => 'Ăn trưa thật đã miệng, bù nước và nghỉ ngơi lấy lại sức.',
                    ],
                    [
                        'step' => 4,
                        'title' => 'Chặng 4 (Chiều): Giải trí phức hợp, Game thực tế ảo hoặc Rạp IMAX',
                        'time_slot' => '14:15 - 16:45',
                        'place_type' => 'entertainment',
                        'preferred_cats' => [15, 13, 12], // Mua sắm/Phức hợp, Rạp phim, Bowling
                        'fallback_types' => ['entertainment'],
                        'vibe_intent' => 'tech_entertainment',
                        'activity_note' => 'Trải nghiệm rạp chiếu màn hình lớn hoặc khu trò chơi điện tử kỹ thuật số.',
                    ],
                    [
                        'step' => 5,
                        'title' => 'Chặng 5 (Tối): Check-in biểu tượng thành phố & Ăn mừng chuyến đi',
                        'time_slot' => '17:15 - 19:30',
                        'place_type' => 'tourism',
                        'preferred_cats' => [11, 8], // Check-in biểu tượng, Hoàng hôn
                        'fallback_types' => ['tourism', 'food'],
                        'vibe_intent' => 'iconic_finish',
                        'activity_note' => 'Chụp hình kỷ niệm tại điểm view cao tầng ngoạn mục và kết thúc ngày đầy ấn tượng.',
                    ],
                ];

            case 'chill':
            default: // Nghỉ Dưỡng Giữa Lòng Phố Thị (Chill 5 Chặng)
                return [
                    [
                        'step' => 1,
                        'title' => 'Chặng 1 (Sáng): Cà phê sân vườn yên ả & Điểm tâm thanh nhẹ',
                        'time_slot' => '08:30 - 09:45',
                        'place_type' => 'food',
                        'preferred_cats' => [7, 3], // Cà phê, Bánh mì
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'slow_morning',
                        'activity_note' => 'Ngồi dưới bóng cây xanh mát, nhâm nhi cà phê và đọc sách trong không khí sớm trong lành.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Chặng 2 (Sáng muộn): Tản bộ công viên cảnh quan & Khám phá di sản',
                        'time_slot' => '10:00 - 11:45',
                        'place_type' => 'tourism',
                        'preferred_cats' => [9, 10, 8], // Công viên cảnh đồi, Di tích, Bảo tàng
                        'fallback_types' => ['tourism'],
                        'vibe_intent' => 'nature_culture_walk',
                        'activity_note' => 'Hít thở không khí tự nhiên, chiêm ngưỡng kiến trúc và cây xanh rợp bóng mát.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Chặng 3 (Trưa): Ẩm thực thanh tịnh, dưỡng lành (Cơm chay / Cơm niêu)',
                        'time_slot' => '12:00 - 13:30',
                        'place_type' => 'food',
                        'preferred_cats' => [6, 1], // Chay, Cơm
                        'fallback_types' => ['food'],
                        'vibe_intent' => 'mindful_lunch',
                        'activity_note' => 'Bữa trưa chay thanh đạm, thanh lọc cơ thể trong không gian thiền tịnh an yên.',
                    ],
                    [
                        'step' => 4,
                        'title' => 'Chặng 4 (Chiều): Trải nghiệm thư thái — Cà phê boardgame nhẹ nhàng',
                        'time_slot' => '14:00 - 16:30',
                        'place_type' => 'entertainment',
                        'preferred_cats' => [14, 7, 15], // Boardgame nhẹ, Cà phê chill, Phức hợp
                        'fallback_types' => ['entertainment', 'food'],
                        'vibe_intent' => 'relaxing_activity',
                        'activity_note' => 'Chơi vài ván boardgame mang tính kết nối hoặc nghe nhạc du dương thư giãn.',
                    ],
                    [
                        'step' => 5,
                        'title' => 'Chặng 5 (Tối): Hoàng hôn bờ hồ & Gió mát ven sông',
                        'time_slot' => '17:00 - 19:00',
                        'place_type' => 'tourism',
                        'preferred_cats' => [11, 9], // Check-in hoàng hôn, Công viên
                        'fallback_types' => ['tourism'],
                        'vibe_intent' => 'sunset_serenity',
                        'activity_note' => 'Ngắm mặt trời lặn sau những rặng dừa nước và rặng cây bên bờ hồ thoáng đãng.',
                    ],
                ];
        }
    }

    // =========================================================================
    // 3. KHUNG THỜI GIAN: NỬA NGÀY (HALF_DAY ~4 TIẾNG - 3 CHẶNG GOM CỤM CHẶT CHẼ)
    // =========================================================================
    switch ($vibe) {
        case 'foodie': // Food Tour Nửa Ngày Oanh Tạc
            return [
                [
                    'step' => 1,
                    'title' => 'Chặng 1: Khởi động vị giác với món nước truyền thống nóng hổi',
                    'time_slot' => '08:30 - 09:30',
                    'place_type' => 'food',
                    'preferred_cats' => [2, 3], // Bún phở, Bánh mì
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'hearty_breakfast',
                    'activity_note' => 'Thưởng thức tô bún bò/phở đậm đà đúng điệu Sài Gòn để đánh thức mọi giác quan.',
                ],
                [
                    'step' => 2,
                    'title' => 'Chặng 2: Cà phê Specialty & Thưởng thức món ăn vặt đặc trưng',
                    'time_slot' => '09:45 - 11:15',
                    'place_type' => 'food',
                    'preferred_cats' => [7, 5], // Cà phê, Ăn vặt
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'coffee_and_snacks',
                    'activity_note' => 'Chuyển qua quán cà phê phong cách, nhâm nhi thức uống yêu thích cùng đồ ăn nhẹ.',
                ],
                [
                    'step' => 3,
                    'title' => 'Chặng 3: Đại tiệc cơm trưa no say — Cơm tấm sườn nướng',
                    'time_slot' => '11:30 - 13:00',
                    'place_type' => 'food',
                    'preferred_cats' => [1, 4], // Cơm, Lẩu nướng
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'big_lunch',
                    'activity_note' => 'Kết thúc nửa ngày với đĩa cơm tấm sườn cọng nướng than hoa trứ danh.',
                ],
            ];

        case 'date': // Nửa Ngày Hẹn Hò Ngọt Ngào
            return [
                [
                    'step' => 1,
                    'title' => 'Chặng 1: Cà phê sáng phong cách Vintage & Trò chuyện khởi đầu ngày',
                    'time_slot' => '08:30 - 09:45',
                    'place_type' => 'food',
                    'preferred_cats' => [7, 3], // Cà phê sân vườn/thơ mộng, Bánh mì
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'romantic_cafe',
                    'activity_note' => 'Điểm hẹn cà phê yên bình, không gian lãng mạn giúp câu chuyện thêm tự nhiên gắn kết.',
                ],
                [
                    'step' => 2,
                    'title' => 'Chặng 2: Cùng nhau trải nghiệm tranh 3D / Bảo tàng nghệ thuật',
                    'time_slot' => '10:00 - 11:45',
                    'place_type' => 'tourism',
                    'preferred_cats' => [8, 11, 9], // Bảo tàng tranh 3D, Check-in, Công viên
                    'fallback_types' => ['tourism'],
                    'vibe_intent' => 'art_experience',
                    'activity_note' => 'Cùng tạo dáng hài hước và chụp lại những bức ảnh kỷ niệm tuyệt đẹp.',
                ],
                [
                    'step' => 3,
                    'title' => 'Chặng 3: Bữa trưa lãng mạn bên nhau',
                    'time_slot' => '12:00 - 13:30',
                    'place_type' => 'food',
                    'preferred_cats' => [4, 1, 6], // Lẩu nướng ấm cúng, Cơm, Chay thanh lịch
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'cozy_lunch',
                    'activity_note' => 'Thưởng thức món ăn nóng hổi trong không gian thân mật, khép lại nửa ngày đáng nhớ.',
                ],
            ];

        case 'adventure': // Nửa Ngày Vận Động & Bung Năng Lượng
            return [
                [
                    'step' => 1,
                    'title' => 'Chặng 1: Nạp nhanh calo với bánh mì giòn rụm & Cà phê năng lượng',
                    'time_slot' => '08:30 - 09:15',
                    'place_type' => 'food',
                    'preferred_cats' => [3, 7, 2], // Bánh mì, Cà phê, Bún phở
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'quick_energy',
                    'activity_note' => 'Bữa sáng nhanh gọn nhưng giàu năng lượng để sẵn sàng vận động thể chất.',
                ],
                [
                    'step' => 2,
                    'title' => 'Chặng 2: Thử thách vận động cực vui — Nhảy bạt nhún Trampoline',
                    'time_slot' => '09:30 - 11:45',
                    'place_type' => 'entertainment',
                    'preferred_cats' => [12, 14, 15], // Thể thao Jump Arena, Boardgame đối kháng, Mua sắm
                    'fallback_types' => ['entertainment'],
                    'vibe_intent' => 'dynamic_action',
                    'activity_note' => 'Vui chơi hết mình tại khu vận động liên hoàn, thi đấu ném bóng bạt nhún.',
                ],
                [
                    'step' => 3,
                    'title' => 'Chặng 3: Bữa trưa giàu đạm ăn mừng chiến thắng',
                    'time_slot' => '12:00 - 13:30',
                    'place_type' => 'food',
                    'preferred_cats' => [1, 4], // Cơm gà xối mỡ, Lẩu nướng
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'power_lunch',
                    'activity_note' => 'Tự thưởng cho bản thân một bữa ăn thật no nê sau những giờ vận động tốn sức.',
                ],
            ];

        case 'chill':
        default: // Thư Thái & Tái Tạo Năng Lượng (Mặc định)
            return [
                [
                    'step' => 1,
                    'title' => 'Chặng 1: Cà phê sáng thảnh thơi & Ngắm nhìn phố xá bắt đầu ngày',
                    'time_slot' => '08:30 - 09:30',
                    'place_type' => 'food',
                    'preferred_cats' => [7, 3], // Cà phê, Bánh mì
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'morning_chill',
                    'activity_note' => 'Bắt đầu ngày mới thảnh thơi với ly bạc xỉu đá hoặc cold brew mát lành.',
                ],
                [
                    'step' => 2,
                    'title' => 'Chặng 2: Tản bộ công viên rợp bóng mát & Hít thở khí trời',
                    'time_slot' => '09:45 - 11:30',
                    'place_type' => 'tourism',
                    'preferred_cats' => [9, 11, 8, 10], // Công viên, Check-in, Bảo tàng, Di tích
                    'fallback_types' => ['tourism'],
                    'vibe_intent' => 'park_stroll',
                    'activity_note' => 'Dạo bước dưới những tán cây xanh, lắng nghe tiếng chim hót và thư thái tâm hồn.',
                ],
                [
                    'step' => 3,
                    'title' => 'Chặng 3: Bữa trưa thanh đạm dinh dưỡng & Phục hồi năng lượng',
                    'time_slot' => '11:45 - 13:15',
                    'place_type' => 'food',
                    'preferred_cats' => [6, 1, 2], // Chay, Cơm, Bún phở
                    'fallback_types' => ['food'],
                    'vibe_intent' => 'nutritious_lunch',
                    'activity_note' => 'Thưởng thức ẩm thực thanh lành, tốt cho sức khỏe để nạp lại nguồn năng lượng tích cực.',
                ],
            ];
    }
}

/**
 * Tiêu đề mô tả lộ trình dựa trên vibe và duration.
 */
function itinerary_meta(string $duration, string $vibe): array
{
    static $metaCache = [];
    $cacheKey = $duration . ':' . $vibe;
    if (isset($metaCache[$cacheKey])) {
        return $metaCache[$cacheKey];
    }
    $vibeNames = [
        'chill' => 'Thư Thái & Check-in',
        'adventure' => 'Vui Chơi Năng Động & Trải Nghiệm',
        'foodie' => 'Ăn Sập & Thưởng Thức Ẩm Thực',
        'date' => 'Hẹn Hò Lãng Mạn',
    ];
    $durationNames = [
        'half_day' => 'Nửa ngày (~4 tiếng)',
        'full_day' => 'Cả ngày (~8 tiếng)',
        'evening' => 'Buổi tối (~3.5 tiếng)',
    ];

    $title = sprintf(
        'Lộ Trình %s — Phong Cách %s',
        $durationNames[$duration] ?? 'Khám Phá',
        $vibeNames[$vibe] ?? 'Tự Do'
    );

    return [
        'title' => $title,
        'duration_label' => $durationNames[$duration] ?? 'Nửa ngày',
        'vibe_label' => $vibeNames[$vibe] ?? 'Chill',
    ];
}

/**
 * Thuật toán tìm kiếm ứng viên nhận thức không gian (Spatial-Aware Candidate Search).
 *
 * Áp dụng cơ chế kiểm soát ràng buộc nghiêm ngặt:
 * 1. Bán kính tối đa R_max giữa 2 điểm liên tiếp (d <= R_max).
 * 2. Hàm chấm điểm đa biến Spatial Utility Score:
 *    Score = ProximityScore (tối đa 40) + VibeCategoryMatch (tối đa 35) + TypeMatch (15) + Rating (10)
 * 3. Nếu trong R_max không có category ưu tiên, nới lỏng sang fallback types trong R_max.
 * 4. Nếu toàn bộ vùng trong R_max cạn kiệt, chỉ nới lỏng bán kính có kiểm soát và gắn cờ cảnh báo rõ ràng.
 */
function find_best_candidate(
    PDO $pdo,
    float $fromLat,
    float $fromLng,
    array $slot,
    array $excludeIds,
    float $maxRadiusKm,
    bool $allowRelaxation = false
): ?array {
    $searchRadius = $maxRadiusKm;
    $box = bounding_box($fromLat, $fromLng, $searchRadius);

    $distanceSql = '(2 * 6371.0088 * ASIN(SQRT('
        . 'POW(SIN((RADIANS(r.latitude) - RADIANS(:lat_1)) / 2), 2)'
        . ' + COS(RADIANS(:lat_2)) * COS(RADIANS(r.latitude))'
        . ' * POW(SIN((RADIANS(r.longitude) - RADIANS(:lng_1)) / 2), 2)'
        . ')))';

    $where = [
        'r.is_active = 1',
        'r.latitude BETWEEN :lat_min AND :lat_max',
        'r.longitude BETWEEN :lng_min AND :lng_max',
    ];
    $bind = [
        ':lat_1' => $fromLat,
        ':lat_2' => $fromLat,
        ':lng_1' => $fromLng,
        ':lat_min' => $box['lat_min'],
        ':lat_max' => $box['lat_max'],
        ':lng_min' => $box['lng_min'],
        ':lng_max' => $box['lng_max'],
        ':radius' => $searchRadius,
    ];

    if (!empty($excludeIds)) {
        $inPlaceholders = [];
        foreach ($excludeIds as $idx => $exId) {
            $key = ':ex_' . $idx;
            $inPlaceholders[] = $key;
            $bind[$key] = (int) $exId;
        }
        $where[] = 'r.id NOT IN (' . implode(',', $inPlaceholders) . ')';
    }

    // Pass 1: Lọc theo các loại hình hợp lệ của slot
    $typeList = array_unique(array_merge([$slot['place_type']], $slot['fallback_types'] ?? []));
    $typePlaceholders = [];
    foreach ($typeList as $tIdx => $tVal) {
        $key = ':t_' . $tIdx;
        $typePlaceholders[] = $key;
        $bind[$key] = $tVal;
    }
    $where[] = 'r.place_type IN (' . implode(',', $typePlaceholders) . ')';

    $sql = "SELECT r.id, r.place_type, r.name, r.address, r.phone, r.latitude AS lat, r.longitude AS lng,
        r.price_min, r.price_max, r.ticket_price, r.estimated_duration, r.best_time_slot,
        r.price_level, r.rating, r.rating_count, r.image_url, r.open_time, r.close_time, r.is_24h,
        c.id AS category_id, c.type_group, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
        {$distanceSql} AS distance_km
        FROM restaurants r
        JOIN categories c ON c.id = r.category_id
        WHERE " . implode(' AND ', $where) . "
        HAVING distance_km <= :radius
        ORDER BY distance_km ASC";

    $stmt = $pdo->prepare($sql);
    foreach ($bind as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nếu không có ứng viên nào trong bán kính R_max
    if (empty($candidates)) {
        if ($allowRelaxation) {
            // Thử nới rộng bán kính dần dần lên 1.5x hoặc 2.0x kèm cờ cảnh báo
            $relaxedRadius = $maxRadiusKm * 1.5;
            $boxRelaxed = bounding_box($fromLat, $fromLng, $relaxedRadius);
            $bind[':lat_min'] = $boxRelaxed['lat_min'];
            $bind[':lat_max'] = $boxRelaxed['lat_max'];
            $bind[':lng_min'] = $boxRelaxed['lng_min'];
            $bind[':lng_max'] = $boxRelaxed['lng_max'];
            $bind[':radius'] = $relaxedRadius;

            $stmtRelaxed = $pdo->prepare($sql);
            foreach ($bind as $k => $v) {
                $stmtRelaxed->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmtRelaxed->execute();
            $candidates = $stmtRelaxed->fetchAll(PDO::FETCH_ASSOC);

            if (empty($candidates)) {
                return null;
            }
        } else {
            return null;
        }
    }

    // =========================================================================
    // HÀM CHẤM ĐIỂM TIỆN ÍCH KHÔNG GIAN ĐA BIẾN (SPATIAL-AWARE UTILITY SCORE)
    // =========================================================================
    $bestCandidate = null;
    $bestScore = -999999.0;
    $preferredCats = $slot['preferred_cats'] ?? [];

    foreach ($candidates as $cand) {
        $dist = (float) $cand['distance_km'];
        $rating = (float) $cand['rating'];
        $catId = (int) $cand['category_id'];
        $pType = (string) $cand['place_type'];

        // 1. Điểm tiệm cận không gian (Spatial Proximity Score, max 40đ)
        // Càng gần điểm trước đó càng đạt điểm cao, phạt mạnh nếu xa
        $normDist = max(0.0, 1.0 - ($dist / max(0.1, $searchRadius)));
        $proximityScore = $normDist * 40.0;

        // 2. Điểm khớp phong cách & danh mục (Vibe & Category Fidelity Score, max 35đ)
        $categoryScore = in_array($catId, $preferredCats, true) ? 35.0 : 0.0;

        // 3. Điểm khớp loại hình chính của slot (Place Type Match, max 15đ)
        $typeScore = ($pType === $slot['place_type']) ? 15.0 : 5.0;

        // 4. Điểm chất lượng uy tín (Rating Score, max 10đ)
        $ratingScore = min(5.0, max(0.0, $rating)) * 2.0;

        $totalScore = $proximityScore + $categoryScore + $typeScore + $ratingScore;

        if ($totalScore > $bestScore) {
            $bestScore = $totalScore;
            $bestCandidate = $cand;
        }
    }

    if ($bestCandidate === null) {
        $bestCandidate = $candidates[0];
    }

    // Chuẩn hóa kiểu dữ liệu
    $distKm = round((float) $bestCandidate['distance_km'], 2);
    $bestCandidate['id'] = (int) $bestCandidate['id'];
    $bestCandidate['lat'] = (float) $bestCandidate['lat'];
    $bestCandidate['lng'] = (float) $bestCandidate['lng'];
    $bestCandidate['price_min'] = (int) $bestCandidate['price_min'];
    $bestCandidate['price_max'] = (int) $bestCandidate['price_max'];
    $bestCandidate['ticket_price'] = (int) ($bestCandidate['ticket_price'] ?? 0);
    $bestCandidate['estimated_duration'] = (int) ($bestCandidate['estimated_duration'] ?? 60);
    $bestCandidate['distance_km'] = $distKm;
    $bestCandidate['rating'] = (float) $bestCandidate['rating'];
    $bestCandidate['rating_count'] = (int) $bestCandidate['rating_count'];

    // Thông tin kiểm soát ràng buộc không gian
    $bestCandidate['is_within_rmax'] = ($distKm <= $maxRadiusKm);
    if (!$bestCandidate['is_within_rmax']) {
        $bestCandidate['spatial_warning'] = sprintf(
            'Khoảng cách chặng (%.2f km) vượt bán kính R_max (%.2f km) do khan hiếm địa điểm khả dụng.',
            $distKm,
            $maxRadiusKm
        );
    }

    $bestCandidate['category'] = [
        'id' => (int) $bestCandidate['category_id'],
        'type_group' => $bestCandidate['type_group'] ?? 'food',
        'name' => $bestCandidate['category_name'],
        'slug' => $bestCandidate['category_slug'],
        'icon' => $bestCandidate['category_icon'],
    ];
    unset(
        $bestCandidate['category_id'],
        $bestCandidate['type_group'],
        $bestCandidate['category_name'],
        $bestCandidate['category_slug'],
        $bestCandidate['category_icon']
    );

    return $bestCandidate;
}

/**
 * Sinh link chỉ đường Google Maps Multi-waypoint.
 */
function build_google_maps_directions_url(float $startLat, float $startLng, array $stops): string
{
    if (empty($stops)) {
        return sprintf('https://www.google.com/maps/search/?api=1&query=%f,%f', $startLat, $startLng);
    }

    $lastStop = end($stops);
    $destLat = $lastStop['place']['lat'];
    $destLng = $lastStop['place']['lng'];

    $waypoints = [];
    for ($i = 0; $i < count($stops) - 1; $i++) {
        $waypoints[] = sprintf('%f,%f', $stops[$i]['place']['lat'], $stops[$i]['place']['lng']);
    }

    $url = sprintf(
        'https://www.google.com/maps/dir/?api=1&origin=%f,%f&destination=%f,%f',
        $startLat,
        $startLng,
        $destLat,
        $destLng
    );

    if (!empty($waypoints)) {
        $url .= '&waypoints=' . urlencode(implode('|', $waypoints));
    }

    return $url;
}

/**
 * Thuật toán chính: Đề xuất lộ trình thông minh nhận thức không gian (Spatial-Aware Itinerary Generator).
 *
 * Kiểm soát tuyệt đối 2 tham số:
 * 1. Phong cách trải nghiệm (Experience Style / Vibe)
 * 2. Bán kính di chuyển tối đa giữa bất kỳ 2 điểm liên tiếp nào (R_max)
 */
function generate_itinerary(
    PDO $pdo,
    float $userLat,
    float $userLng,
    string $duration = 'half_day',
    string $vibe = 'chill',
    float $maxRadiusKm = 8.0
): array {
    $validDurations = ['half_day', 'full_day', 'evening'];
    if (!in_array($duration, $validDurations, true)) {
        $duration = 'half_day';
    }

    $validVibes = ['chill', 'adventure', 'foodie', 'date'];
    if (!in_array($vibe, $validVibes, true)) {
        $vibe = 'chill';
    }

    // Đảm bảo bán kính tối thiểu hợp lý (1.0km đến 25.0km)
    $maxRadiusKm = max(1.0, min(25.0, $maxRadiusKm));

    $template = itinerary_template($duration, $vibe);
    $meta = itinerary_meta($duration, $vibe);

    $stops = [];
    $excludeIds = [];
    $currentLat = $userLat;
    $currentLng = $userLng;

    $totalDistanceKm = 0.0;
    $totalMinutes = 0;
    $totalBudgetMin = 0;
    $maxLegDistanceKm = 0.0;
    $allWithinRmax = true;

    foreach ($template as $slot) {
        // Bước 1: Tìm kiếm nghiêm ngặt trong bán kính R_max (không cho phép relaxation)
        $candidate = find_best_candidate($pdo, $currentLat, $currentLng, $slot, $excludeIds, $maxRadiusKm, false);

        // Bước 2: Nếu khu vực cạn kiệt, mới kích hoạt relaxation có gắn cảnh báo
        if ($candidate === null) {
            $candidate = find_best_candidate($pdo, $currentLat, $currentLng, $slot, $excludeIds, $maxRadiusKm, true);
        }

        if ($candidate !== null) {
            $excludeIds[] = $candidate['id'];

            $legDist = (float) $candidate['distance_km'];
            if ($legDist > $maxRadiusKm) {
                $allWithinRmax = false;
            }
            if ($legDist > $maxLegDistanceKm) {
                $maxLegDistanceKm = $legDist;
            }

            // Tính thời gian di chuyển (trung bình 25km/h trong đô thị + 3 phút chờ đèn đỏ)
            $travelMinutes = max(3, (int) round(($legDist / 25.0) * 60));
            $durationMinutes = $candidate['estimated_duration'] > 0 ? $candidate['estimated_duration'] : 60;

            $totalDistanceKm += $legDist;
            $totalMinutes += ($travelMinutes + $durationMinutes);
            $totalBudgetMin += ($candidate['ticket_price'] + $candidate['price_min']);

            $stops[] = [
                'step' => $slot['step'],
                'action_title' => $slot['title'],
                'time_slot' => $slot['time_slot'],
                'vibe_intent' => $slot['vibe_intent'] ?? '',
                'activity_note' => $slot['activity_note'] ?? '',
                'travel_time_minutes' => $travelMinutes,
                'distance_from_prev_km' => $legDist,
                'is_within_rmax' => $candidate['is_within_rmax'] ?? true,
                'spatial_warning' => $candidate['spatial_warning'] ?? null,
                'place' => $candidate,
            ];

            // Cập nhật vị trí hiện tại cho chặng tiếp theo
            $currentLat = $candidate['lat'];
            $currentLng = $candidate['lng'];
        }
    }

    $googleMapsUrl = build_google_maps_directions_url($userLat, $userLng, $stops);

    return [
        'summary' => [
            'title' => $meta['title'],
            'duration' => $duration,
            'duration_label' => $meta['duration_label'],
            'vibe' => $vibe,
            'vibe_label' => $meta['vibe_label'],
            'total_stops' => count($stops),
            'total_distance_km' => round($totalDistanceKm, 2),
            'total_time_minutes' => $totalMinutes,
            'estimated_budget_min' => $totalBudgetMin,
            'max_radius_constraint_km' => $maxRadiusKm,
            'max_leg_distance_km' => round($maxLegDistanceKm, 2),
            'is_strict_radius_satisfied' => $allWithinRmax,
            'starting_point' => [
                'lat' => $userLat,
                'lng' => $userLng,
            ],
            'google_maps_url' => $googleMapsUrl,
        ],
        'stops' => $stops,
    ];
}

/**
 * Task BE-04: Đổi chặng đơn lẻ (Reroll a single stop) có nhận thức không gian.
 */
function reroll_itinerary_stop(
    PDO $pdo,
    int $currentStopId,
    array $excludeIds,
    float $fromLat,
    float $fromLng,
    string $placeType = 'food',
    string $vibe = 'chill',
    float $maxRadiusKm = 8.0
): ?array {
    $excludeIds[] = $currentStopId;

    $preferredCats = [1, 2, 4, 7];
    if ($placeType === 'tourism') {
        $preferredCats = ($vibe === 'date') ? [8, 11] : [9, 10, 11];
    } elseif ($placeType === 'entertainment') {
        $preferredCats = ($vibe === 'adventure') ? [12, 14] : [13, 14, 15];
    } else {
        if ($vibe === 'foodie') {
            $preferredCats = [1, 2, 4, 5];
        } elseif ($vibe === 'date') {
            $preferredCats = [4, 7];
        }
    }

    $slot = [
        'step' => 0,
        'title' => 'Chặng thay thế',
        'time_slot' => '',
        'place_type' => $placeType,
        'preferred_cats' => $preferredCats,
        'fallback_types' => [$placeType],
    ];

    $candidate = find_best_candidate($pdo, $fromLat, $fromLng, $slot, $excludeIds, $maxRadiusKm, false);

    if ($candidate === null) {
        $candidate = find_best_candidate($pdo, $fromLat, $fromLng, $slot, $excludeIds, $maxRadiusKm, true);
    }

    return $candidate;
}
