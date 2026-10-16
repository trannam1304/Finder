# Quy Tắc Cho Agent (Agent Rules)

## 1. QUY TẮC NHÁNH GIT: LUÔN LUÔN DÙNG BRANCH `dev_Kiet` (HOẶC `dev_kiet`)
- **TUYỆT ĐỐI KHÔNG** commit hay push thẳng vào branch `main`.
- **MỌI THAY ĐỔI** phải được commit và push vào branch riêng của Kiệt: `dev_Kiet` (remote: `origin/dev_kiet`).
- Trước khi bắt đầu công việc, luôn đảm bảo đang ở nhánh `dev_Kiet` (nếu cần cập nhật code mới nhất từ `main`, hãy merge hoặc rebase `main` vào `dev_Kiet`).

## 2. BẮT BUỘC: Tự Động Git Commit & Push Sau Mỗi Lần Hoàn Thành Sửa Đổi
Mỗi khi Agent hoàn thành một yêu cầu sửa lỗi (bugfix), cập nhật UI/UX, thêm tính năng (feature), refactor hoặc tối ưu code:

1. **Kiểm tra trạng thái (Status Check):**
   - Đảm bảo đang đứng trong thư mục repository Git (`Finder`).
   - Đảm bảo đang ở branch `dev_Kiet` (hoặc `dev_kiet`).
   - Chạy lệnh `git status` để kiểm tra các file đã chỉnh sửa, thêm mới hoặc bị xóa.

2. **Stage thay đổi (Git Add):**
   - Chạy `git add .` hoặc chỉ định chính xác các file đã thay đổi liên quan đến task.
   - Không add các file rác, file nhị phân tạm, file cấu hình chứa credential cá nhân nếu có.

3. **Tạo Commit (Git Commit):**
   - Thực hiện `git commit -m "<type>(<scope>): <mô tả rõ ràng công việc vừa làm>"`.
   - Tuân thủ quy chuẩn Conventional Commits:
     - `feat`: Thêm chức năng mới
     - `fix`: Sửa lỗi (bug, font, layout, crash...)
     - `style`: Căn chỉnh UI, CSS, format, font chữ
     - `refactor`: Tái cấu trúc code mà không đổi chức năng
     - `docs`: Cập nhật tài liệu, README, hướng dẫn

4. **Đẩy lên Remote (Git Push):**
   - **BẮT BUỘC:** Chạy `git push origin dev_Kiet` (hoặc `git push origin dev_kiet`).
   - **TUYỆT ĐỐI KHÔNG:** Chạy `git push origin main`.
   - Luôn kiểm tra kết quả terminal để chắc chắn commit đã được push thành công lên GitHub/remote repository.

5. **Báo cáo kết quả:**
   - Trong phản hồi cuối cùng gửi User, luôn kèm theo thông tin branch (`dev_Kiet`), commit hash và xác nhận việc push đã hoàn tất thành công.
