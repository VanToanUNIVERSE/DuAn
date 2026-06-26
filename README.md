# AcademiaLink — Smart Student Planner

Hệ thống lập kế hoạch học tập thông minh cho sinh viên, kết hợp công cụ quản lý chương trình đào tạo cho cán bộ quản lý đào tạo.

---

## Tổng quan

AcademiaLink giải quyết hai bài toán song song:

| Actor | Bài toán |
|---|---|
| **Sinh viên** | Lập lộ trình học tập cá nhân theo đúng chuẩn đầu ra, theo dõi điểm và tiến độ, nhận gợi ý môn học |
| **Cán bộ quản lý đào tạo** | Nắm số lượng sinh viên đăng ký từng học phần theo kỳ để lên kế hoạch mở lớp |

---

## Tính năng chính

### Phía sinh viên (`/suggest`)

- **Lập kế hoạch đa học kỳ** — tự động rải môn học theo số tín chỉ mục tiêu/kỳ, xử lý môn tiên quyết và song hành
- **Nhóm tự chọn (Elective Group)** — hiển thị toàn bộ phương án trong khung chung, sinh viên tự chọn môn muốn học, giới hạn TC theo quy định nhóm
- **Kéo thả môn học** giữa các học kỳ (kéo cả nhóm tự chọn cùng lúc)
- **Nhập điểm theo kỳ** — tự động tính GPA, phân loại Pass/Fail, cảnh báo rớt môn
- **Thống kê TC bắt buộc / tự chọn** theo từng học kỳ
- **Dự báo tốt nghiệp** — ước tính số kỳ còn lại, GPA tích lũy, khả năng hoàn thành sớm/trễ
- **Gợi ý môn học** thông minh (dựa trên tiên quyết, nhóm kỹ năng, tải TC)
- **Tư vấn điều chỉnh lộ trình** khi phát hiện lệch kế hoạch
- **Cảnh báo tự động** (quá tải TC, môn chưa qua tiên quyết, nguy cơ rớt)
- **Lịch sử kỳ học** — lưu snapshot điểm từng kỳ

### Phía admin (`/admin`)

- **Quản lý môn học** — CRUD, import hàng loạt từ file Excel (`.xlsx`), phân loại bắt buộc/tự chọn, nhóm tự chọn
- **Chương trình đào tạo** — tạo mới, sao chép toàn bộ phân công môn từ chương trình cũ
- **Phân công môn học** — giao diện chọn môn vào từng học kỳ, phân công tự động, nhóm tự chọn đi kèm nhau
- **Thống kê đăng ký học phần** — số SV đăng ký từng môn theo kỳ, lọc theo chương trình, xuất CSV để lập lịch mở lớp

---

## Công nghệ

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.1, Laravel 10 |
| Database | MySQL |
| Frontend | Vanilla JS, CSS tùy chỉnh (không dùng framework JS) |
| Import Excel | [Maatwebsite/Laravel-Excel](https://laravel-excel.com/) v3.1 |
| Auth | Laravel Session Auth + Middleware `admin` |

---

## Cài đặt

### Yêu cầu

- PHP >= 8.1 với các extension: `pdo_mysql`, `mbstring`, `zip`, `gd`
- Composer
- MySQL >= 5.7

### Các bước

```bash
# 1. Clone repo
git clone <repo-url>
cd DuAn

# 2. Cài dependencies PHP
composer install

# 3. Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# 4. Cấu hình database trong .env
#    DB_DATABASE=academialink
#    DB_USERNAME=root
#    DB_PASSWORD=...

# 5. Chạy migration
php artisan migrate

# 6. Khởi động server
php artisan serve
```

Truy cập: `http://127.0.0.1:8000`

---

## Cấu trúc thư mục quan trọng

```
app/
├── Http/Controllers/
│   ├── Admin/
│   │   ├── SubjectController             # Quản lý môn học
│   │   ├── TrainingProgramController     # Chương trình đào tạo
│   │   ├── CurriculumSubjectController   # Phân công môn → học kỳ
│   │   ├── EnrollmentStatsController     # Thống kê đăng ký học phần
│   │   └── DashboardController
│   └── Api/
│       ├── StudyPlanController           # CRUD kế hoạch, di chuyển môn, điểm
│       ├── RecommendationController      # Gợi ý môn học
│       ├── GraduationForecastController
│       ├── ProgressController
│       └── CascadeController            # Phân tích ảnh hưởng khi đổi môn
│
├── Models/
│   ├── TrainingProgram         # Chương trình đào tạo
│   ├── CurriculumFramework     # Khung chương trình
│   ├── Semester                # Học kỳ trong khung
│   ├── CurriculumSubject       # Pivot: môn phân công vào kỳ
│   ├── ElectiveGroup           # Nhóm môn tự chọn
│   ├── Subject / SubjectRelation
│   ├── StudyPlan / StudyPlanSemester / StudyPlanSubject
│   ├── UserGrade               # Bảng điểm thực tế
│   └── Warning                 # Cảnh báo học tập
│
├── Services/
│   ├── StudyPlanService        # Logic tạo / tự động rải kế hoạch
│   ├── AcademicEvaluationService
│   ├── GraduationAdvisorService
│   ├── RecommendationService
│   ├── CascadeAnalysisService
│   ├── ProgressService
│   └── PlanAdjustmentService
│
└── Imports/
    └── SubjectsImport          # Import môn học từ Excel (3-pass)

public/
├── js/student-planner.js       # Toàn bộ logic frontend student planner
└── css/student-planner.css

resources/views/admin/
├── layouts/app.blade.php
├── subjects/
├── training-programs/
├── curriculum/
└── enrollment-stats/
```

---

## Import môn học từ Excel

Vào **Admin → Môn học → Import Excel**.

File Excel cần các cột:

| Cột | Bắt buộc | Mô tả | Ví dụ |
|---|---|---|---|
| `subject_code` | ✅ | Mã môn | `CT103H` |
| `subjects` | ✅ | Tên môn | `Nhập môn máy học và CNTT` |
| `credits` | | Số tín chỉ | `3` |
| `is_elective` | | Loại môn | `Bắt buộc` / `Tự chọn` |
| `elective_group` | | Tên nhóm tự chọn | `1` |
| `required_credits` | | TC cần đạt của nhóm | `4` |
| `prerequisite` | | Mã tiên quyết (phân cách `,`) | `CT103H,CT054H` |
| `corequisite` | | Mã song hành | `CT054H_TH` |
| `skill_group` | | Nhóm kỹ năng | `Lập trình và Kỹ nghệ PM` |
| `program_group` | | Nhóm chương trình | `Khoa học Máy tính` |

> Tải file mẫu tại: **Admin → Môn học → Tải file mẫu**

---

## API chính (Student Planner)

Tất cả route `/api/v1/*` dùng session auth (`middleware web`).

| Method | Endpoint | Chức năng |
|---|---|---|
| `POST` | `/api/v1/study-plans/generate` | Tạo kế hoạch tự động |
| `GET` | `/api/v1/study-plans/active` | Lấy kế hoạch đang hoạt động |
| `POST` | `/api/v1/study-plans/update-grade` | Cập nhật điểm môn học |
| `POST` | `/api/v1/study-plans/move-subject` | Di chuyển môn sang kỳ khác |
| `POST` | `/api/v1/study-plans/toggle-elective` | Chọn / bỏ chọn môn tự chọn |
| `POST` | `/api/v1/study-plans/add-retake` | Thêm môn học lại |
| `GET` | `/api/v1/recommendations` | Gợi ý môn học |
| `GET` | `/api/v1/graduation-forecast` | Dự báo tốt nghiệp |
| `GET` | `/api/v1/progress` | Tiến độ tích lũy TC & GPA |
| `GET` | `/api/v1/warnings` | Danh sách cảnh báo |
| `GET` | `/api/v1/cascade-impact/{subjectId}` | Phân tích ảnh hưởng kéo môn |

---

## Phân quyền

| Role | Truy cập |
|---|---|
| Sinh viên | `/suggest`, `/api/v1/*`, `/grades/*` |
| Admin | `/admin/*` — kiểm tra qua middleware `admin` (`users.is_admin = true`) |

---

## Thống kê đăng ký học phần

**Admin → Thống kê đăng ký HP** tổng hợp số sinh viên đăng ký từng học phần theo kỳ từ kế hoạch đang hoạt động:

- Lọc theo chương trình đào tạo, học kỳ, số SV tối thiểu
- Thanh tỷ lệ nhu cầu màu sắc (xanh lá = nhu cầu cao, vàng = vừa, xám = thấp)
- Xuất **CSV** để đưa vào công cụ lập lịch mở lớp

---

## Lệnh hay dùng

```bash
php artisan serve                   # Chạy dev server
php artisan migrate:fresh --seed    # Reset DB và seed lại
php artisan route:list              # Xem toàn bộ route
php artisan tinker                  # REPL tương tác
php artisan queue:work              # Chạy queue worker (nếu có)
```
