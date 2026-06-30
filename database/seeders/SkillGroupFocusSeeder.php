<?php

namespace Database\Seeders;

use App\Models\SkillGroup;
use Illuminate\Database\Seeder;

class SkillGroupFocusSeeder extends Seeder
{
    /**
     * Gán định hướng chuyên ngành (focus_area) cho các nhóm kỹ năng kỹ thuật,
     * tương ứng các định hướng CNTT trong báo cáo (mục 2.2.2).
     *
     * Dùng cho điểm K(s): môn thuộc nhóm trùng định hướng sinh viên chọn được
     * cộng ưu tiên (RecommendationService +40, SchedulerService +80).
     * Idempotent — cập nhật theo tên nhóm, chạy lại nhiều lần vẫn an toàn.
     */
    public function run(): void
    {
        $map = [
            'Lập trình và Kỹ nghệ Phần mềm' => 'software',
            'Khoa học Dữ liệu'              => 'data',
            'Hệ thống Máy tính'             => 'security',
            'Công nghệ Ứng dụng'            => 'application',
        ];

        foreach ($map as $name => $focus) {
            SkillGroup::where('name', $name)->update(['focus_area' => $focus]);
        }
    }
}
