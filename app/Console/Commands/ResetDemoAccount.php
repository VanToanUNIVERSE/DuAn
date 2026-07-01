<?php

namespace App\Console\Commands;

use App\Models\SemesterHistory;
use App\Models\SemesterHistoryItem;
use App\Models\StudyPlan;
use App\Models\StudyPlanRevision;
use App\Models\User;
use App\Models\UserGrade;
use Illuminate\Console\Command;

class ResetDemoAccount extends Command
{
    protected $signature = 'demo:reset {email=cntt24a01@sv.test}';

    protected $description = 'Đưa tài khoản demo về trạng thái ban đầu: xoá kế hoạch/điểm/lịch sử và bỏ cấu hình mục tiêu.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user  = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Không tìm thấy tài khoản: {$email}");
            return self::FAILURE;
        }

        StudyPlanRevision::where('user_id', $user->id)->delete();
        UserGrade::where('user_id', $user->id)->delete();
        StudyPlan::where('user_id', $user->id)->forceDelete();

        // Xoá luôn LỊCH SỬ HỌC KỲ: nếu không, hệ thống sẽ tự dựng lại kế hoạch từ lịch sử
        // (HK1 "đã hoàn thành" với điểm cũ) → tài khoản vẫn còn dữ liệu sau khi reset.
        $historyIds = SemesterHistory::where('user_id', $user->id)->pluck('id');
        SemesterHistoryItem::whereIn('semester_history_id', $historyIds)->delete();
        SemesterHistory::where('user_id', $user->id)->delete();

        $user->update([
            'pref_current_semester'    => null,
            'pref_target_years'        => null,
            'pref_graduation_semester' => null,
            'pref_skill_focus'         => null,
        ]);

        $this->info("Đã reset tài khoản demo về trạng thái ban đầu: {$email}");
        return self::SUCCESS;
    }
}
