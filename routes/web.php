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
        $subjects = App\Models\Subject::with(['skillGroup', 'programGroup', 'assignedSemesters'])
            ->get()
            ->groupBy(function ($subject) {
                return $subject->assignedSemesters->first()?->name ?? 'Khác';
            })->sortBy(function ($group, $key) {
                // Để "Khác" xuống cuối, các số thì sort theo số
                return $key === 'Khác' ? 999 : (int)$key;
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

// ─── Admin Routes ────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Quản lý môn học
    Route::delete('/subjects/delete-all',    [App\Http\Controllers\Admin\SubjectController::class, 'deleteAll'])->name('subjects.deleteAll');
    Route::get('/subjects/import',           [App\Http\Controllers\Admin\SubjectController::class, 'importForm'])->name('subjects.import.form');
    Route::post('/subjects/import',          [App\Http\Controllers\Admin\SubjectController::class, 'import'])->name('subjects.import');
    Route::get('/subjects/template-download',[App\Http\Controllers\Admin\SubjectController::class, 'downloadTemplate'])->name('subjects.template');
    Route::resource('/subjects',              App\Http\Controllers\Admin\SubjectController::class)->names('subjects');

    // Quản lý Skill Groups
    Route::get('/skill-groups',            [App\Http\Controllers\Admin\SkillGroupController::class, 'index'])->name('skill-groups.index');
    Route::post('/skill-groups',           [App\Http\Controllers\Admin\SkillGroupController::class, 'store'])->name('skill-groups.store');
    Route::put('/skill-groups/{skillGroup}',    [App\Http\Controllers\Admin\SkillGroupController::class, 'update'])->name('skill-groups.update');
    Route::delete('/skill-groups/{skillGroup}', [App\Http\Controllers\Admin\SkillGroupController::class, 'destroy'])->name('skill-groups.destroy');

    // Quản lý Program Groups
    Route::get('/program-groups',               [App\Http\Controllers\Admin\ProgramGroupController::class, 'index'])->name('program-groups.index');
    Route::post('/program-groups',              [App\Http\Controllers\Admin\ProgramGroupController::class, 'store'])->name('program-groups.store');
    Route::put('/program-groups/{programGroup}',    [App\Http\Controllers\Admin\ProgramGroupController::class, 'update'])->name('program-groups.update');
    Route::delete('/program-groups/{programGroup}', [App\Http\Controllers\Admin\ProgramGroupController::class, 'destroy'])->name('program-groups.destroy');

    // Quản lý Chương trình đào tạo
    Route::resource('/training-programs', App\Http\Controllers\Admin\TrainingProgramController::class)->except(['create', 'show', 'edit']);

    // Phân công môn học vào học kỳ theo chương trình đào tạo
    Route::get('/curriculum',                                           [App\Http\Controllers\Admin\CurriculumSubjectController::class, 'index'])->name('curriculum.index');
    Route::get('/curriculum/{curriculumFramework}',                     [App\Http\Controllers\Admin\CurriculumSubjectController::class, 'show'])->name('curriculum.show');
    Route::post('/curriculum/{curriculumFramework}/assign',             [App\Http\Controllers\Admin\CurriculumSubjectController::class, 'assign'])->name('curriculum.assign');
    Route::post('/curriculum/{curriculumFramework}/auto-assign',           [App\Http\Controllers\Admin\CurriculumSubjectController::class, 'autoAssign'])->name('curriculum.auto-assign');
    Route::delete('/curriculum/{curriculumFramework}/remove/{assignment}', [App\Http\Controllers\Admin\CurriculumSubjectController::class, 'remove'])->name('curriculum.remove');
    Route::delete('/curriculum/{curriculumFramework}/semester/{semester}/clear', [App\Http\Controllers\Admin\CurriculumSubjectController::class, 'clearSemester'])->name('curriculum.clear-semester');
    Route::delete('/curriculum/{curriculumFramework}/clear-all', [App\Http\Controllers\Admin\CurriculumSubjectController::class, 'clearAll'])->name('curriculum.clear-all');
});


