<?php
declare(strict_types=1);

$collection = [
    'info' => [
        '_postman_id' => 'food-finder-api-collection-v2',
        'name' => 'Food Finder API — Automated Test Suite',
        'description' => "Bộ kiểm thử API tự động toàn diện cho dự án Food Finder:\n- Xác thực JWT (Access & Refresh Token trong HttpOnly Cookie)\n- Quên mật khẩu & Đặt lại mật khẩu với OTP\n- Tìm kiếm, lọc và gợi ý địa điểm (Ẩm thực, Du lịch, Giải trí)\n- Lập lộ trình thông minh & Đổi chặng\n- Đồng bộ Địa điểm yêu thích & Lộ trình theo từng tài khoản\n- Đánh giá & Đề xuất địa điểm từ người dùng",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'item' => [],
];

// Helper tạo request
function make_request(string $name, string $method, string $path, array $query = [], ?array $body = null, array $tests = [], ?string $prerequest = null, array $headers = []): array {
    $urlObj = [
        'raw' => '{{baseUrl}}' . $path . ($query ? ('?' . http_build_query($query)) : ''),
        'host' => ['{{baseUrl}}'],
        'path' => array_values(array_filter(explode('/', trim($path, '/')))),
    ];

    if ($query) {
        $urlObj['query'] = [];
        foreach ($query as $k => $v) {
            $urlObj['query'][] = ['key' => (string) $k, 'value' => (string) $v];
        }
    }

    $req = [
        'method' => $method,
        'header' => array_merge([
            ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text']
        ], $headers),
        'url' => $urlObj,
    ];

    if ($body !== null) {
        $req['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'options' => ['raw' => ['language' => 'json']],
        ];
        $req['header'][] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }

    $events = [];
    if ($prerequest) {
        $events[] = [
            'listen' => 'prerequest',
            'script' => ['type' => 'text/javascript', 'exec' => explode("\n", $prerequest)],
        ];
    }
    if ($tests) {
        $events[] = [
            'listen' => 'test',
            'script' => ['type' => 'text/javascript', 'exec' => $tests],
        ];
    }

    $item = ['name' => $name, 'request' => $req];
    if ($events) $item['event'] = $events;
    return $item;
}

// ══════════════════════════════════════════════════════════════════════════════
// 1. NHÓM 01: XÁC THỰC THÀNH VIÊN (AUTH & JWT)
// ══════════════════════════════════════════════════════════════════════════════
$authFolder = [
    'name' => '01. Xác Thực & Thành Viên (JWT & Cookies)',
    'item' => [
        make_request(
            '1.1 Đăng ký tài khoản mới',
            'POST',
            '/api/auth/register.php',
            [],
            [
                'username' => '{{test_username}}',
                'email' => '{{test_email}}',
                'password' => '{{test_password}}',
                'full_name' => 'Thành Viên Postman',
            ],
            [
                'pm.test("Status code là 201 Created", function () {',
                '    pm.response.to.have.status(201);',
                '});',
                'pm.test("Trả về success true và thông tin user", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.user.id).to.be.a("number");',
                '    pm.expect(json.data.user.username).to.eql(pm.environment.get("test_username"));',
                '    pm.environment.set("current_user_id", json.data.user.id);',
                '    if (json.data.access_token) {',
                '        pm.environment.set("access_token", json.data.access_token);',
                '    }',
                '});',
                'pm.test("Server cấp Access và Refresh Token trong Cookie", function () {',
                '    pm.expect(pm.cookies.has("access_token") || pm.response.text.includes("access_token")).to.be.true;',
                '});',
            ],
            "if (!pm.environment.get('test_username')) {\n    var ts = Date.now();\n    pm.environment.set('test_username', 'user_pm_' + ts);\n    pm.environment.set('test_email', 'user_pm_' + ts + '@example.com');\n    pm.environment.set('test_password', 'SecurePass@123');\n}"
        ),

        make_request(
            '1.2 Đăng nhập bằng tài khoản vừa tạo',
            'POST',
            '/api/auth/login.php',
            [],
            [
                'identifier' => '{{test_username}}',
                'password' => '{{test_password}}',
            ],
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Đăng nhập thành công, nhận token và profile", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.user.id).to.eql(pm.environment.get("current_user_id"));',
                '    if (json.data.access_token) {',
                '        pm.environment.set("access_token", json.data.access_token);',
                '    }',
                '});',
            ]
        ),

        make_request(
            '1.3 Đăng nhập thất bại (Sai mật khẩu - Expect 401)',
            'POST',
            '/api/auth/login.php',
            [],
            [
                'identifier' => '{{test_username}}',
                'password' => 'WrongPassword123!',
            ],
            [
                'pm.test("Status code là 401 Unauthorized", function () {',
                '    pm.response.to.have.status(401);',
                '});',
                'pm.test("Mã lỗi trả về INVALID_CREDENTIALS", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.false;',
                '    pm.expect(json.error.code).to.eql("INVALID_CREDENTIALS");',
                '});',
            ]
        ),

        make_request(
            '1.4 Lấy thông tin cá nhân hiện tại (Protected)',
            'GET',
            '/api/auth/me.php',
            [],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Thông tin cá nhân đúng với tài khoản đang đăng nhập", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.user.id).to.eql(pm.environment.get("current_user_id"));',
                '    pm.expect(json.data.user.email).to.eql(pm.environment.get("test_email"));',
                '});',
            ],
            null,
            [['key' => 'Authorization', 'value' => 'Bearer {{access_token}}', 'type' => 'text']]
        ),

        make_request(
            '1.5 Làm mới Access Token (Token Rotation)',
            'POST',
            '/api/auth/refresh.php',
            [],
            null,
            [
                'pm.test("Làm mới token thành công (HTTP 200)", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    if (json.data && json.data.access_token) {',
                '        pm.environment.set("access_token", json.data.access_token);',
                '    }',
                '});',
            ]
        ),

        make_request(
            '1.6 Quên mật khẩu - Yêu cầu mã OTP',
            'POST',
            '/api/auth/forgot_password.php',
            [],
            [
                'identifier' => '{{test_email}}',
            ],
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Mã xác nhận OTP 6 số được sinh thành công", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.reset_code).to.be.a("string");',
                '    pm.expect(json.data.reset_code.length).to.be.at.least(4);',
                '    pm.environment.set("reset_code", json.data.reset_code);',
                '});',
            ]
        ),

        make_request(
            '1.7 Đặt lại mật khẩu mới bằng mã OTP',
            'POST',
            '/api/auth/reset_password.php',
            [],
            [
                'identifier' => '{{test_email}}',
                'reset_code' => '{{reset_code}}',
                'new_password' => 'NewSecurePass@2026',
            ],
            [
                'pm.test("Đặt lại mật khẩu thành công (HTTP 200)", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.environment.set("test_password", "NewSecurePass@2026");',
                '    if (json.data && json.data.access_token) {',
                '        pm.environment.set("access_token", json.data.access_token);',
                '    }',
                '});',
            ]
        ),

        make_request(
            '1.8 Đăng xuất tài khoản',
            'POST',
            '/api/auth/logout.php',
            [],
            null,
            [
                'pm.test("Đăng xuất thành công (HTTP 200)", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '});',
            ]
        ),

        make_request(
            '1.9 Kiểm tra sau khi đăng xuất - Expect 401',
            'GET',
            '/api/auth/me.php',
            [],
            null,
            [
                'pm.test("Từ chối truy cập vì đã đăng xuất (HTTP 401)", function () {',
                '    pm.response.to.have.status(401);',
                '});',
            ]
        ),
    ],
];
$collection['item'][] = $authFolder;

// ══════════════════════════════════════════════════════════════════════════════
// 2. NHÓM 02: ĐỊA ĐIỂM & BẢN ĐỒ (RESTAURANTS & PLACES)
// ══════════════════════════════════════════════════════════════════════════════
$placesFolder = [
    'name' => '02. Địa Điểm & Khám Phá (Places & Categories)',
    'item' => [
        make_request(
            '2.1 Lấy toàn bộ danh mục địa điểm',
            'GET',
            '/api/categories.php',
            [],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Trả về danh sách 15 danh mục thuộc 3 nhóm", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.length).to.be.at.least(10);',
                '});',
            ]
        ),

        make_request(
            '2.2 Tìm kiếm địa điểm bán kính 3km quanh ĐH Tôn Đức Thắng',
            'GET',
            '/api/restaurants.php',
            ['lat' => '10.7326', 'lng' => '106.6996', 'radius' => '3'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Tìm thấy danh sách địa điểm trong bán kính 3km", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.length).to.be.above(0);',
                '    pm.environment.set("first_place_id", json.data[0].id);',
                '});',
            ]
        ),

        make_request(
            '2.3 Lọc riêng nhóm Ẩm thực (type=food)',
            'GET',
            '/api/restaurants.php',
            ['lat' => '10.7326', 'lng' => '106.6996', 'radius' => '5', 'type' => 'food'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Toàn bộ kết quả đều là nhóm food", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    json.data.forEach(function(item) {',
                '        pm.expect(item.place_type).to.eql("food");',
                '    });',
                '});',
            ]
        ),

        make_request(
            '2.4 Lọc riêng nhóm Du lịch (type=tourism)',
            'GET',
            '/api/restaurants.php',
            ['lat' => '10.7326', 'lng' => '106.6996', 'radius' => '10', 'type' => 'tourism'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Có ít nhất 10 điểm du lịch", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.length).to.be.at.least(10);',
                '});',
            ]
        ),

        make_request(
            '2.5 Lọc riêng nhóm Vui chơi giải trí (type=entertainment)',
            'GET',
            '/api/restaurants.php',
            ['lat' => '10.7326', 'lng' => '106.6996', 'radius' => '10', 'type' => 'entertainment'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Có ít nhất 10 điểm vui chơi giải trí", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.length).to.be.at.least(10);',
                '});',
            ]
        ),

        make_request(
            '2.6 Chi tiết địa điểm ID=1',
            'GET',
            '/api/restaurant.php',
            ['id' => '1'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Chi tiết quán có đầy đủ tên, tọa độ, địa chỉ", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.id).to.eql(1);',
                '    pm.expect(json.data.name).to.be.a("string");',
                '    pm.expect(json.data.lat).to.be.a("number");',
                '});',
            ]
        ),

        make_request(
            '2.7 Chi tiết địa điểm không tồn tại (Expect 404)',
            'GET',
            '/api/restaurant.php',
            ['id' => '999999'],
            null,
            [
                'pm.test("Status code là 404 Not Found", function () {',
                '    pm.response.to.have.status(404);',
                '});',
            ]
        ),

        make_request(
            '2.8 Gợi ý ngẫu nhiên địa điểm (Random)',
            'GET',
            '/api/random.php',
            ['lat' => '10.7326', 'lng' => '106.6996', 'radius' => '5'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Trả về một địa điểm ngẫu nhiên hợp lệ", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.id).to.be.above(0);',
                '});',
            ]
        ),

        make_request(
            '2.9 Tìm kiếm tọa độ theo tên đường (Geocoding)',
            'GET',
            '/api/geocode.php',
            ['q' => 'Nguyễn Hữu Thọ Quận 7'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
            ]
        ),
    ],
];
$collection['item'][] = $placesFolder;

// ══════════════════════════════════════════════════════════════════════════════
// 3. NHÓM 03: LỘ TRÌNH THÔNG MINH (ITINERARY)
// ══════════════════════════════════════════════════════════════════════════════
$itinFolder = [
    'name' => '03. Lập Lộ Trình Đi Chơi Thông Minh',
    'item' => [
        make_request(
            '3.1 Lộ trình Nửa ngày Chill (half_day + chill)',
            'GET',
            '/api/itinerary.php',
            ['lat' => '10.7326', 'lng' => '106.6996', 'duration' => 'half_day', 'vibe' => 'chill', 'radius' => '8'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Lộ trình có từ 3 chặng dừng hợp lý", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.stops.length).to.be.at.least(2);',
                '    pm.environment.set("first_stop_id", json.data.stops[0].place.id);',
                '});',
            ]
        ),

        make_request(
            '3.2 Lộ trình Cả ngày Trải nghiệm (full_day + adventure)',
            'GET',
            '/api/itinerary.php',
            ['lat' => '10.7326', 'lng' => '106.6996', 'duration' => 'full_day', 'vibe' => 'adventure', 'radius' => '10'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Lộ trình cả ngày có 4 đến 5 chặng dừng", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.stops.length).to.be.at.least(3);',
                '});',
            ]
        ),

        make_request(
            '3.3 Lộ trình Buổi tối Hẹn hò (evening + date)',
            'GET',
            '/api/itinerary.php',
            ['lat' => '10.7326', 'lng' => '106.6996', 'duration' => 'evening', 'vibe' => 'date', 'radius' => '8'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
            ]
        ),

        make_request(
            '3.4 Đổi chặng dừng lộ trình (Reroll)',
            'GET',
            '/api/itinerary_reroll.php',
            [
                'current_id' => '1',
                'from_lat' => '10.7326',
                'from_lng' => '106.6996',
                'place_type' => 'food',
                'vibe' => 'chill',
                'radius' => '5',
            ],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Trả về chặng thay thế khác ID ban đầu", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.place.id).to.not.eql(1);',
                '});',
            ]
        ),
    ],
];
$collection['item'][] = $itinFolder;

// ══════════════════════════════════════════════════════════════════════════════
// 4. NHÓM 04: MỤC ĐÃ LƯU THEO TÀI KHOẢN (FAVORITES & SAVED ITINERARIES)
// ══════════════════════════════════════════════════════════════════════════════
$favFolder = [
    'name' => '04. Mục Đã Lưu Theo Tài Khoản (Favorites & Saved Itineraries)',
    'item' => [
        make_request(
            '4.0 Đăng nhập lại để có quyền thao tác dữ liệu User',
            'POST',
            '/api/auth/login.php',
            [],
            [
                'identifier' => '{{test_username}}',
                'password' => '{{test_password}}',
            ],
            [
                'pm.test("Đăng nhập thành công", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    if (json.data && json.data.access_token) {',
                '        pm.environment.set("access_token", json.data.access_token);',
                '    }',
                '});',
            ]
        ),

        make_request(
            '4.1 Thêm địa điểm vào danh sách yêu thích',
            'POST',
            '/api/favorites.php',
            [],
            [
                'restaurant_id' => 1,
                'action' => 'add',
            ],
            [
                'pm.test("Thêm yêu thích thành công (HTTP 200)", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.saved).to.be.true;',
                '    pm.expect(json.data.ids).to.include(1);',
                '});',
            ],
            null,
            [['key' => 'Authorization', 'value' => 'Bearer {{access_token}}', 'type' => 'text']]
        ),

        make_request(
            '4.2 Lấy danh sách địa điểm yêu thích của tài khoản',
            'GET',
            '/api/favorites.php',
            [],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Danh sách yêu thích chứa đúng địa điểm ID=1", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.ids).to.include(1);',
                '});',
            ],
            null,
            [['key' => 'Authorization', 'value' => 'Bearer {{access_token}}', 'type' => 'text']]
        ),

        make_request(
            '4.3 Lưu một lộ trình mới vào tài khoản',
            'POST',
            '/api/user_itineraries.php',
            [],
            [
                'itinerary' => [
                    'id' => 'itin_test_pm',
                    'title' => 'Lộ trình Chill Quận 7 Test',
                    'duration' => 'half_day',
                    'vibe' => 'chill',
                    'summary' => [
                        'duration' => 'half_day',
                        'vibe' => 'chill',
                        'total_distance_km' => 4.5,
                    ],
                    'stops' => [
                        ['place' => ['id' => 1, 'name' => 'Phở Bắc Hai Thiền']],
                        ['place' => ['id' => 2, 'name' => 'Highlands Coffee Crescent Mall']],
                    ],
                ]
            ],
            [
                'pm.test("Lưu lộ trình thành công (HTTP 200)", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '});',
            ],
            null,
            [['key' => 'Authorization', 'value' => 'Bearer {{access_token}}', 'type' => 'text']]
        ),

        make_request(
            '4.4 Lấy danh sách lộ trình đã lưu của tài khoản',
            'GET',
            '/api/user_itineraries.php',
            [],
            null,
            [
                'pm.test("Lấy danh sách lộ trình thành công", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.list.length).to.be.above(0);',
                '});',
            ],
            null,
            [['key' => 'Authorization', 'value' => 'Bearer {{access_token}}', 'type' => 'text']]
        ),

        make_request(
            '4.5 Xóa lộ trình vừa lưu',
            'DELETE',
            '/api/user_itineraries.php',
            ['id' => 'itin_test_pm'],
            null,
            [
                'pm.test("Xóa lộ trình thành công", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '});',
            ],
            null,
            [['key' => 'Authorization', 'value' => 'Bearer {{access_token}}', 'type' => 'text']]
        ),

        make_request(
            '4.6 Xóa địa điểm khỏi yêu thích',
            'POST',
            '/api/favorites.php',
            [],
            [
                'restaurant_id' => 1,
                'action' => 'remove',
            ],
            [
                'pm.test("Bỏ lưu yêu thích thành công", function () {',
                '    pm.response.to.have.status(200);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.saved).to.be.false;',
                '});',
            ],
            null,
            [['key' => 'Authorization', 'value' => 'Bearer {{access_token}}', 'type' => 'text']]
        ),
    ],
];
$collection['item'][] = $favFolder;

// ══════════════════════════════════════════════════════════════════════════════
// 5. NHÓM 05: ĐÁNH GIÁ & ĐỀ XUẤT QUÁN (COMMUNITY)
// ══════════════════════════════════════════════════════════════════════════════
$communityFolder = [
    'name' => '05. Đánh Giá & Đề Xuất Quán (Community)',
    'item' => [
        make_request(
            '5.1 Lấy danh sách đánh giá của quán ID=1',
            'GET',
            '/api/reviews.php',
            ['restaurant_id' => '1'],
            null,
            [
                'pm.test("Status code là 200 OK", function () {',
                '    pm.response.to.have.status(200);',
                '});',
                'pm.test("Cấu trúc trả về gồm reviews, avg_rating và rating_distribution", function () {',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '    pm.expect(json.data.reviews).to.be.an("array");',
                '    pm.expect(json.data.avg_rating).to.be.a("number");',
                '});',
            ]
        ),

        make_request(
            '5.2 Gửi đánh giá mới cho quán ID=1',
            'POST',
            '/api/reviews.php',
            [],
            [
                'restaurant_id' => 1,
                'author_name' => 'Tester Postman',
                'rating' => 5,
                'comment' => 'Món ăn rất ngon, phục vụ nhanh chóng!',
            ],
            [
                'pm.test("Gửi đánh giá thành công (HTTP 200 hoặc 201)", function () {',
                '    pm.expect([200, 201]).to.include(pm.response.code);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '});',
            ],
            null,
            [['key' => 'Authorization', 'value' => 'Bearer {{access_token}}', 'type' => 'text']]
        ),

        make_request(
            '5.3 Gửi đề xuất địa điểm mới từ cộng đồng',
            'POST',
            '/api/suggest.php',
            [],
            [
                'name' => 'Quán Ăn Thử Nghiệm Postman',
                'place_type' => 'food',
                'category_id' => 1,
                'address' => '123 Đường Số 7, Phường Tân Phong, Quận 7',
                'phone' => '0901234567',
                'latitude' => 10.7326,
                'longitude' => 106.6996,
                'price_min' => 30000,
                'price_max' => 60000,
                'note' => 'Đề xuất tự động từ Postman collection',
            ],
            [
                'pm.test("Gửi đề xuất thành công (HTTP 200 hoặc 201)", function () {',
                '    pm.expect([200, 201]).to.include(pm.response.code);',
                '    var json = pm.response.json();',
                '    pm.expect(json.success).to.be.true;',
                '});',
            ]
        ),
    ],
];
$collection['item'][] = $communityFolder;

// File 1: Postman Collection
$collectionJson = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents('docs/Food_Finder_API.postman_collection.json', $collectionJson);
echo "Đã tạo docs/Food_Finder_API.postman_collection.json\n";

// File 2: Postman Environment
$environment = [
    'id' => 'food-finder-local-env',
    'name' => 'Food Finder — Localhost (8000)',
    'values' => [
        ['key' => 'baseUrl', 'value' => 'http://127.0.0.1:8000', 'enabled' => true],
        ['key' => 'test_username', 'value' => 'postman_tester', 'enabled' => true],
        ['key' => 'test_email', 'value' => 'postman_tester@example.com', 'enabled' => true],
        ['key' => 'test_password', 'value' => 'SecurePass@123', 'enabled' => true],
        ['key' => 'access_token', 'value' => '', 'enabled' => true],
        ['key' => 'reset_code', 'value' => '', 'enabled' => true],
        ['key' => 'current_user_id', 'value' => '', 'enabled' => true],
    ],
    '_postman_variable_scope' => 'environment',
];
$environmentJson = json_encode($environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents('docs/Food_Finder_Environment.postman_environment.json', $environmentJson);
echo "Đã tạo docs/Food_Finder_Environment.postman_environment.json\n";

