<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\SemesterHistoryController;
use App\Http\Controllers\UserPreferenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ─── Redirect gốc ───────────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('suggest');
});

// ─── Auth Routes (chỉ cho guest - chưa đăng nhập) ──────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});

// ─── Đăng xuất ──────────────────────────────────────────────────────────────
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Routes cần xác thực (session auth, đã bao gồm CSRF & StartSession) ─────
Route::middleware('auth')->group(function () {

    // ── Trang chính ─────────────────────────────────────────────────────────
    Route::get('/suggest', function () {
        $subjects = App\Models\Subject::with(['subjectType', 'skillGroup', 'programGroup', 'semester'])
            ->get()
            ->groupBy(function ($subject) {
                return $subject->semester?->name ?? 'Môn khác';
            });

        $totalCredits = App\Models\Subject::sum('credits');
        $academicYears = App\Models\TrainingProgram::distinct()->pluck('academic_year')->toArray();
        $programTypes = App\Models\TrainingProgram::distinct()->pluck('program_type')->toArray();

        return view('suggest', compact('subjects', 'academicYears', 'programTypes', 'totalCredits'));
    })->name('suggest');

    // ── Routes điểm số ──────────────────────────────────────────────────────
    // Đặt trong web.php (KHÔNG phải api.php) vì:
    //   • api middleware group thiếu StartSession & VerifyCsrfToken
    //   • auth:web cần session → phải nằm trong web middleware group
    //   • CSRF token từ <meta name="csrf-token"> được JS gửi qua X-CSRF-TOKEN header

    // GET  /grades        → Tải toàn bộ điểm đã lưu của user hiện tại
    Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');

    // POST /grades/save   → Lưu (upsert) một hoặc nhiều điểm vào DB
    Route::post('/grades/save', [GradeController::class, 'save'])->name('grades.save');

    // GET  /grades/chart-data → Dữ liệu biểu đồ so sánh điểm cá nhân vs. TB khóa
    Route::get('/grades/chart-data', [GradeController::class, 'chartData'])->name('grades.chart');

    // ── Routes cấu hình chương trình (Niên khóa, Hệ đào tạo, Học kỳ, Mục tiêu) ─
    // Lưu lựa chọn dropdown của user để khôi phục khi đăng nhập lại.

    // GET  /preferences        → Lấy cấu hình đã lưu
    Route::get('/preferences', [UserPreferenceController::class, 'index'])->name('preferences.index');

    // POST /preferences/save   → Lưu cấu hình mới
    Route::post('/preferences/save', [UserPreferenceController::class, 'save'])->name('preferences.save');

    // ── Routes lịch sử học kỳ ───────────────────────────────────────────────
    // GET  /semester-history          → Lấy toàn bộ lịch sử
    Route::get('/semester-history', [SemesterHistoryController::class, 'index'])->name('semester-history.index');

    // POST /semester-history/complete → Lưu khi hoàn tất học kỳ
    Route::post('/semester-history/complete', [SemesterHistoryController::class, 'complete'])->name('semester-history.complete');
});

