import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.worksheet.datavalidation import DataValidation
from openpyxl.utils import get_column_letter
import csv
import os

wb = openpyxl.Workbook()

# Setup styles
FONT_FAMILY = "Segoe UI"
font_title = Font(name=FONT_FAMILY, size=16, bold=True, color="0F172A")
font_subtitle = Font(name=FONT_FAMILY, size=10, italic=True, color="475569")
font_section = Font(name=FONT_FAMILY, size=12, bold=True, color="0F172A")
font_header = Font(name=FONT_FAMILY, size=10, bold=True, color="FFFFFF")
font_sub_header = Font(name=FONT_FAMILY, size=10, bold=True, color="1E293B")
font_cell = Font(name=FONT_FAMILY, size=9.5, color="1E293B")
font_cell_bold = Font(name=FONT_FAMILY, size=9.5, bold=True, color="0F172A")
font_kpi_num = Font(name=FONT_FAMILY, size=18, bold=True, color="0284C7")
font_kpi_label = Font(name=FONT_FAMILY, size=9.5, bold=True, color="475569")

fill_header = PatternFill(start_color="0F172A", end_color="0F172A", fill_type="solid")
fill_header_accent = PatternFill(start_color="0284C7", end_color="0284C7", fill_type="solid")
fill_header_sub = PatternFill(start_color="E2E8F0", end_color="E2E8F0", fill_type="solid")
fill_zebra = PatternFill(start_color="F8FAFC", end_color="F8FAFC", fill_type="solid")
fill_kpi_box = PatternFill(start_color="F1F5F9", end_color="F1F5F9", fill_type="solid")

fill_todo = PatternFill(start_color="FEF3C7", end_color="FEF3C7", fill_type="solid")
fill_in_progress = PatternFill(start_color="E0F2FE", end_color="E0F2FE", fill_type="solid")
fill_review = PatternFill(start_color="F3E8FF", end_color="F3E8FF", fill_type="solid")
fill_done = PatternFill(start_color="DCFCE7", end_color="DCFCE7", fill_type="solid")

fill_p1 = PatternFill(start_color="FEE2E2", end_color="FEE2E2", fill_type="solid")
fill_p2 = PatternFill(start_color="FEF3C7", end_color="FEF3C7", fill_type="solid")
fill_p3 = PatternFill(start_color="F1F5F9", end_color="F1F5F9", fill_type="solid")

border_thin = Side(border_style="thin", color="CBD5E1")
border_cell = Border(left=border_thin, right=border_thin, top=border_thin, bottom=border_thin)
border_header = Border(left=border_thin, right=border_thin, top=border_thin, bottom=border_thin)

align_center = Alignment(horizontal="center", vertical="center", wrap_text=True)
align_left = Alignment(horizontal="left", vertical="center", wrap_text=True)
align_right = Alignment(horizontal="right", vertical="center")
align_header = Alignment(horizontal="center", vertical="center", wrap_text=True)

# ==============================================================================
# SHEET 1: DASHBOARD & TỔNG QUAN
# ==============================================================================
ws_dash = wb.active
ws_dash.title = "📊 Dashboard & Tiến Độ"
ws_dash.views.sheetView[0].showGridLines = True

# Title block
ws_dash["A1"] = "DỰ ÁN: FOOD & TRIP FINDER — KẾ HOẠCH & THEO DÕI TIẾN ĐỘ"
ws_dash["A1"].font = font_title
ws_dash["A2"] = "Mở rộng nền tảng: Quán ăn/uống + Điểm du lịch + Khu vui chơi & Tính năng Đề xuất lộ trình đi chơi thông minh"
ws_dash["A2"].font = font_subtitle

# KPI Summary Cards
ws_dash["A4"] = "TỔNG SỐ TASK"
ws_dash["C4"] = "CHƯA BẮT ĐẦU"
ws_dash["E4"] = "ĐANG LÀM"
ws_dash["G4"] = "CHỜ REVIEW"
ws_dash["I4"] = "HOÀN THÀNH"
ws_dash["K4"] = "TIẾN ĐỘ CHUNG"

for col_idx, col_letter in enumerate(["A", "C", "E", "G", "I", "K"]):
    cell_lbl = ws_dash[f"{col_letter}4"]
    cell_lbl.font = font_kpi_label
    cell_lbl.alignment = align_center
    cell_lbl.fill = fill_kpi_box
    ws_dash.merge_cells(f"{col_letter}4:{chr(ord(col_letter)+1)}4")
    
    cell_val = ws_dash[f"{col_letter}5"]
    cell_val.font = font_kpi_num
    cell_val.alignment = align_center
    ws_dash.merge_cells(f"{col_letter}5:{chr(ord(col_letter)+1)}5")

# KPI Formulas referencing Task sheet
ws_dash["A5"] = "=COUNTA('📋 Phân Công Task Chi Tiết'!A4:A24)"
ws_dash["C5"] = "=COUNTIF('📋 Phân Công Task Chi Tiết'!G4:G24, \"To Do\")"
ws_dash["E5"] = "=COUNTIF('📋 Phân Công Task Chi Tiết'!G4:G24, \"In Progress\")"
ws_dash["G5"] = "=COUNTIF('📋 Phân Công Task Chi Tiết'!G4:G24, \"In Review\")"
ws_dash["I5"] = "=COUNTIF('📋 Phân Công Task Chi Tiết'!G4:G24, \"Done\")+COUNTIF('📋 Phân Công Task Chi Tiết'!G4:G24, \"Completed\")"
ws_dash["K5"] = "=IF(A5>0, ROUND((I5/A5)*100, 1) & \"%\", \"0%\")"

ws_dash["C5"].font = Font(name=FONT_FAMILY, size=18, bold=True, color="D97706")
ws_dash["E5"].font = Font(name=FONT_FAMILY, size=18, bold=True, color="0284C7")
ws_dash["G5"].font = Font(name=FONT_FAMILY, size=18, bold=True, color="7C3AED")
ws_dash["I5"].font = Font(name=FONT_FAMILY, size=18, bold=True, color="16A34A")
ws_dash["K5"].font = Font(name=FONT_FAMILY, size=18, bold=True, color="0F172A")

for r in range(4, 6):
    for c in range(1, 13):
        ws_dash.cell(row=r, column=c).border = border_cell

# Section: TIẾN ĐỘ THEO PHÂN HỆ (MODULES)
ws_dash["A8"] = "TIẾN ĐỘ THEO CÁC PHÂN HỆ CÔNG VIỆC (MODULES)"
ws_dash["A8"].font = font_section

module_headers = ["STT", "Phân Hệ / Module", "Tổng Tasks", "Đã Xong", "Đang Làm", "Chưa Làm", "Tiến Độ"]
for col_i, h in enumerate(module_headers, start=1):
    c = ws_dash.cell(row=9, column=col_i, value=h)
    c.font = font_header
    c.fill = fill_header_accent
    c.alignment = align_center
    c.border = border_header

modules_data = [
    (1, "Database & Schema Migration", "=COUNTIF('📋 Phân Công Task Chi Tiết'!B4:B24, \"Database\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Database\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Done\")+COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Database\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Completed\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Database\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"In Progress\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Database\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"To Do\")", "=IF(C10>0, ROUND((D10/C10)*100, 0)&\"%\", \"0%\")"),
    (2, "Backend API & Thuật toán Lộ trình", "=COUNTIF('📋 Phân Công Task Chi Tiết'!B4:B24, \"Backend\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Backend\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Done\")+COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Backend\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Completed\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Backend\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"In Progress\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Backend\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"To Do\")", "=IF(C11>0, ROUND((D11/C11)*100, 0)&\"%\", \"0%\")"),
    (3, "Frontend Giao diện & Bản đồ Lộ trình", "=COUNTIF('📋 Phân Công Task Chi Tiết'!B4:B24, \"Frontend\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Frontend\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Done\")+COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Frontend\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Completed\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Frontend\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"In Progress\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Frontend\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"To Do\")", "=IF(C12>0, ROUND((D12/C12)*100, 0)&\"%\", \"0%\")"),
    (4, "Admin CMS Quản trị Mở rộng", "=COUNTIF('📋 Phân Công Task Chi Tiết'!B4:B24, \"Admin\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Admin\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Done\")+COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Admin\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Completed\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Admin\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"In Progress\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Admin\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"To Do\")", "=IF(C13>0, ROUND((D13/C13)*100, 0)&\"%\", \"0%\")"),
    (5, "Kiểm Thử Chất Lượng (QA & Testing)", "=COUNTIF('📋 Phân Công Task Chi Tiết'!B4:B24, \"QA & Testing\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"QA & Testing\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Done\")+COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"QA & Testing\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Completed\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"QA & Testing\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"In Progress\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"QA & Testing\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"To Do\")", "=IF(C14>0, ROUND((D14/C14)*100, 0)&\"%\", \"0%\")"),
    (6, "Tài Liệu, Báo Cáo & Demo (Docs)", "=COUNTIF('📋 Phân Công Task Chi Tiết'!B4:B24, \"Documentation\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Documentation\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Done\")+COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Documentation\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"Completed\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Documentation\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"In Progress\")", "=COUNTIFS('📋 Phân Công Task Chi Tiết'!B4:B24, \"Documentation\", '📋 Phân Công Task Chi Tiết'!G4:G24, \"To Do\")", "=IF(C15>0, ROUND((D15/C15)*100, 0)&\"%\", \"0%\")"),
]

for row_idx, row_vals in enumerate(modules_data, start=10):
    for col_idx, val in enumerate(row_vals, start=1):
        cell = ws_dash.cell(row=row_idx, column=col_idx, value=val)
        cell.font = font_cell
        cell.border = border_cell
        if col_idx in [1, 3, 4, 5, 6, 7]:
            cell.alignment = align_center
            if col_idx == 7:
                cell.font = font_cell_bold
        else:
            cell.alignment = align_left

# Section: LỘ TRÌNH TRIỂN KHAI THEO GIAI ĐOẠN (SPRINTS)
ws_dash["A18"] = "LỘ TRÌNH CÁC CỘT MỐC (SPRINT MILESTONES)"
ws_dash["A18"].font = font_section

sprint_headers = ["Giai Đoạn", "Tên Giai Đoạn", "Thời Gian", "Mục Tiêu Trọng Tâm", "Đầu Ra Dự Kiến"]
for col_i, h in enumerate(sprint_headers, start=1):
    c = ws_dash.cell(row=19, column=col_i, value=h)
    c.font = font_header
    c.fill = fill_header
    c.alignment = align_center
    c.border = border_header

sprints_data = [
    ("Sprint 1", "Mở Rộng CSDL & Seed Data Du Lịch/Vui Chơi", "12/10 - 16/10/2026", "Thêm loại địa điểm (Place Type), 30+ điểm du lịch/vui chơi quanh Q7/TPHCM, đồng bộ Mock Data", "SQL script chạy mượt, Mock-data có đủ du lịch/vui chơi"),
    ("Sprint 2", "Thuật Toán Đề Xuất Lộ Trình & Backend API", "16/10 - 22/10/2026", "Thuật toán ghép cặp thông minh (Ăn uống + Du lịch + Vui chơi), tối ưu khoảng cách ngắn nhất", "API /api/itinerary.php trả về lộ trình JSON chuẩn xác"),
    ("Sprint 3", "Giao Diện Lập Lịch Trình & Leaflet Polyline", "20/10 - 27/10/2026", "Modal thiết lập lộ trình, Timeline trực quan, vẽ đường nối Polyline Leaflet, nút đổi chặng", "Người dùng tạo được lộ trình hoàn chỉnh trên Web"),
    ("Sprint 4", "Admin CMS, Kiểm Thử Toàn Diện & Báo Cáo", "26/10 - 02/11/2026", "Quản trị đa địa điểm trong Admin, Test GPS thực tế, tài liệu kỹ thuật, slide demo", "Hệ thống hoạt động trơn tru sẵn sàng nghiệm thu"),
]

for row_idx, row_vals in enumerate(sprints_data, start=20):
    for col_idx, val in enumerate(row_vals, start=1):
        cell = ws_dash.cell(row=row_idx, column=col_idx, value=val)
        cell.font = font_cell
        cell.border = border_cell
        if col_idx in [1, 3]:
            cell.alignment = align_center
            if col_idx == 1:
                cell.font = font_cell_bold
        else:
            cell.alignment = align_left


# ==============================================================================
# SHEET 2: PHÂN CÔNG TASK CHI TIẾT
# ==============================================================================
ws_tasks = wb.create_sheet(title="📋 Phân Công Task Chi Tiết")
ws_tasks.views.sheetView[0].showGridLines = True

ws_tasks["A1"] = "BẢNG PHÂN CÔNG VÀ THEO DÕI TIẾN ĐỘ CÔNG VIỆC CHI TIẾT"
ws_tasks["A1"].font = font_title
ws_tasks["A2"] = "Hướng dẫn: Cập nhật trạng thái (To Do, In Progress, In Review, Done, Completed) và Người phụ trách để Dashboard tự động tính toán"
ws_tasks["A2"].font = font_subtitle

task_cols = [
    "Mã Task",
    "Phân Hệ (Module)",
    "Tên Công Việc (Task Title)",
    "Mô Tả Chi Tiết & Tiêu Chí Nghiệm Thu (Acceptance Criteria)",
    "Người Phụ Trách",
    "Độ Ưu Tiên",
    "Trạng Thái",
    "Bắt Đầu",
    "Hạn Chót",
    "Tiến Độ (%)",
    "Ghi Chú & Công Nghệ Liên Quan"
]

for col_idx, col_name in enumerate(task_cols, start=1):
    c = ws_tasks.cell(row=3, column=col_idx, value=col_name)
    c.font = font_header
    c.fill = fill_header
    c.alignment = align_header
    c.border = border_header

tasks_list = [
    (
        "DB-01",
        "Database",
        "Mở rộng Database Schema hỗ trợ Đa loại hình địa điểm",
        "Mở rộng schema thêm cột: place_type ('food', 'tourism', 'entertainment'), ticket_price (giá vé), estimated_duration (thời lượng phút), best_time_slot ('morning', 'afternoon', 'evening').",
        "Backend Dev",
        "P1 - Cao",
        "Completed",
        "12/10/2026",
        "14/10/2026",
        "100%",
        "MySQL / MariaDB, database/schema.sql"
    ),
    (
        "DB-02",
        "Database",
        "Mở rộng Bảng Danh Mục (Categories) theo 3 Nhóm lớn",
        "Thêm type_group vào bảng categories. Tạo danh mục Du lịch (Bảo tàng, Di tích, Công viên, Cảnh quan) & Vui chơi (Khu trò chơi, Rạp phim, Boardgame, Workshop).",
        "Backend Dev",
        "P1 - Cao",
        "Completed",
        "12/10/2026",
        "14/10/2026",
        "100%",
        "database/seed_categories.sql"
    ),
    (
        "DB-03",
        "Database",
        "Thu thập dữ liệu & Seed 30+ Điểm Du lịch & Vui chơi thực tế",
        "Thu thập toạ độ GPS chính xác quanh Q7 & TP.HCM: Cầu Ánh Sao, Hồ Bán Nguyệt, Bảo tàng tranh 3D Artinus, Jump Arena, Thảo Cầm Viên, Bến Bạch Đằng, Landmark 81...",
        "Data Lead",
        "P1 - Cao",
        "Completed",
        "13/10/2026",
        "16/10/2026",
        "100%",
        "database/seed_places_expansion.sql"
    ),
    (
        "DB-04",
        "Frontend",
        "Cập nhật Dữ liệu Mock Data Frontend (mock-data.js)",
        "Đồng bộ toàn bộ danh sách điểm Du lịch và Vui chơi vào mock-data.js để app chạy chế độ demo độc lập không cần backend.",
        "Tuấn Kiệt",
        "P2 - Trung bình",
        "Completed",
        "14/10/2026",
        "16/10/2026",
        "100%",
        "public/assets/js/mock-data.js"
    ),
    (
        "BE-01",
        "Backend",
        "Cập nhật RESTful API Lấy danh sách địa điểm theo loại hình",
        "Nâng cấp api/restaurants.php (hoặc api/places.php) hỗ trợ param: type=all|food|tourism|entertainment, min_price, max_price, tính khoảng cách Haversine.",
        "Backend Dev",
        "P1 - Cao",
        "Completed",
        "15/10/2026",
        "17/10/2026",
        "100%",
        "public/api/restaurants.php, app/geo.php"
    ),
    (
        "BE-02",
        "Backend",
        "Nghiên cứu & Thiết kế Thuật toán Đề xuất Lộ trình Đi chơi",
        "Xây dựng thuật toán ghép lịch trình: Vị trí hiện tại -> [Ăn sáng/Cafe] -> [Khu vui chơi/Điểm tham quan] -> [Ăn trưa/tối] -> [Chill ngắm cảnh]. Sắp xếp theo khoảng cách ngắn nhất (Nearest Neighbor).",
        "Backend Dev",
        "P1 - Cao",
        "Completed",
        "16/10/2026",
        "20/10/2026",
        "100%",
        "app/itinerary.php (Thuật toán định tuyến & heuristics)"
    ),
    (
        "BE-03",
        "Backend",
        "Xây dựng Endpoint API Đề xuất Lộ trình (/api/itinerary.php)",
        "API nhận: user_lat, user_lng, duration (half_day / full_day / evening), vibe, max_radius. Trả về JSON: tổng km, tổng giờ dự kiến, danh sách stops kèm thời gian đề xuất.",
        "Backend Dev",
        "P1 - Cao",
        "Completed",
        "18/10/2026",
        "21/10/2026",
        "100%",
        "public/api/itinerary.php, JSON REST API"
    ),
    (
        "BE-04",
        "Backend",
        "API Đổi Chặng Linh Hoạt trong Lộ trình (/api/itinerary_reroll.php)",
        "Cho phép người dùng bấm 'Đổi địa điểm' ở 1 chặng bất kỳ (không thích bảo tàng này thì đổi sang điểm giải trí khác tương đương gần đó) mà không vỡ toàn bộ lịch trình.",
        "Backend Dev",
        "P2 - Trung bình",
        "Completed",
        "20/10/2026",
        "22/10/2026",
        "100%",
        "public/api/itinerary_reroll.php"
    ),
    (
        "FE-01",
        "Frontend",
        "Nâng cấp Bộ lọc Nhóm Địa Điểm Cấp 1 trên Bản đồ & Explore",
        "Thêm thanh Tabs phân loại: [Tất cả] [🍜 Ẩm thực] [🏛️ Du lịch] [🎡 Vui chơi giải trí] trên index.html và explore.html. Click tab lọc nhanh tức thì.",
        "Tuấn Kiệt",
        "P1 - Cao",
        "Completed",
        "17/10/2026",
        "19/10/2026",
        "100%",
        "public/index.html, public/explore.html, filters.js"
    ),
    (
        "FE-02",
        "Frontend",
        "Thiết kế Leaflet Marker & Popup Riêng Biệt theo Loại Hình",
        "Marker màu đỏ/cam cho Quán ăn; Xanh lam/tím cho Du lịch; Vàng/cam neon cho Khu vui chơi. Bổ sung icon vector SVG tương ứng trong icons.js.",
        "Tuấn Kiệt",
        "P2 - Trung bình",
        "Completed",
        "18/10/2026",
        "20/10/2026",
        "100%",
        "public/assets/js/map.js, icons.js"
    ),
    (
        "FE-03",
        "Frontend",
        "Cập nhật Card Địa Điểm & Trang Chi Tiết (restaurant.html)",
        "Hiển thị badge loại địa điểm, giá vé vào cổng (Vé miễn phí / Có phí), thời lượng trải nghiệm đề xuất (Ví dụ: 'Gợi ý: 90 phút').",
        "Tuấn Kiệt",
        "P2 - Trung bình",
        "Completed",
        "19/10/2026",
        "21/10/2026",
        "100%",
        "public/restaurant.html, format.js"
    ),
    (
        "FE-04",
        "Frontend",
        "Thiết kế Modal / Giao diện Thiết lập Lộ trình Đi chơi",
        "Form cho người dùng chọn: Thời lượng (Nửa ngày / Cả ngày / Buổi tối), Vibe (Chill, Hẹn hò, Trải nghiệm, Ăn sập), Bán kính. Nút CTA 'Tạo lộ trình ngay'.",
        "Frontend Dev",
        "P1 - Cao",
        "To Do",
        "20/10/2026",
        "23/10/2026",
        "0%",
        "Giao diện Neo-Brutalism, itinerary-setup.js"
    ),
    (
        "FE-05",
        "Frontend",
        "Xây dựng Giao diện Timeline Lịch trình Thông minh",
        "Hiển thị thẻ lịch trình dạng Timeline dọc trực quan: Cột mốc giờ (09:00 - 11:30...), Thẻ địa điểm, Khoảng cách km từ chặng trước, Nút 'Đổi điểm khác' (Reroll).",
        "Frontend Dev",
        "P1 - Cao",
        "To Do",
        "22/10/2026",
        "25/10/2026",
        "0%",
        "Timeline CSS Component, itinerary-view.js"
    ),
    (
        "FE-06",
        "Frontend",
        "Vẽ Tuyến Đường Đa Điểm trên Leaflet (Routing Polyline)",
        "Vẽ đường Polyline nối Vị trí người dùng -> Điểm 1 -> Điểm 2 -> Điểm 3... Đánh số thứ tự Marker [1][2][3]. Tích hợp nút 'Mở toàn bộ lộ trình trên Google Maps'.",
        "Frontend Dev",
        "P1 - Cao",
        "To Do",
        "23/10/2026",
        "26/10/2026",
        "0%",
        "Leaflet Polyline, Google Maps multi-waypoint URL"
    ),
    (
        "FE-07",
        "Frontend",
        "Tính năng Lưu & Chia Sẻ Lộ trình (LocalStorage / Link)",
        "Cho phép lưu các lộ trình tâm đắc vào danh sách cá nhân (LocalStorage) và tạo link chia sẻ hoặc xuất ảnh tóm tắt lịch trình.",
        "Frontend Dev",
        "P3 - Thấp",
        "To Do",
        "25/10/2026",
        "27/10/2026",
        "0%",
        "LocalStorage, Web Share API"
    ),
    (
        "ADM-01",
        "Admin",
        "Cập nhật Admin CMS Quản trị Đa loại hình Địa điểm",
        "Thêm trường chọn Loại hình (Ăn uống / Du lịch / Vui chơi), nhập giá vé vào cổng, thời lượng ghé thăm trong trang thêm/sửa quán của Admin CMS.",
        "Tuấn Kiệt",
        "P2 - Trung bình",
        "Completed",
        "24/10/2026",
        "26/10/2026",
        "100%",
        "public/admin/restaurants.php"
    ),
    (
        "ADM-02",
        "Admin",
        "Thống kê Dashboard Admin theo Phân loại Mới",
        "Cập nhật biểu đồ Dashboard thống kê tỷ trọng 3 nhóm địa điểm và số lượt người dùng sử dụng tính năng tạo lộ trình.",
        "Tuấn Kiệt",
        "P3 - Thấp",
        "Completed",
        "26/10/2026",
        "28/10/2026",
        "100%",
        "public/admin/dashboard.php, Chart.js"
    ),
    (
        "QA-01",
        "QA & Testing",
        "Kiểm thử Thuật toán Gợi ý Lộ trình với các Toạ độ thực tế",
        "Test các vị trí xuất phát: TDTU Quận 7, Bến Bạch Đằng Q1, Khu Sala Q2... Kiểm tra tính hợp lý của lịch trình, không bị đảo chiều vô lý, giờ mở cửa khớp.",
        "Tuấn Kiệt",
        "P1 - Cao",
        "Completed",
        "26/10/2026",
        "28/10/2026",
        "100%",
        "Test cases, API testing (Postman)"
    ),
    (
        "QA-02",
        "QA & Testing",
        "Kiểm thử Giao diện Responsive Mobile & Hiệu năng Bản đồ",
        "Kiểm tra trải nghiệm trên thiết bị di động (Mobile Web / PWA), thao tác chạm vuốt bản đồ, độ mượt khi zoom và tải nhiều điểm dừng trên lộ trình.",
        "Tuấn Kiệt",
        "P2 - Trung bình",
        "Completed",
        "28/10/2026",
        "30/10/2026",
        "100%",
        "Mobile browser test, Chrome DevTools"
    ),
    (
        "DOC-01",
        "Documentation",
        "Cập nhật Tài liệu Kỹ thuật, Sơ đồ Thuật toán & ERD mới",
        "Vẽ sơ đồ hoạt động (Flowchart tạo lộ trình), sơ đồ use-case, cập nhật ERD database và tài liệu mô tả API cho hội đồng nghiệm thu.",
        "Team Leader",
        "P2 - Trung bình",
        "To Do",
        "29/10/2026",
        "31/10/2026",
        "0%",
        "PLAN.md, Mermaid diagrams, Báo cáo đồ án"
    ),
    (
        "DOC-02",
        "Documentation",
        "Chuẩn bị Slide Thuyết trình & Video Demo Tính năng Lộ trình",
        "Thiết kế slide báo cáo tổng kết, kịch bản demo trực quan làm nổi bật tính năng gợi ý lịch trình đi chơi dựa trên vị trí thời gian thực.",
        "All Team",
        "P2 - Trung bình",
        "To Do",
        "30/10/2026",
        "02/11/2026",
        "0%",
        "Slide PPTX / Google Slides, Video demo"
    )
]

for row_idx, task in enumerate(tasks_list, start=4):
    is_zebra = (row_idx % 2 == 1)
    for col_idx, val in enumerate(task, start=1):
        cell = ws_tasks.cell(row=row_idx, column=col_idx, value=val)
        cell.font = font_cell
        cell.border = border_cell
        if is_zebra:
            cell.fill = fill_zebra
            
        # Specific alignments & formatting
        if col_idx in [1, 6, 7, 8, 9, 10]:
            cell.alignment = align_center
        elif col_idx in [2, 5]:
            cell.alignment = align_center
            cell.font = font_cell_bold
        else:
            cell.alignment = align_left
            
        # Priority badges
        if col_idx == 6:
            if "P1" in val:
                cell.fill = fill_p1
                cell.font = Font(name=FONT_FAMILY, size=9.5, bold=True, color="991B1B")
            elif "P2" in val:
                cell.fill = fill_p2
                cell.font = Font(name=FONT_FAMILY, size=9.5, bold=True, color="92400E")
            else:
                cell.fill = fill_p3
                cell.font = Font(name=FONT_FAMILY, size=9.5, bold=True, color="475569")
                
        # Status badges
        if col_idx == 7:
            if val in ("Done", "Completed"):
                cell.fill = fill_done
                cell.font = Font(name=FONT_FAMILY, size=9.5, bold=True, color="166534")
            elif val == "In Progress":
                cell.fill = fill_in_progress
                cell.font = Font(name=FONT_FAMILY, size=9.5, bold=True, color="075985")
            elif val == "In Review":
                cell.fill = fill_review
                cell.font = Font(name=FONT_FAMILY, size=9.5, bold=True, color="6B21A8")
            else: # To Do
                cell.fill = fill_todo
                cell.font = Font(name=FONT_FAMILY, size=9.5, bold=True, color="92400E")

# Add Data Validation for Status & Priority dropdowns
dv_status = DataValidation(type="list", formula1='"To Do,In Progress,In Review,Done,Completed"', allow_blank=True)
ws_tasks.add_data_validation(dv_status)
dv_status.add(f"G4:G{len(tasks_list)+3}")

dv_priority = DataValidation(type="list", formula1='"P1 - Cao,P2 - Trung bình,P3 - Thấp"', allow_blank=True)
ws_tasks.add_data_validation(dv_priority)
dv_priority.add(f"F4:F{len(tasks_list)+3}")

dv_module = DataValidation(type="list", formula1='"Database,Backend,Frontend,Admin,QA & Testing,Documentation"', allow_blank=True)
ws_tasks.add_data_validation(dv_module)
dv_module.add(f"B4:B{len(tasks_list)+3}")


# ==============================================================================
# SHEET 3: PHÂN CHIA VAI TRÒ (RACI MATRIX)
# ==============================================================================
ws_raci = wb.create_sheet(title="👥 Phân Chia Vai Trò (RACI)")
ws_raci.views.sheetView[0].showGridLines = True

ws_raci["A1"] = "BẢNG PHÂN CHIA VAI TRÒ & TRÁCH NHIỆM TRONG NHÓM (RACI MATRIX)"
ws_raci["A1"].font = font_title
ws_raci["A2"] = "R = Responsible (Thực hiện), A = Accountable (Chịu trách nhiệm chính), C = Consulted (Tham vấn), I = Informed (Nhận thông tin)"
ws_raci["A2"].font = font_subtitle

# Member List
ws_raci["A4"] = "DANH SÁCH THÀNH VIÊN DỰ ÁN"
ws_raci["A4"].font = font_section

mem_headers = ["STT", "Họ & Tên Thành Viên", "Vai Trò Đảm Nhận", "Phạm Vi Phụ Trách", "Ghi Chú"]
for col_i, h in enumerate(mem_headers, start=1):
    c = ws_raci.cell(row=5, column=col_i, value=h)
    c.font = font_header
    c.fill = fill_header_accent
    c.alignment = align_center
    c.border = border_header

members = [
    (1, "52400207 - Phạm Nguyễn Tuấn Kiệt", "Frontend & UI/UX Lead, Admin CMS, QA & Testing", "Mock Data (DB-04), Bộ lọc cấp 1 (FE-01), Marker & Popup (FE-02), Card & Chi tiết (FE-03), Admin CMS (ADM-01, ADM-02), Kiểm thử (QA-01, QA-02)", "Hoàn thành 100% (8/8 tasks)"),
    (2, "52400230 - Nguyễn Trọng Quý", "Database Architect, Data Engineering & Docs", "Thiết kế Schema mở rộng (DB-01), Danh mục 3 nhóm (DB-02), Seed 50+ địa điểm thực tế (DB-03), Báo cáo kỹ thuật & ERD (DOC-01)", "Lead Database & Docs"),
    (3, "52400217 - Trần Hữu Nam", "Backend API, Routing Algorithm & Itinerary UI", "REST API Địa điểm & Lộ trình (BE-01..BE-04), Giao diện Modal, Timeline, Leaflet Polyline & Chia sẻ Lộ trình (FE-04..FE-07)", "Lead Backend & Routing"),
]

for row_idx, mem in enumerate(members, start=6):
    for col_idx, val in enumerate(mem, start=1):
        c = ws_raci.cell(row=row_idx, column=col_idx, value=val)
        c.font = font_cell
        c.border = border_cell
        if col_idx in [1, 3]:
            c.alignment = align_center
            if col_idx == 3:
                c.font = font_cell_bold
        else:
            c.alignment = align_left

# RACI Matrix Table
ws_raci["A12"] = "MA TRẬN PHÂN CÔNG TRÁCH NHIỆM (RACI MATRIX)"
ws_raci["A12"].font = font_section

raci_headers = ["STT", "Hạng Mục Công Việc", "Trọng Quý (DB/Docs)", "Tuấn Kiệt (FE/Admin/QA)", "Hữu Nam (BE/Routing)", "Ghi Chú"]
for col_i, h in enumerate(raci_headers, start=1):
    c = ws_raci.cell(row=13, column=col_i, value=h)
    c.font = font_header
    c.fill = fill_header
    c.alignment = align_center
    c.border = border_header

raci_matrix = [
    (1, "Thiết kế Schema CSDL & Thu thập dữ liệu Du lịch/Vui chơi", "R", "C", "C", "DB-01, DB-02, DB-03"),
    (2, "Đồng bộ Mock Data Frontend 50 địa điểm & 15 danh mục", "C", "R", "I", "DB-04"),
    (3, "Xây dựng Thuật toán & API Đề xuất Lộ trình", "C", "I", "R", "BE-01, BE-02, BE-03, BE-04"),
    (4, "Nâng cấp bộ lọc đa dạng, Marker phân màu & Card chi tiết", "I", "R", "C", "FE-01, FE-02, FE-03"),
    (5, "Thiết kế Giao diện Timeline, Polyline & Chia sẻ lộ trình", "I", "C", "R", "FE-04, FE-05, FE-06, FE-07"),
    (6, "Cập nhật Admin CMS & Thống kê Dashboard mở rộng", "C", "R", "I", "ADM-01, ADM-02"),
    (7, "Kiểm thử Thuật toán GPS & Responsive Mobile", "C", "R", "C", "QA-01, QA-02"),
    (8, "Viết báo cáo kỹ thuật, Slide & Video Demo", "R", "C", "C", "DOC-01, DOC-02"),
]

for row_idx, r in enumerate(raci_matrix, start=14):
    for col_idx, val in enumerate(r, start=1):
        c = ws_raci.cell(row=row_idx, column=col_idx, value=val)
        c.font = font_cell
        c.border = border_cell
        if col_idx != 2:
            c.alignment = align_center
            if val in ["R", "A"]:
                c.font = font_cell_bold
                c.fill = fill_in_progress if val == "R" else fill_p1
        else:
            c.alignment = align_left


# ==============================================================================
# SHEET 4: ĐẶC TẢ TÍNH NĂNG LỘ TRÌNH (SPECS & LOGIC)
# ==============================================================================
ws_specs = wb.create_sheet(title="💡 Đặc Tả Thuật Toán Lộ Trình")
ws_specs.views.sheetView[0].showGridLines = True

ws_specs["A1"] = "ĐẶC TẢ THUẬT TOÁN & QUY TẮC ĐỀ XUẤT LỘ TRÌNH ĐI CHƠI (ITINERARY PLANNER)"
ws_specs["A1"].font = font_title
ws_specs["A2"] = "Tài liệu kỹ thuật định hướng phát triển cho Backend & Frontend"
ws_specs["A2"].font = font_subtitle

specs_headers = ["Thông Số / Bước", "Mô Tả & Quy Tắc Logic", "Công Thức / Chi Tiết Kỹ Thuật", "Ghi Chú"]
for col_i, h in enumerate(specs_headers, start=1):
    c = ws_specs.cell(row=4, column=col_i, value=h)
    c.font = font_header
    c.fill = fill_header_accent
    c.alignment = align_center
    c.border = border_header

specs_data = [
    ("Input 1: Vị trí người dùng", "Toạ độ GPS thời điểm hiện tại của người dùng", "user_lat, user_lng (HTML5 Geolocation fallback về TDTU Q7)", "Điểm xuất phát bắt buộc"),
    ("Input 2: Khung thời gian", "Thời lượng chuyến đi mong muốn", "half_day_morning (08:00 - 13:00) | half_day_afternoon (13:00 - 18:00) | evening (17:00 - 22:00) | full_day (08:00 - 21:00)", "Quyết định số chặng dừng"),
    ("Input 3: Phong cách (Vibe)", "Sở thích trải nghiệm chuyến đi", "Chill & Cafe (nhẹ nhàng) | Vui chơi hết mình (năng động) | Check-in & Sống ảo (cảnh đẹp) | Ăn sập (ẩm thực) | Hẹn hò lãng mạn", "Trọng số lọc danh mục"),
    ("Input 4: Bán kính di chuyển", "Giới hạn khoảng cách tối đa từ điểm xuất phát", "Mặc định 5km, cho phép chọn 3km / 5km / 10km", "Tránh gợi ý quá xa"),
    ("Quy tắc cấu trúc chặng (Sáng)", "1. Ăn sáng/Cafe -> 2. Vui chơi/Bảo tàng/Check-in -> 3. Ăn trưa", "Stop 1: Food (Cafe/Breakfast) -> Stop 2: Tourism/Entertainment -> Stop 3: Food (Lunch)", "3 chặng cân bằng"),
    ("Quy tắc cấu trúc chặng (Chiều)", "1. Ăn trưa/Trà sữa -> 2. Điểm du lịch/Trải nghiệm -> 3. Ngắm hoàng hôn/Ăn tối", "Stop 1: Food -> Stop 2: Tourism/Entertainment -> Stop 3: Food/Cafe", "3 chặng"),
    ("Quy tắc cấu trúc chặng (Tối)", "1. Ăn tối ẩm thực -> 2. Khu vui chơi/Rạp phim/Dạo phố -> 3. Cafe chill/Quán nước view đêm", "Stop 1: Food (Dinner) -> Stop 2: Entertainment/Tourism -> Stop 3: Cafe/Dessert", "3 chặng"),
    ("Quy tắc cấu trúc chặng (Cả ngày)", "Sáng (Ăn sáng + Tham quan) -> Trưa (Ăn trưa + Nghỉ ngơi cafe) -> Chiều (Khu vui chơi) -> Tối (Ăn tối + Dạo ngắm cảnh đêm)", "5 chặng: Food -> Tourism -> Food -> Entertainment -> Tourism/Food", "Chuyến đi trọn vẹn"),
    ("Thuật toán tối ưu đường đi", "Áp dụng Nearest Neighbor Heuristic", "Từ Stop n, tìm Stop n+1 thỏa mãn loại hình cần tìm sao cho khoảng cách d(n, n+1) nhỏ nhất", "Tránh đi ngược chiều zíc zắc"),
    ("Tính năng Đổi chặng (Reroll)", "Bấm nút 'Đổi' tại chặng i để chọn địa điểm khác", "Tìm địa điểm thay thế cùng nhóm trong bán kính gần điểm i-1 và i+1, loại trừ điểm đang chọn", "Người dùng làm chủ lịch trình"),
    ("Hiển thị & Dẫn đường", "Vẽ Leaflet Polyline và tạo Multi-waypoint link Google Maps", "https://www.google.com/maps/dir/lat0,lng0/lat1,lng1/lat2,lng2/...", "Điều hướng thực tế 1 chạm"),
]

for row_idx, s in enumerate(specs_data, start=5):
    for col_idx, val in enumerate(s, start=1):
        c = ws_specs.cell(row=row_idx, column=col_idx, value=val)
        c.font = font_cell
        c.border = border_cell
        if col_idx in [1, 4]:
            c.alignment = align_center
            if col_idx == 1:
                c.font = font_cell_bold
        else:
            c.alignment = align_left


# ==============================================================================
# TỰ ĐỘNG CÂN CHỈNH ĐỘ RỘNG CỘT (AUTO COLUMN WIDTH)
# ==============================================================================
for ws in [ws_dash, ws_tasks, ws_raci, ws_specs]:
    for col in ws.columns:
        max_len = 0
        col_letter = get_column_letter(col[0].column)
        for cell in col:
            # Skip title row from calculation
            if cell.row in [1, 2]:
                continue
            val_str = str(cell.value or "")
            if len(val_str) > max_len:
                max_len = len(val_str)
        # Cap width
        adjusted_width = min(max(max_len + 3, 10), 65)
        ws.column_dimensions[col_letter].width = adjusted_width

# Custom fine-tuning for specific columns
ws_tasks.column_dimensions["A"].width = 12   # Mã Task
ws_tasks.column_dimensions["B"].width = 16   # Phân hệ
ws_tasks.column_dimensions["C"].width = 34   # Tên công việc
ws_tasks.column_dimensions["D"].width = 55   # Mô tả
ws_tasks.column_dimensions["E"].width = 18   # Người phụ trách
ws_tasks.column_dimensions["F"].width = 16   # Độ ưu tiên
ws_tasks.column_dimensions["G"].width = 15   # Trạng thái
ws_tasks.column_dimensions["H"].width = 13   # Bắt đầu
ws_tasks.column_dimensions["I"].width = 13   # Hạn chót
ws_tasks.column_dimensions["J"].width = 12   # Tiến độ
ws_tasks.column_dimensions["K"].width = 38   # Ghi chú

# Save Excel file
excel_path = "FoodFinder_Project_Plan.xlsx"
wb.save(excel_path)
print(f"Excel workbook created successfully: {excel_path}")

# Export CSV for quick single-sheet import
csv_path = "FoodFinder_Project_Plan.csv"
with open(csv_path, mode="w", newline="", encoding="utf-8-sig") as f:
    writer = csv.writer(f)
    writer.writerow(task_cols)
    for task in tasks_list:
        writer.writerow(task)
print(f"CSV file created successfully: {csv_path}")
