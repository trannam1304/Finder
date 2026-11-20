# Storyboard & Kịch Bản Video Demo Onboarding — Food Finder

## 1. Thông tin chung
- **Sản phẩm:** Food Finder — Tìm quán ăn quanh bạn
- **Đường dẫn trải nghiệm:** [https://finder-theta-rose.vercel.app/](https://finder-theta-rose.vercel.app/)
- **Đối tượng xem:** Quý Thầy/Cô hướng dẫn & đánh giá đồ án môn học (ĐH Tôn Đức Thắng)
- **File video thành phẩm:** [food-finder-onboarding-demo.mp4](file:///c:/Users/Kiet/OneDrive/Documents/Finder/demo/food-finder-onboarding-demo.mp4)
- **Thông số kỹ thuật:** `1920×1080` (Full HD 16:9), `30 fps`, Codec Video `H.264 (yuv420p)`, Codec Âm thanh `AAC 48 kHz Stereo`, Tổng thời lượng **`104.67 giây` (~1 phút 45 giây)**, Dung lượng **`16.52 MB`**.
- **Quy trình thực hiện:** Ghi hình tự động trên trang trực tuyến qua **Playwright MCP**, cắt ghép & chuẩn hóa qua **FFmpeg MCP**, kết hợp lớp hiệu ứng onboarding ([director.js](file:///c:/Users/Kiet/OneDrive/Documents/Finder/demo/director.js)) và tổng hợp 57 hiệu ứng âm thanh UI nhẹ ([mix-sfx.py](file:///c:/Users/Kiet/OneDrive/Documents/Finder/demo/mix-sfx.py)), **không sử dụng voice-over**.
- **Phạm vi bảo mật:** Tuyệt đối **không** mở, không highlight và không demo khu vực `Quản trị` / `Admin`.

---

## 2. Phân bổ thời lượng tổng thể

| Phần | Mốc thời gian | Thời lượng | Mục tiêu nội dung |
| :--- | :--- | :---: | :--- |
| **1. Mở đầu** | `00:00.0 – 00:10.0` | **10.0 s** | Đặt vấn đề *"Hôm nay ăn gì? Ăn ở đâu gần đây?"* và giới thiệu Food Finder giải quyết ra sao |
| **2. Giới thiệu giao diện** | `00:10.0 – 00:25.0` | **15.0 s** | Quét nhanh bố cục trang chủ: Thanh danh mục & bộ lọc, Danh sách quán, Bản đồ tương tác |
| **3. Demo tính năng (5 phần)** | `00:25.0 – 01:34.7` | **69.7 s** | Hướng dẫn từng thao tác theo phong cách onboarding (Highlight mờ nền + Zoom + Tooltip + SFX) |
| **4. Kết thúc** | `01:34.7 – 01:44.7` | **10.0 s** | Tổng kết 3 giá trị cốt lõi của sản phẩm và lời cảm ơn Quý Thầy/Cô |
| **Tổng cộng** | **`00:00.0 – 01:44.7`** | **104.7 s** | Đạt chuẩn ngân sách thời lượng (~10s + ~15s + 60–70s + ~10s) |

---

## 3. Kịch bản chi tiết từng phân cảnh (Storyboard)

### Phần 1 — Mở đầu (`00:00.0 – 00:10.0` • `10.0 giây`)
- **Màn hình hiển thị gì:**
  - Nhịp 1 (`00:00 – 00:04.8`): Thẻ mở đầu Neo-Brutalism với tiêu đề *"“Hôm nay ăn gì? Ăn ở đâu gần đây?”"* cùng 3 thẻ vấn đề thường gặp của sinh viên & giới trẻ (mất 15–30 phút suy nghĩ, khó lọc theo bán kính/giá/giờ mở, ngại đi ngược đường).
  - Nhịp 2 (`00:04.8 – 00:10.0`): Chuyển sang thương hiệu **Food Finder — Tìm quán ăn quanh bạn**, link `finder-theta-rose.vercel.app` và 3 trụ cột giải pháp.
- **Thao tác cần làm:** Chuyển cảnh tự động giữa Beat 1 → Beat 2 và fade mượt sang trang chủ.
- **Kết quả:** Thiết lập bối cảnh rõ ràng trước khi vào giao diện thực tế.
- **Âm thanh & Hiệu ứng:** Hiệu ứng `transition` khi mở/đóng màn và `chime` nhẹ khi hiện logo Food Finder.

---

### Phần 2 — Giới thiệu giao diện trang chủ (`00:10.0 – 00:25.0` • `15.0 giây`)
- **Màn hình hiển thị gì:**
  - Toàn cảnh trang chủ `https://finder-theta-rose.vercel.app/` tại khu vực quanh ĐH Tôn Đức Thắng (Quận 7) hiển thị **`34 địa điểm quanh bạn`** với đầy đủ nhãn **`ĐANG MỞ`** màu xanh.
  - Lần lượt làm nổi bật 3 vùng chính với nền xung quanh được làm tối + làm mờ (`backdrop-filter: blur`):
    1. **Thanh danh mục & Bộ lọc** phía trên (`GIAO DIỆN • 1/3`).
    2. **Danh sách quán gần bạn** bên trái (`GIAO DIỆN • 2/3`) hiển thị ảnh món, giá tham khảo, cự ly, giờ gợi ý và điểm đánh giá.
    3. **Bản đồ tương tác Leaflet** bên phải (`GIAO DIỆN • 3/3`) hiển thị marker phân màu theo loại hình và gom cụm tự động.
- **Thao tác cần làm:** Quét nhanh lần lượt 3 khu vực trên màn hình toàn cảnh (`zoom 1.0×`).
- **Kết quả:** Người xem nắm bắt ngay bố cục 2 cột trực quan giữa danh sách và bản đồ.
- **Âm thanh & Hiệu ứng:** 3 nhịp `whoosh` êm khi khung spotlight di chuyển giữa Header → Sidebar → Map.

---

### Phần 3 — Demo tính năng (`00:25.0 – 01:34.7` • `69.7 giây`)

#### Tính năng 1: Thanh danh mục & Bộ lọc khoảng cách `500 m → 15 km` (`00:25.0 – 00:38.0` • `13.0 giây`)
- **Màn hình hiển thị gì:**
  - Camera phóng to (`zoom 1.48×`) vào thanh danh mục **`Tất cả / Ẩm thực / Du lịch / Vui chơi`** kèm tooltip giải thích.
  - Camera lia và phóng to (`zoom 1.55×`) vào bộ chọn bán kính **`3 km`** với dải pill trực quan `500 m • 3 km • 15 km` trên tooltip.
  - Khi đổi sang **`500 m`** rồi **`15 km`**, camera thu về toàn cảnh (`zoom 1.0×`) làm nổi bật danh sách bên trái và bản đồ cập nhật theo thời gian thực.
- **Thao tác cần làm:**
  1. Di chuyển con trỏ đến tab **`Ẩm thực`** và click chọn.
  2. Di chuyển con trỏ đến bộ chọn khoảng cách, chọn **`500 m`**, quan sát danh sách thu gọn.
  3. Tiếp tục chọn **`15 km`** để mở rộng phạm vi.
- **Kết quả:** Danh sách chỉ giữ lại nhóm **Ẩm thực**; khi chọn `500 m` chỉ còn các quán trong tầm đi bộ (`Phúc Long`, `Mì Quảng Cô Vân`, `Cơm Chay Bồ Đề Tâm`, `Phở Ngon`); khi mở lên `15 km` bản đồ và danh sách tự động mở rộng toàn khu vực.
- **Âm thanh & Hiệu ứng:** `whoosh` khi zoom vào/ra, `click` khi chọn tab `Ẩm thực`, `select` khi đổi `500 m` và `15 km`.

#### Tính năng 2: Bộ lọc mức giá & Trạng thái Đang mở (`00:38.0 – 00:50.0` • `12.0 giây`)
- **Màn hình hiển thị gì:**
  - Thanh bộ lọc cuộn mượt sang phải làm lộ 4 nút **`Bình dân`**, **`Trung bình`**, **`Cao cấp`**, **`Đang mở`**.
  - Camera phóng to (`zoom 1.46×`) vào cụm 4 nút này, làm mờ toàn bộ nền và hiển thị tooltip hướng dẫn kết hợp mức giá + trạng thái mở cửa.
- **Thao tác cần làm:**
  1. Click chọn chip **`Bình dân`**.
  2. Click tiếp chip **`Đang mở`**.
  3. Thu camera về toàn cảnh (`zoom 1.0×`) để kiểm tra danh sách bên trái.
- **Kết quả:** Số lượng kết quả thu gọn chuẩn xác còn **`10 địa điểm quanh bạn`**, mọi thẻ quán đều hiển thị mức giá bình dân và nhãn **`ĐANG MỞ`** màu xanh lá.
- **Âm thanh & Hiệu ứng:** `whoosh` khi zoom, hai tiếng `click` giòn nhẹ khi bật `Bình dân` và `Đang mở`, `whoosh` khi thu về danh sách kết quả.

#### Tính năng 3: Nút “Hôm nay ăn gì?” (`00:50.0 – 01:02.0` • `12.0 giây`)
- **Màn hình hiển thị gì:**
  - Camera phóng to (`zoom 1.55×`) vào nút nổi màu vàng **`Hôm nay ăn gì?`** ở góc dưới bên phải kèm tooltip: *"Nút này gợi ý một quán ngẫu nhiên khi bạn chưa biết ăn gì."*
  - Sau khi bấm, thẻ overlay giữa màn hình bật lên với nhãn **`ĂN Ở ĐÂY ĐI`**, ảnh món ăn, loại hình, mức giá, khoảng cách và 3 nút **`CHỈ ĐƯỜNG`**, **`CHỌN LẠI`**, **`ĐÓNG`**.
- **Thao tác cần làm:**
  1. Click nút **`Hôm nay ăn gì?`**.
  2. Di chuột qua nút **`Chỉ đường`** để giới thiệu tính năng mở Google Maps.
  3. Click **`Chọn lại`** để xem gợi ý mới không trùng lặp, sau đó bấm **`Đóng`**.
- **Kết quả:** Gợi ý ngay một quán ăn phù hợp bộ lọc (`Cơm Chay Bồ Đề Tâm`...) và đổi mượt sang gợi ý khác khi bấm **`Chọn lại`**.
- **Âm thanh & Hiệu ứng:** `whoosh` zoom vào nút FAB, `click` khi bấm, `chime` êm khi thẻ gợi ý bật lên và khi chọn lại thành công.

#### Tính năng 4: Smart Generator “Thiết lập lộ trình đi chơi” (`01:02.0 – 01:19.5` • `17.5 giây`)
- **Màn hình hiển thị gì:**
  - Camera phóng to (`zoom 1.50×`) vào nút **`Lộ trình`** trên thanh tiêu đề.
  - Khi mở modal **Thiết Lập Lộ Trình Đi Chơi**, spotlight lần lượt đi qua từng bước:
    - Bước 1: Thời lượng (**`Nửa ngày`**, `Cả ngày`, `Buổi tối`).
    - Bước 2: Phong cách/Vibe (**`Chill thư thái`**, **`Hẹn hò lãng mạn`**, **`Vui chơi trải nghiệm`**, **`Ăn sập Sài Gòn`**).
    - Bước 3: Bán kính di chuyển (**`3 km`**, `5 km`, **`8 km`**, `12 km`).
  - Camera zoom nhẹ (`1.30×`) vào nút **`Tạo lộ trình ngay`**.
  - Kết quả trả về hiển thị **Timeline 3 chặng** ở cột trái (giờ gợi ý, quãng đường từng chặng, nút **`GOOGLE MAPS`**, **`LƯU`**) cùng **tuyến đường thực tế màu xanh** vẽ trực tiếp trên bản đồ Leaflet.
- **Thao tác cần làm:**
  1. Bấm nút **`Lộ trình`**.
  2. Chọn **`Nửa ngày`** → chọn **`Ăn sập Sài Gòn`** → chọn **`8 km`**.
  3. Bấm **`Tạo lộ trình ngay`** và cuộn nhẹ thanh Timeline để xem đủ 3 chặng (`Mì Quảng Cô Vân` → `Phúc Long` → `Cơm tấm Bụi Sài Gòn`).
- **Kết quả:** Sinh tự động lộ trình 3 chặng tối ưu quanh ĐH Tôn Đức Thắng (`~2.05 km`, `~2.7 giờ`) kèm đường đi nối điểm trên bản đồ.
- **Âm thanh & Hiệu ứng:** `whoosh` khi zoom nút mở & nút submit, `click` cho từng bước chọn cấu hình, `chime` báo hiệu lộ trình hoàn tất.

#### Tính năng 5: Các trang phụ trên thanh menu (`01:19.5 – 01:34.7` • `15.2 giây`)
- **Màn hình hiển thị gì:**
  1. **Menu điều hướng (`01:19.5 – 01:22.3`, `2.8s`):** Zoom `1.45×` đúng vùng 4 nút **`Khám phá`**, **`Đã lưu`**, **`Đề xuất`**, **`Giới thiệu`** (không highlight nút `Quản trị`).
  2. **Trang Khám phá (`01:22.3 – 01:25.8`, `3.5s`):** Bố cục dạng lưới `50 địa điểm` kèm cột lọc danh mục, mức giá, đánh giá và cuộn nhẹ xem danh sách.
  3. **Trang Đã lưu (`01:25.8 – 01:28.8`, `3.0s`):** Hai tab **`Địa Điểm Yêu Thích`** và **`Lộ Trình Đã Lưu`** để quản lý mục đã lưu.
  4. **Trang Đề xuất (`01:28.8 – 01:31.8`, `3.0s`):** Form đóng góp địa điểm mới kèm bản đồ Leaflet cho phép chạm chấm tọa độ trực tiếp.
  5. **Trang Giới thiệu (`01:31.8 – 01:34.7`, `2.9s`):** Tổng quan đồ án với khối **4 Trụ Cột Công Nghệ** và chi tiết công thức Haversine / Trọng số.
- **Thao tác cần làm:** Click mở từng trang phụ tương ứng, chuyển tab **`Lộ Trình Đã Lưu`** ở trang Đã lưu và cuộn nhẹ ở trang Khám phá / Đề xuất / Giới thiệu.
- **Kết quả:** Thể hiện đầy đủ hệ sinh thái tính năng dành cho người dùng cuối, hoàn toàn không lộ trang Quản trị.
- **Âm thanh & Hiệu ứng:** `whoosh` khi zoom menu, `click` chuyển trang & chuyển tab, `transition` êm khi chuyển giữa các trang phụ.

---

### Phần 4 — Kết thúc (`01:34.7 – 01:44.7` • `10.0 giây`)
- **Màn hình hiển thị gì:**
  - Nhịp 1 (`01:34.7 – 01:39.5`): Tóm tắt 3 giá trị cốt lõi (*Tìm kiếm chính xác • Quyết định tức thì • Lộ trình tối ưu*).
  - Nhịp 2 (`01:39.5 – 01:44.7`): Lời cảm ơn *"Trân trọng cảm ơn Quý Thầy/Cô đã theo dõi & đánh giá!"*, địa chỉ web `https://finder-theta-rose.vercel.app/` và thông tin nhóm sinh viên thực hiện (Phạm Nguyễn Tuấn Kiệt, Nguyễn Trọng Quý, Trần Hữu Nam — ĐH Tôn Đức Thắng).
- **Thao tác cần làm:** Chuyển nhịp mượt từ tổng kết sang lời cảm ơn và fade tối cuối video.
- **Kết quả:** Kết thúc trọn vẹn, trang trọng, đúng tinh thần báo cáo đồ án.
- **Âm thanh & Hiệu ứng:** `chime` kết thúc nhẹ nhàng và `transition` khi fade out.
