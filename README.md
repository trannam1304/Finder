# Food Finder 🍜

Ứng dụng web tìm kiếm địa điểm ăn uống, vui chơi và du lịch thông minh quanh vị trí người dùng. Hỗ trợ xem trên bản đồ tương tác Leaflet/OpenStreetMap, tìm kiếm & bộ lọc đa tiêu chí (danh mục, khoảng cách, mức giá), gợi ý ngẫu nhiên ("Hôm nay ăn gì?"), lập lộ trình trải nghiệm và trang quản trị Admin CMS hoàn chỉnh.

Dự án được xây dựng với kiến trúc **Hybrid Database**: Hỗ trợ kết nối đám mây **TiDB Cloud Serverless (AWS Singapore qua TLS)** và **MySQL Localhost (XAMPP)**, xác thực thành viên bảo mật bằng **JWT & HttpOnly Cookie**, kèm bộ kiểm thử API tự động với **Postman**.

---

## ☁️ Trạng thái Cơ sở dữ liệu (Database Status)

Hiện tại dự án **ĐÃ ĐƯỢC CHUYỂN HOÀN TOÀN LÊN CLOUD (TiDB Cloud Serverless)**:
- **Cloud Host**: `gateway01.ap-southeast-1.prod.aws.tidbcloud.com:4000`
- **Vùng máy chủ**: AWS ap-southeast-1 (Singapore)
- **Bảo mật**: TLS 1.2 / 1.3 qua giao thức PDO MySQL SSL
- **Cơ sở dữ liệu**: `food_finder` (đã nạp sẵn 15 danh mục, 50 địa điểm thực tế quanh TP.HCM và tài khoản Admin)

> [!NOTE]
> **Bạn không cần bật MySQL trong XAMPP nữa!** Máy chủ PHP sẽ truy vấn trực tiếp cơ sở dữ liệu trên TiDB Cloud. Tuy nhiên, dữ liệu MySQL ở máy local vẫn được giữ nguyên độc lập như một bản backup.

### Chuyển đổi qua lại giữa Cloud và Local (Chỉ 1 lệnh)
- **Chuyển sang TiDB Cloud:**
  ```powershell
  php connect_tidb.php "d1XN05kLUwUdlFLe"
  ```
- **Chuyển về MySQL Localhost (Offline):**
  ```powershell
  php connect_tidb.php local
  ```

---

## 🚀 Hướng dẫn khởi chạy nhanh (30 giây)

### Bước 1: Khởi động Web Server
Chạy lệnh sau tại thư mục gốc của dự án:
```powershell
php -S localhost:8000 -t public
```
*(Hoặc nhấp đúp vào file `start_server.bat` trên Windows)*

### Bước 2: Truy cập ứng dụng
- **Trang người dùng:** [http://localhost:8000](http://localhost:8000)
- **Trang khám phá bản đồ:** [http://localhost:8000/explore.html](http://localhost:8000/explore.html)
- **Gợi ý & Lập lộ trình:** [http://localhost:8000/suggest.html](http://localhost:8000/suggest.html)
- **Đăng nhập / Đăng ký:** [http://localhost:8000/login.html](http://localhost:8000/login.html)
- **Trang quản trị Admin:** [http://localhost:8000/admin.html](http://localhost:8000/admin.html)
  - Tài khoản Admin: `admin`
  - Mật khẩu: `Admin@123456`

---

## 🌟 Các tính năng nổi bật

### 1. Khám phá & Lập lộ trình thông minh
- **Tìm kiếm đa dạng:** 3 nhóm lớn gồm **Ăn uống (Food)**, **Du lịch (Tourism)** và **Vui chơi giải trí (Entertainment)** với 15 danh mục chi tiết.
- **Bản đồ tương tác OpenStreetMap:** Tự động định vị GPS, vẽ bán kính tìm kiếm (1km, 3km, 5km, 10km), ghim vị trí trực quan, không yêu cầu API key bản quyền.
- **Hôm nay ăn gì & Lập lịch trình:** Tự động tính toán lộ trình 2 chặng (Sáng - Trưa, Chiều - Tối, Hẹn hò) kèm tính năng đổi chặng (Reroll) thông minh.

### 2. Xác thực JWT & Bảo mật Người dùng
- **Kiến trúc JWT không dùng PHP Session truyền thống:**
  - `Access Token` ngắn hạn (15 phút) cấp qua JSON Response.
  - `Refresh Token` dài hạn (7 ngày) bảo mật qua `HttpOnly`, `SameSite=Lax` Cookie.
  - Cơ chế **Refresh Token Rotation**: Mỗi lần đổi token mới, token cũ bị thu hồi ngay lập tức để chống Replay Attack.
- **Quên mật khẩu & Đặt lại mật khẩu:**
  - Quy trình 2 bước: Nhập email/username -> Nhận mã OTP 6 số (hạn 15 phút) -> Xác thực OTP và nhập mật khẩu mới.

### 3. Đồng bộ Dữ liệu Cá nhân theo Tài khoản (User-Scoped Storage)
- **Thành viên đăng nhập:** Địa điểm yêu thích và lộ trình đã lưu được lưu vào database Cloud theo `user_id`, đồng bộ tức thì trên mọi thiết bị.
- **Khách vãng lai (Guest):** Tự động xóa sạch dữ liệu lưu tạm sau mỗi lần tải lại trang (reload), đảm bảo quyền riêng tư và tránh lẫn lộn dữ liệu giữa các tài khoản.

### 4. Quản trị viên CMS Admin
- Quản lý danh sách địa điểm: Thêm mới, chỉnh sửa, xóa, tìm kiếm theo tên/danh mục.
- Chọn tọa độ trực quan bằng cách kéo thả Marker trên bản đồ.
- Quản lý đề xuất địa điểm từ cộng đồng (Duyệt / Từ chối).

---

## 🧪 Kiểm thử Tự động (Testing Suite)

### 1. Kiểm thử toàn bộ Endpoint HTTP bằng CLI (24 Test Cases)
Chạy script kiểm thử hồi quy tích hợp:
```powershell
php docs/test_all_http_endpoints.php
```
*Kết quả: 24/24 PASS (Trang giao diện, REST API, tìm kiếm, lộ trình, đánh giá, bảo mật session).*

### 2. Kiểm thử API bằng Postman (28 Test Cases)
Thư viện Postman hoàn chỉnh có sẵn trong thư mục dự án:
- Collection: [Food_Finder_API.postman_collection.json](file:///d:/Finder/Food_Finder_API.postman_collection.json)
- Environment: [Food_Finder_Environment.postman_environment.json](file:///d:/Finder/Food_Finder_Environment.postman_environment.json)

Xem hướng dẫn chi tiết tại [docs/HUONG_DAN_TEST_POSTMAN.md](file:///d:/Finder/docs/HUONG_DAN_TEST_POSTMAN.md).

---

## 📁 Cấu trúc Thư mục Dự án

```
Finder/
├── app/                              # Mã nguồn Backend PHP (bảo vệ ngoài web root)
│   ├── bootstrap.php                 # Nạp core, helper và cấu hình
│   ├── config.php                    # File cấu hình hoạt động (TiDB Cloud / Localhost)
│   ├── config.example.php            # File cấu hình mẫu
│   ├── core/                         # Nhân hệ thống: db (PDO SSL), jwt, auth, response
│   ├── helpers/                      # Validate, format chuỗi, tiện ích
│   └── services/                     # Nghiệp vụ: geo, restaurant_query, upload ảnh
├── database/                         # DDL SQL, seed data và migration
│   ├── schema.sql                    # Cấu trúc bảng chính (categories, restaurants, admins, reviews)
│   ├── migration_users_jwt.sql       # Bảng users, refresh tokens, favorites, password resets
│   ├── seed_categories.sql           # 15 danh mục theo 3 nhóm
│   ├── seed_restaurants.sql          # 18 quán ăn mẫu quanh Q7
│   ├── seed_places_expansion.sql     # 32 địa điểm du lịch & vui chơi mở rộng
│   └── setup.php                     # Script khởi tạo / nạp CSDL tự động 1-click
├── docs/                             # Báo cáo, tài liệu kiểm thử và script test
│   ├── test_all_http_endpoints.php   # Test suite 24 endpoint HTTP
│   ├── HUONG_DAN_TEST_POSTMAN.md     # Hướng dẫn chi tiết chạy kiểm thử Postman
│   └── BAO_CAO_MO_TA_DU_AN_FOOD_FINDER.docx # Báo cáo đặc tả dự án chi tiết
├── public/                           # Thư mục Web Root (công khai)
│   ├── index.html                    # Trang chủ
│   ├── explore.html                  # Bản đồ tương tác
│   ├── suggest.html                  # Gợi ý & Lập lộ trình
│   ├── favorites.html                # Địa điểm & lộ trình đã lưu
│   ├── restaurant.html               # Trang chi tiết quán / địa điểm
│   ├── login.html                    # Đăng nhập, Đăng ký, Quên mật khẩu
│   ├── admin.html                    # Bảng điều khiển quản trị Admin
│   ├── api/                          # REST API Endpoints
│   │   ├── auth/                     # login, register, me, refresh, logout, forgot/reset password
│   │   ├── categories.php            # API danh mục
│   │   ├── restaurants.php           # API tìm kiếm địa điểm đa tiêu chí
│   │   ├── itinerary.php             # API lập lộ trình thông minh
│   │   ├── favorites.php             # API quản lý yêu thích theo user
│   │   └── user_itineraries.php      # API lưu lộ trình theo user
│   ├── admin/                        # Giao diện & chức năng CMS Admin
│   └── assets/                       # CSS tokens, JS ES-modules, icon, hình ảnh
├── connect_tidb.php                  # Công cụ chuyển đổi môi trường Cloud / Local 1 chạm
├── setup_database.bat                # Khởi tạo CSDL nhanh trên Windows
├── start_server.bat                  # Khởi chạy PHP server nhanh trên Windows
└── README.md                         # Tài liệu hướng dẫn sử dụng
```

---

## 🔒 Hướng dẫn Triển khai Production

1. **Web Server Root:** Trỏ Document Root vào thư mục `public/`. Tuyệt đối không để lộ thư mục `app/`, `database/` ra ngoài Internet.
2. **Biến môi trường (Environment Variables):** Có thể truyền trực tiếp qua Docker / Server config thay vì ghi cứng vào `config.php`:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL=true`, `JWT_SECRET`.
3. **HTTPS & Cookie:** Khi chạy HTTPS thật, đổi `COOKIE_SECURE = true` trong [app/config.php](file:///d:/Finder/app/config.php) để cookie được gửi độc quyền qua kênh mã hóa SSL.
