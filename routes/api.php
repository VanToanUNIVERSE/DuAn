<?php

use App\Http\Controllers\SuggestionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ─── Route lấy thông tin user (Sanctum token-based) ─────────────────────────
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ─── Route gợi ý môn học (không cần đăng nhập) ──────────────────────────────
Route::get('/suggestions', [SuggestionController::class, 'index']);

// ─── LuanVan New API Routes ───────────────────────────────────────────────
Route::prefix('v1')->middleware(['web', 'auth'])->group(function () {
    Route::get('/recommendations', [\App\Http\Controllers\Api\RecommendationController::class, 'index']);
    Route::get('/graduation-forecast', [\App\Http\Controllers\Api\GraduationForecastController::class, 'index']);

    Route::prefix('study-plans')->group(function () {
        // CRUD
        Route::get('/',       [\App\Http\Controllers\Api\StudyPlan\StudyPlanController::class, 'index']);
        Route::get('/active', [\App\Http\Controllers\Api\StudyPlan\StudyPlanController::class, 'getActivePlan']);
        Route::get('/saved',  [\App\Http\Controllers\Api\StudyPlan\StudyPlanController::class, 'getSavedPlans']);
        Route::post('/generate', [\App\Http\Controllers\Api\StudyPlan\StudyPlanController::class, 'generate']);
        Route::post('/{id}/save',   [\App\Http\Controllers\Api\StudyPlan\StudyPlanController::class, 'savePlan']);
        Route::get('/{id}/load',    [\App\Http\Controllers\Api\StudyPlan\StudyPlanController::class, 'loadPlan']);
        Route::delete('/{id}',      [\App\Http\Controllers\Api\StudyPlan\StudyPlanController::class, 'destroy']);

        // Điểm số
        Route::post('/update-grade', [\App\Http\Controllers\Api\StudyPlan\StudyPlanGradeController::class, 'updateGrade']);

        // Thao tác môn học
        Route::post('/move-subject',      [\App\Http\Controllers\Api\StudyPlan\StudyPlanSubjectController::class, 'moveSubject']);
        Route::post('/toggle-elective',   [\App\Http\Controllers\Api\StudyPlan\StudyPlanSubjectController::class, 'toggleElective']);
        Route::post('/apply-suggestions', [\App\Http\Controllers\Api\StudyPlan\StudyPlanSubjectController::class, 'applySuggestions']);
        Route::post('/add-retake',        [\App\Http\Controllers\Api\StudyPlan\StudyPlanSubjectController::class, 'addRetake']);
        Route::post('/{id}/dedup-retakes', [\App\Http\Controllers\Api\StudyPlan\StudyPlanSubjectController::class, 'dedupRetakes']);

        // Advisory & mục tiêu
        Route::get('/{id}/advisory',        [\App\Http\Controllers\Api\StudyPlan\StudyPlanAdvisoryController::class, 'advisory']);
        Route::post('/{id}/apply-advisory', [\App\Http\Controllers\Api\StudyPlan\StudyPlanAdvisoryController::class, 'applyAdvisory']);
        Route::post('/{id}/adjust-target',  [\App\Http\Controllers\Api\StudyPlan\StudyPlanAdvisoryController::class, 'adjustTarget']);
    });


    Route::get('/progress',   [\App\Http\Controllers\Api\ProgressController::class, 'index']);
    Route::get('/gpa-trend',  [\App\Http\Controllers\Api\ProgressController::class, 'gpaTrend']);
    Route::get('/warnings',   [\App\Http\Controllers\Api\ProgressController::class, 'warnings']);
    Route::post('/warnings/{id}/read', [\App\Http\Controllers\Api\ProgressController::class, 'markWarningRead']);

    Route::get('/cascade-impact/{subjectId}',   [\App\Http\Controllers\Api\CascadeController::class, 'show']);
    Route::post('/cascade-impact/multiple',     [\App\Http\Controllers\Api\CascadeController::class, 'multiple']);
});

// LƯU Ý: Các route lưu/tải điểm (grades) đã được chuyển sang routes/web.php
// vì chúng cần session authentication (middleware 'web') để hoạt động đúng.
// Xem thêm: routes/web.php phần 'Routes điểm số'.
