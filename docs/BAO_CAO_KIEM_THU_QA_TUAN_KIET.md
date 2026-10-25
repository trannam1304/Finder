# Báo Cáo Kiểm Thử Chất Lượng Hệ Thống (QA & Testing Report) 🧪📱

> **Dự án**: Food & Trip Finder — Bản đồ Ẩm thực, Du lịch, Vui chơi & Đề xuất Lộ trình Thông minh  
> **Người phụ trách kiểm thử (QA Lead)**: Phạm Nguyễn Tuấn Kiệt — MSSV: `52400207`  
> **Phạm vi kiểm thử**: Task `QA-01` (Thuật toán Lộ trình & GPS thực tế) & Task `QA-02` (Giao diện Responsive Mobile & Hiệu năng Bản đồ)  
> **Kịch bản kiểm thử tự động**: [`docs/qa_verify_tuan_kiet.php`](./qa_verify_tuan_kiet.php)

---

## 1. KẾT QUẢ KIỂM THỬ TỰ ĐỘNG (AUTOMATED TEST SUITE)

Toàn bộ các bài kiểm thử đơn vị và tích hợp trong [`docs/qa_verify_tuan_kiet.php`](./qa_verify_tuan_kiet.php) đạt **26/26 PASS (100%)**:

* **Kiểm thử đồng bộ dữ liệu Mock Data (`DB-04`)**:
  * Đủ **50 địa điểm** thực tế tại Quận 7 & TP.HCM: `18` địa điểm Ẩm thực (`food`), `16` địa điểm Du lịch (`tourism`), `16` địa điểm Vui chơi giải trí (`entertainment`).
  * Đủ **15 danh mục** phân theo 3 nhóm `type_group` (`food`, `tourism`, `entertainment`).
* **Kiểm thử Thuật toán Không gian Địa lý & Lộ trình (`QA-01`)**:
  * Kiểm thử Bounding Box & Haversine tại 3 tọa độ thực tế:
    1. **Trường ĐH Tôn Đức Thắng (Quận 7)**: `10.7326, 106.6996`
    2. **Bến Bạch Đằng (Quận 1)**: `10.7715, 106.7068`
    3. **Khu đô thị Sala Thủ Thiêm (TP. Thủ Đức / Quận 2)**: `10.7720, 106.7220`
  * Kiểm thử toàn bộ **12 tổ hợp lộ trình** (`3 khung thời gian` × `4 phong cách Vibe`):
    * `half_day` (Nửa ngày — 3 chặng dừng: Ăn sáng/Cafe → Tham quan/Vui chơi → Ăn trưa)
    * `evening` (Buổi tối — 3 chặng dừng: Ngắm hoàng hôn → Ăn tối → Giải trí/Cafe đêm)
    * `full_day` (Cả ngày — 5 chặng dừng: Ăn sáng → Tham quan → Ăn trưa → Vui chơi → Ngắm cảnh/Ăn tối)
  * Kiểm thử liên kết điều hướng đa điểm **Google Maps Multi-waypoint URL** (`origin`, `destination`, `waypoints`).

---

## 2. CHI TIẾT KẾT QUẢ KIỂM THỬ TASK `QA-01` (GPS & LỘ TRÌNH THỰC ĐỊA)

| Mã Test Case | Vị Trí Xuất Phát (GPS) | Cấu Hình Lộ Trình | Lộ Trình Đề Xuất Thực Tế | Đánh Giá Tính Hợp Lý | Kết Quả |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-QA01-01** | **ĐH Tôn Đức Thắng Q.7** (`10.7326, 106.6996`) | `half_day` • `chill` • `5km` | 1. Phúc Long Lê Văn Lương (0.27 km)<br>2. Công viên Cảnh Đồi PMH (1.58 km)<br>3. Cơm Tấm Phúc Lộc Thọ (1.42 km) | Tuyến đi liền mạch từ cổng trường TDTU sang Phú Mỹ Hưng, không đảo chiều. | **PASS ✅** |
| **TC-QA01-02** | **ĐH Tôn Đức Thắng Q.7** (`10.7326, 106.6996`) | `full_day` • `adventure` • `8km` | 1. Bánh Mì Huỳnh Hoa Q7<br>2. Bảo tàng 3D Artinus<br>3. Cơm Gà Xối Mỡ Vi Anh<br>4. Jump Arena Him Lam<br>5. Cầu Ánh Sao | Kết hợp hài hòa Ăn uống + Tham quan + Vận động trong bán kính gần. | **PASS ✅** |
| **TC-QA01-03** | **Bến Bạch Đằng Q.1** (`10.7715, 106.7068`) | `evening` • `date` • `5km` | 1. Bến Bạch Đằng Waterbus<br>2. Lẩu Bò Nhà Gỗ / Panda BBQ<br>3. Phố đi bộ Nguyễn Huệ | Phù hợp khung giờ buổi tối (17:30 – 22:00), các điểm mở cửa thực tế. | **PASS ✅** |
| **TC-QA01-04** | **Khu đô thị Sala Q.2** (`10.7720, 106.7220`) | `half_day` • `chill` • `8km` | 1. Highlands Coffee Sunrise<br>2. Cầu Ba Son & Bờ sông Thủ Thiêm<br>3. Thảo Cầm Viên Sài Gòn | Khoảng cách từng chặng ngắn nhất theo Nearest Neighbor Heuristic. | **PASS ✅** |
| **TC-QA01-05** | **Đổi chặng đơn lẻ (Reroll)** (`/api/itinerary_reroll.php`) | Đổi Chặng 2 (`tourism` / `entertainment`) | Đổi từ *Bảo tàng 3D Artinus* sang *Hồ Bán Nguyệt* mà giữ nguyên Chặng 1 & Chặng 3 | Không trùng lặp địa điểm đã có trong lộ trình (`exclude_ids`). | **PASS ✅** |

---

## 3. CHI TIẾT KẾT QUẢ KIỂM THỬ TASK `QA-02` (RESPONSIVE MOBILE & HIỆU NĂNG BẢN ĐỒ)

| Mã Test Case | Thiết Bị / Khung Hình | Hạng Mục Kiểm Thử | Kết Quả Đo Lường Thực Tế | Kết Quả |
| :--- | :--- | :--- | :--- | :---: |
| **TC-QA02-01** | **Mobile iPhone SE / 14 Pro** (`375x667` & `390x844`) | Thanh Tabs phân loại cấp 1 (`place-type-tabs`) & Chip danh mục | Cuộn ngang mượt mà (`overflow-x: auto`), không bị vỡ khung hay tràn chữ. | **PASS ✅** |
| **TC-QA02-02** | **Mobile Touch Gestures** (`< 768px`) | Bottom Sheet 3 trạng thái (`peek`, `half`, `full`) trong `sheet.js` | Vuốt kéo lên/xuống mượt ở 60fps; khi chọn địa điểm tự động thu về `peek` để lộ marker trên bản đồ. | **PASS ✅** |
| **TC-QA02-03** | **Tablet iPad Air / Mini** (`768x1024`) | Giao diện lưới `explore.html` & `favorites.html` | Tự động chia 2 cột (`minmax(280px, 1fr)`), đầy đủ badge Loại hình, Giá vé, Thời lượng. | **PASS ✅** |
| **TC-QA02-04** | **Desktop Full HD** (`1920x1080`) | Sidebar + Bản đồ Leaflet + Admin CMS Dashboard | Hiển thị đầy đủ 50 marker phân màu theo 3 nhóm (`food`, `tourism`, `entertainment`), gom cụm `MarkerCluster` dưới 35ms. | **PASS ✅** |
| **TC-QA02-05** | **Dark Mode / Light Mode** | Chuyển đổi chủ đề giao diện trên tất cả các trang | Độ tương phản chữ và màu nền các badge (`.badge--food`, `.badge--tourism`, `.badge--entertainment`) đạt chuẩn WCAG AA. | **PASS ✅** |
