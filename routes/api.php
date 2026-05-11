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

// LƯU Ý: Các route lưu/tải điểm (grades) đã được chuyển sang routes/web.php
// vì chúng cần session authentication (middleware 'web') để hoạt động đúng.
// Xem thêm: routes/web.php phần 'Routes điểm số'.


