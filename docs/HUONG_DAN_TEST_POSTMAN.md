# 🚀 Hướng Dẫn Kiểm Thử Toàn Diện API Bằng Postman — Food Finder

Tài liệu này hướng dẫn cách sử dụng bộ **Postman Collection & Environment** đã được tạo sẵn để kiểm thử toàn bộ 100% các API của hệ thống Food Finder một cách tự động, nhanh chóng và chính xác.

---

## 📂 1. Các file cấu hình đã chuẩn bị sẵn

Trong thư mục `docs/`, bạn sẽ thấy 2 file chính:

1. **`docs/Food_Finder_API.postman_collection.json`**:
   - Chứa **28 kịch bản API** chi tiết được nhóm thành 5 module nghiệp vụ.
   - Mỗi request đều được viết sẵn các bài kiểm tra tự động (`pm.test` assertions): kiểm tra Status Code (200, 201, 401, 404), cấu trúc dữ liệu JSON, kiểu dữ liệu, v.v.
   - Có kịch bản kiểm tra cả các trường hợp lỗi (Negative Test) như sai mật khẩu, tài khoản không tồn tại.

2. **`docs/Food_Finder_Environment.postman_environment.json`**:
   - Chứa các biến môi trường: `baseUrl` (`http://127.0.0.1:8000`), `access_token`, `test_username`, `test_password`, `reset_code`.

---

## 📋 2. Danh mục 28 API được kiểm thử

### Thư mục 01: Xác thực & Thành viên (JWT & Cookies)
* `1.1 Đăng ký tài khoản mới` (`POST /api/auth/register.php`): Tự động sinh user mới, kiểm tra mã 201 và lưu token.
* `1.2 Đăng nhập bằng tài khoản vừa tạo` (`POST /api/auth/login.php`): Kiểm tra mã 200 và cấp cookie.
* `1.3 Đăng nhập thất bại (Sai mật khẩu)` (`POST /api/auth/login.php`): Kỳ vọng mã 401 `INVALID_CREDENTIALS`.
* `1.4 Lấy thông tin cá nhân hiện tại` (`GET /api/auth/me.php`): Đọc token JWT và xác minh profile.
* `1.5 Làm mới Access Token (Token Rotation)` (`POST /api/auth/refresh.php`): Kiểm tra xoay vòng Refresh Token.
* `1.6 Quên mật khẩu - Yêu cầu mã OTP` (`POST /api/auth/forgot_password.php`): Tự động lấy mã OTP 6 số.
* `1.7 Đặt lại mật khẩu mới bằng mã OTP` (`POST /api/auth/reset_password.php`): Cập nhật mật khẩu và tự động đăng nhập.
* `1.8 Đăng xuất tài khoản` (`POST /api/auth/logout.php`): Thu hồi token và dọn dẹp cookie.
* `1.9 Kiểm tra sau khi đăng xuất` (`GET /api/auth/me.php`): Kỳ vọng mã 401 Unauthorized.

### Thư mục 02: Địa Điểm & Khám Phá
* `2.1 Lấy toàn bộ danh mục địa điểm` (`GET /api/categories.php`)
* `2.2 Tìm kiếm địa điểm bán kính 3km` (`GET /api/restaurants.php?lat=...&lng=...&radius=3`)
* `2.3 Lọc riêng nhóm Ẩm thực` (`GET /api/restaurants.php?type=food`)
* `2.4 Lọc riêng nhóm Du lịch` (`GET /api/restaurants.php?type=tourism`)
* `2.5 Lọc riêng nhóm Vui chơi giải trí` (`GET /api/restaurants.php?type=entertainment`)
* `2.6 Chi tiết địa điểm ID=1` (`GET /api/restaurant.php?id=1`)
* `2.7 Chi tiết địa điểm không tồn tại` (`GET /api/restaurant.php?id=999999`): Kỳ vọng mã 404 Not Found.
* `2.8 Gợi ý ngẫu nhiên địa điểm` (`GET /api/random.php`)
* `2.9 Tìm kiếm tọa độ theo tên đường (Geocoding)` (`GET /api/geocode.php?q=...`)

### Thư mục 03: Lập Lộ Trình Đi Chơi Thông Minh
* `3.1 Lộ trình Nửa ngày Chill` (`GET /api/itinerary.php?duration=half_day&vibe=chill`)
* `3.2 Lộ trình Cả ngày Trải nghiệm` (`GET /api/itinerary.php?duration=full_day&vibe=adventure`)
* `3.3 Lộ trình Buổi tối Hẹn hò` (`GET /api/itinerary.php?duration=evening&vibe=date`)
* `3.4 Đổi chặng dừng lộ trình (Reroll)` (`GET /api/itinerary_reroll.php`)

### Thư mục 04: Mục Đã Lưu Theo Tài Khoản (Favorites)
* `4.0 Đăng nhập lại để có quyền thao tác` (`POST /api/auth/login.php`)
* `4.1 Thêm địa điểm vào danh sách yêu thích` (`POST /api/favorites.php`)
* `4.2 Lấy danh sách địa điểm yêu thích của tài khoản` (`GET /api/favorites.php`)
* `4.3 Lưu một lộ trình mới vào tài khoản` (`POST /api/user_itineraries.php`)
* `4.4 Lấy danh sách lộ trình đã lưu của tài khoản` (`GET /api/user_itineraries.php`)
* `4.5 Xóa lộ trình vừa lưu` (`DELETE /api/user_itineraries.php?id=...`)
* `4.6 Xóa địa điểm khỏi yêu thích` (`POST /api/favorites.php`)

### Thư mục 05: Đánh Giá & Đề Xuất Quán (Community)
* `5.1 Lấy danh sách đánh giá của quán ID=1` (`GET /api/reviews.php?restaurant_id=1`)
* `5.2 Gửi đánh giá mới cho quán ID=1` (`POST /api/reviews.php`)
* `5.3 Gửi đề xuất địa điểm mới từ cộng đồng` (`POST /api/suggest.php`)

---

## 🛠️ 3. Hướng dẫn 3 bước chạy kiểm thử trong Postman

### Bước 1: Khởi động Server Food Finder
Mở terminal tại thư mục gốc của dự án và chạy:
```powershell
.\start_server.bat
```
Hoặc:
```powershell
php -S 127.0.0.1:8000 -t public
```
*(Đảm bảo server đang chạy tại `http://127.0.0.1:8000`)*

### Bước 2: Import vào Postman
1. Mở ứng dụng **Postman** trên máy tính.
2. Bấm vào nút **`Import`** ở góc trên cùng bên trái (cạnh logo Postman).
3. Kéo và thả 2 file sau vào cửa sổ Import:
   - `d:\Finder\docs\Food_Finder_API.postman_collection.json`
   - `d:\Finder\docs\Food_Finder_Environment.postman_environment.json`
4. Postman sẽ hiển thị collection: **`Food Finder API — Automated Test Suite`**.

### Bước 3: Chọn Môi trường & Chạy Test tự động (Runner)
1. Ở góc trên cùng bên phải của Postman, mở dropdown chọn Environment và chọn:  
   👉 **`Food Finder — Localhost (8000)`**
2. Nhấp chuột phải (hoặc bấm dấu `...`) vào tên Collection **`Food Finder API — Automated Test Suite`**.
3. Chọn **`Run collection`** (hoặc bấm nút **Run** ở góc trên).
4. Bấm nút màu cam **`Run Food Finder API — Automated Test Suite`**.

---

## 📊 4. Kết quả nghiệm thu
Toàn bộ 28 API sẽ được Postman gửi request tuần tự trong vòng **2 đến 4 giây**:
* Tất cả các dòng test script sẽ hiển thị nhãn **`PASS`** màu xanh lá cây 🟢 (Status code đúng, JSON schema chuẩn, token được cấp và xoay vòng chính xác).
* Không có lỗi đỏ (0 Failures).

