# HUIT Schedule Hub 🎓
> **Hệ thống Quản lý và Phân lịch Giảng dạy Đa cơ sở Thông minh**  
> Dự án bài thi thực hành - Môn học: Mã nguồn mở (MNM)  
> Sinh viên thực hiện: **Nguyễn Gia Bảo** - MSSV: **2001230044**

[![PHP Syntax Check (CI/CD)](https://github.com/huit-opensource/huit-schedule-hub/actions/workflows/ci.yml/badge.svg)](https://github.com/huit-opensource/huit-schedule-hub/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B%20%7C%208.2%20%7C%208.3-777BB4?logo=php&logoColor=white)](https://php.net)
[![Composer](https://img.shields.io/badge/Composer-2.x-885630?logo=composer&logoColor=white)](https://getcomposer.org)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)](https://mysql.com)

---

## 📌 1. Giới thiệu bài toán

Trong môi trường giáo dục đại học, giảng viên thường phải di chuyển giảng dạy giữa nhiều cơ sở khác nhau (Ví dụ: Cơ sở chính Tân Phú, Cơ sở 2 Nguyễn Thị Tú, Trung tâm tin học thực hành, v.v.).

**HUIT Schedule Hub** được thiết kế giải quyết triệt để các bài toán:
1. **Phân lịch theo tiết trống & tối ưu hóa:** Phòng đào tạo dễ dàng phân lịch dựa trên các buổi/tiết trống; thuật toán thông minh phát hiện cảnh báo nguy cơ di chuyển gấp giữa 2 cơ sở liền kề và loại bỏ khoảng trống chết.
2. **Gợi ý giảng viên thông minh (AI / Logic Matching):** Khi xếp lịch một học phần mới, hệ thống tự động quét và gợi ý giảng viên có chuyên môn tương ứng và trống lịch trong ca đó.
3. **Cổng thông tin giảng viên trực tuyến (Teacher Portal):** Giảng viên đăng nhập xem lịch cá nhân trực quan theo tuần/tháng, nắm bắt chính xác phòng học, địa chỉ cơ sở và xuất lịch dạng `.ics` đồng bộ vào Google Calendar / Apple Calendar.
4. **Quản lý & Lọc chung:** Dashboard trực quan, ma trận thời khóa biểu dạng lưới và biểu đồ phân bổ thời gian (Chart.js) kiểm soát cân bằng tải giảng dạy giữa các thầy/cô.

---

## ⚙️ 2. Yêu cầu hệ thống

- **Web Server:** Apache hoặc Nginx (Đề xuất chạy trên Laragon hoặc XAMPP)
- **Ngôn ngữ:** PHP >= 8.1 (Hỗ trợ PHP 8.2, PHP 8.3)
- **Cơ sở dữ liệu:** MySQL 8.x hoặc MariaDB
- **Quản lý gói:** Composer 2.x
- **Git** & **GitHub Actions**

---

## 🚀 3. Hướng dẫn cài đặt & Cấu hình chi tiết

### Bước 1: Khởi tạo Virtual Host trên Laragon
Đặt thư mục dự án trong `C:\laragon\www\huit-schedule` hoặc thư mục sinh viên.
Laragon sẽ tự động nhận diện tên miền ảo:
- `http://huit-schedule.test:8080` (hoặc `http://localhost:8080/NguyenGiaBao_2001230044_CC`)

### Bước 2: Cài đặt thư viện phụ thuộc (Composer)
Mở Terminal / PowerShell tại thư mục dự án và chạy:
```bash
composer install
```
> Dự án sử dụng 2 thư viện mã nguồn mở bắt buộc theo chuẩn Packagist:
> - `vlucas/phpdotenv`: Bảo mật thông số kết nối Database qua file `.env`.
> - `fakerphp/faker`: Sinh dữ liệu mẫu giả lập danh sách giảng viên, tiết trống và lịch giảng dạy.

### Bước 3: Cấu hình biến môi trường (`.env`)
Tạo file `.env` từ file mẫu `.env.example`:
```bash
# Windows PowerShell
copy .env.example .env
```
Mở file `.env` và kiểm tra thông số kết nối MySQL của bạn:
```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=huit_db
DB_USER=root
DB_PASS=

APP_NAME="HUIT Schedule Hub"
APP_ENV=local
APP_URL=http://huit-schedule.test:8080
```

### Bước 4: Tạo cơ sở dữ liệu & Import dữ liệu mẫu
Có 2 cách thực hiện:

#### Cách 4.1: Chạy lệnh Database Seeder bằng PHP (Khuyên dùng)
Hệ thống tích hợp sẵn script tự động tạo CSDL và seed dữ liệu chuẩn hóa với `fakerphp/faker`:
```bash
# Bước a: Tạo schema các bảng
mysql -u root huit_db < database/schema.sql

# Bước b: Chạy seeder tự động sinh dữ liệu mẫu
php database/seed.php
```

#### Cách 4.2: Import trực tiếp file SQL qua HeidiSQL / phpMyAdmin
1. Mở **HeidiSQL** (hoặc phpMyAdmin trong Laragon).
2. Tạo mới Database mang tên `huit_db` với bảng mã `utf8mb4_unicode_ci`.
3. Mở file `database/huit_db.sql` và bấm **Execute (F9)** để import toàn bộ cấu trúc và dữ liệu.

---

## 📁 4. Cấu trúc thư mục dự án

```text
NguyenGiaBao_2001230044_CC/
├── .github/
│   └── workflows/
│       └── ci.yml               # CI/CD GitHub Actions: Tự động kiểm tra cú pháp PHP (php -l)
├── config/
│   └── database.php             # Kết nối PDO & nạp vlucas/phpdotenv
├── database/
│   ├── schema.sql               # File DDL tạo các bảng branches, teachers, schedules
│   ├── seed.php                 # Script FakerPHP sinh dữ liệu mẫu thông minh
│   └── huit_db.sql              # File backup SQL hoàn chỉnh để import nhanh
├── src/
│   ├── Models/
│   │   ├── Branch.php           # Quản lý cơ sở đào tạo (branches)
│   │   ├── Teacher.php          # Quản lý giảng viên (teachers)
│   │   └── Schedule.php         # Quản lý lịch giảng dạy (schedules)
│   └── Services/
│       └── ScheduleOptimizer.php# Thuật toán gợi ý giảng viên, tối ưu di chuyển, phát hiện xung đột
├── api/
│   ├── recommend_teacher.php    # API gợi ý giảng viên theo môn học & tiết trống
│   ├── check_conflict.php       # API kiểm tra trùng phòng / trùng giờ
│   └── stats.php                # API cung cấp số liệu cho Chart.js
├── includes/
│   ├── header.php               # Giao diện thanh điều hướng dùng chung
│   └── footer.php               # Giao diện chân trang
├── index.php                    # Trang tổng quan: KPI, biểu đồ phân bổ, ma trận thời khóa biểu
├── schedules.php                # Quản lý CRUD lịch giảng dạy + Gợi ý AI Live Suggester
├── teachers.php                 # Quản lý CRUD giảng viên
├── branches.php                 # Quản lý CRUD cơ sở đào tạo
├── optimize.php                 # Phân tích tiết trống & tối ưu hóa di chuyển liên cơ sở
├── teacher_portal.php           # Cổng thông tin cá nhân dành riêng cho giảng viên
├── export_ics.php               # Xuất thời khóa biểu định dạng chuẩn .ICS
├── composer.json                # Cấu hình Composer & PSR-4 Autoloading
├── .env.example                 # Mẫu biến môi trường
├── .gitignore                   # Loại trừ vendor/ và .env
├── LICENSE                      # Giấy phép MIT
└── README.md                    # Tài liệu hướng dẫn sử dụng chi tiết
```

---

## 💡 5. Tính năng nổi bật & Hướng dẫn kiểm thử

### 1. Phân lịch với thuật toán gợi ý giảng viên (Live AI Suggester)
- Vào mục **Lịch giảng dạy** -> **Thêm lịch mới** (`schedules.php?action=create`).
- Nhập tên học phần (ví dụ: *"Lập trình Web với PHP"*, *"Cơ sở dữ liệu"* hoặc *"Trí tuệ nhân tạo"*).
- Chọn **Thứ** và **Buổi/Tiết**:
  - Hệ thống bên phải lập tức kích hoạt bộ lọc quét tự động.
  - Phân loại trực quan: 
    - ⭐⭐⭐ **Ưu tiên hàng đầu (Tối ưu)**: Đúng chuyên môn + Đang dạy cùng cơ sở trong ngày (không phải di chuyển xa).
    - ⭐⭐ **Phù hợp cao**: Đúng chuyên môn + Rảnh giờ đó.
    - ⚠️ **Cảnh báo**: Giảng viên có ca liền kề tại cơ sở khác.
    - ❌ **Đã bận**: Bị khóa nút chọn và hiển thị rõ lý do bận môn gì ở phòng nào.
  - Bấm **"Chọn"** để tự động điền nhanh vào form.

### 2. Kiểm tra trùng lặp thời gian & phòng học (Conflict Validation)
- Nếu cố tình xếp 2 lớp cùng phòng trong cùng 1 buổi, hoặc 1 giảng viên dạy 2 lớp cùng giờ:
  - Hệ thống sẽ hiển thị cảnh báo đỏ và ngăn không cho lưu vào CSDL, bảo đảm toàn vẹn dữ liệu.

### 3. Trang Tối ưu hóa & Khai thác tiết trống (`optimize.php`)
- Truy cập menu **"Tối ưu & Tiết trống"**:
  - Xem báo cáo sức khỏe lịch dạy toàn trường.
  - Chọn giảng viên bất kỳ để xem ma trận **"Tiết Trống"** (các ô màu xanh lá).
  - Bấm trực tiếp vào **Tiết Trống** bất kỳ để chuyển thẳng đến màn hình phân lịch cho giảng viên vào đúng khung giờ đó.

### 4. Cổng thông tin Giảng viên (`teacher_portal.php`)
- Bấm vào nút **"Cổng Giảng viên"** ở góc phải menu.
- Đăng nhập bằng Email giảng viên (Mật khẩu mặc định: `password123`) hoặc bấm chọn tài khoản bất kỳ tại danh sách đăng nhập nhanh.
- Xem thời khóa biểu cá nhân, chỉ rõ cơ sở và phòng học.
- Bấm **"Chỉ đường"** để mở vị trí cơ sở trên Google Maps.
- Bấm **"Xuất .ICS"** để tải file lịch dạy tích hợp vào điện thoại / Google Calendar.

---

## 🤖 6. Tự động hóa CI/CD (GitHub Actions)

Dự án được cấu hình quy trình CI/CD hoàn toàn tự động tại file `.github/workflows/ci.yml`:
- Kích hoạt mỗi khi có lệnh `git push` hoặc `pull_request` lên nhánh `main` / `master`.
- Tự động dựng môi trường PHP 8.3 trên máy ảo Ubuntu.
- Xác thực tính hợp lệ của `composer.json`.
- Chạy kiểm tra lỗi cú pháp PHP tự động (`php -l`) trên toàn bộ tệp tin nguồn PHP trong dự án để đảm bảo không có bất kỳ lỗi Syntax Error nào trước khi triển khai.

---

## 📄 7. Bản quyền (License)

Dự án được phát hành theo giấy phép mã nguồn mở **[MIT License](LICENSE)**.
Toàn bộ mã nguồn mở tự do sử dụng và phát triển vì mục đích học tập và nghiên cứu.
