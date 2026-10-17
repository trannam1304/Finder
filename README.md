# Food Finder 🍜

Ứng dụng web tìm quán ăn quanh vị trí người dùng, hỗ trợ xem trên bản đồ tương tác, tìm kiếm & bộ lọc danh mục/giá/khoảng cách, gợi ý quán ngẫu nhiên ("Hôm nay ăn gì?") và trang quản trị Admin đầy đủ.

Được xây dựng với HTML5, CSS3 hiện đại, Vanilla JavaScript, PHP (PDO) 8.1+ và MySQL/MariaDB.

---

## 🚀 Hướng dẫn chạy nhanh (Windows + XAMPP)

1. Mở **XAMPP Control Panel** và nhấn **Start** cho **Apache** và **MySQL**.
2. Khởi tạo / Tạo lại Database bằng 1 trong 2 cách:
   - **Cách 1 (Khuyên dùng - gõ trong Terminal):**
     ```bash
     php database/setup.php
     ```
   - **Cách 2:** Nhấp đúp vào file `setup_database.bat`.
   *(Script sẽ tự động tạo database `food_finder`, import toàn bộ schema, danh mục, dữ liệu quán ăn mẫu và tài khoản admin mặc định: `admin` / `Admin@123456`)*
3. Sao chép `app/config.example.php` thành `app/config.php` (mặc định đã cấu hình sẵn cho XAMPP `root` không mật khẩu).
4. Tạo thêm tài khoản quản trị Admin tùy ý (nếu cần):
   ```bash
   php database/create_admin.php
   ```
5. Nhấp đúp vào `start_server.bat` (hoặc chạy lệnh bên dưới) để khởi động web server:
   ```bash
   php -S localhost:8000 -t public
   ```
6. Truy cập trình duyệt tại: **http://localhost:8000**
   - Trang người dùng: `http://localhost:8000`
   - Trang quản trị: `http://localhost:8000/admin.html` (hoặc `http://localhost:8000/admin/`)

---

## 🛠️ Cài đặt thủ công

1. **Import Database**:
   - `database/schema.sql`
   - `database/seed_categories.sql`
   - `database/seed_restaurants.sql`
2. **Cấu hình**:
   - Copy `app/config.example.php` -> `app/config.php`
   - Điền thông tin kết nối MySQL trong `app/config.php`.
3. **Khởi chạy PHP Built-in Server**:
   ```bash
   php -S localhost:8000 -t public
   ```

*Lưu ý:* Khi chạy với PHP server, ứng dụng sử dụng API và cơ sở dữ liệu thật. Khi mở file HTML trực tiếp hoặc API chưa sẵn sàng, giao diện tự động chuyển sang dữ liệu mẫu (mock data) để xem trước.

---

## 📁 Cấu trúc thư mục

```
food-finder/
├── app/                  # Mã nguồn backend PHP (bảo mật, ngoài web root)
│   ├── bootstrap.php     # File nạp trung tâm core & helpers
│   ├── config.example.php# File cấu hình mẫu
│   ├── core/             # Hạ tầng cốt lõi (db, auth, csrf, response)
│   ├── services/         # Nghiệp vụ xử lý (geo, restaurant_query, upload)
│   └── helpers/          # Hàm tiện ích (validate, format)
├── database/             # File schema SQL, seeds và script tạo admin
├── docs/                 # Tài liệu dự án, kế hoạch Google Sheets, preview mockup
│   ├── plan_sheets/      # File Excel, CSV theo dõi tiến độ & script
│   └── previews/         # Giao diện preview tĩnh của CMS Admin
├── public/               # Thư mục Web Root (duy nhất công khai)
│   ├── index.html        # Trang chủ ứng dụng
│   ├── explore.html      # Bản đồ khám phá tương tác
│   ├── suggest.html      # Tính năng "Hôm nay ăn gì?"
│   ├── favorites.html    # Danh sách quán đã lưu
│   ├── restaurant.html   # Chi tiết quán ăn
│   ├── api/              # Các endpoint REST API
│   ├── admin/            # Backend & View cho module Admin
│   ├── assets/           # CSS (Design Tokens), JS (ES Modules), hình ảnh
│   └── uploads/          # Thư mục ảnh tải lên
├── setup_database.bat    # Script khởi tạo CSDL 1-click cho Windows
├── start_server.bat      # Script khởi chạy server 1-click cho Windows
├── PLAN.md               # Kế hoạch phát triển đồ án chi tiết
└── README.md
```

---

## 🔒 Bảo mật & Triển khai Production

- Đặt **document root** của web server vào thư mục `public/`; tuyệt đối không đưa `app/` ra ngoài web root.
- Cần chạy HTTPS trên production để tính năng định vị vị trí (Geolocation API) hoạt động chính xác trên thiết bị di động.
- Thư mục `public/uploads/restaurants/` có quyền ghi cho web server và đã được cấu hình `.htaccess` chặn thực thi script PHP.
