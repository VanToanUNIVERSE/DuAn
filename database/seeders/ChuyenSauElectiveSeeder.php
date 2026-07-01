<?php

namespace Database\Seeders;

use App\Models\CurriculumFramework;
use App\Models\ElectiveGroup;
use App\Models\ProgramGroup;
use App\Models\SkillGroup;
use App\Models\Subject;
use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bổ sung một ít môn TỰ CHỌN khối chuyên sâu (theo CTĐT Cần Thơ, mã CT3xxH)
 * vào khung 2024-2028 Chính quy. Idempotent — tra theo tên/mã, chạy lại an toàn.
 *
 * Wiring đúng như các nhóm tự chọn sẵn có:
 *   subjects(is_elective) + curriculum_subject(fw, semester, elective_group_id)
 *   + elective_group_subjects(pivot).
 */
class ChuyenSauElectiveSeeder extends Seeder
{
    public function run(): void
    {
        $tp = TrainingProgram::where('academic_year', '2024-2028')
            ->where('program_type', 'Chính quy')->first();
        if (!$tp) { $this->command?->warn('[ChuyenSau] Không thấy CTĐT 2024-2028 Chính quy — bỏ qua.'); return; }

        $fw = CurriculumFramework::where('training_program_id', $tp->id)->first();
        if (!$fw) { $this->command?->warn('[ChuyenSau] Không thấy khung CTĐT — bỏ qua.'); return; }

        // Đặt nhóm tự chọn chuyên sâu vào học kỳ 7 của khung
        $semId = DB::table('semesters')
            ->where('curriculum_framework_id', $fw->id)->where('name', '7')->value('id');

        $pgChuyenSau = ProgramGroup::where('name', 'like', '%chuyên sâu%')->value('id');
        $sg = fn(string $name) => SkillGroup::where('name', $name)->value('id');

        $group = ElectiveGroup::firstOrCreate(
            ['curriculum_framework_id' => $fw->id, 'name' => 'Chuyên ngành chuyên sâu (tự chọn)'],
            ['required_credits' => 6]
        );

        $items = [
            ['code' => 'CT301H', 'name' => 'An ninh mạng',                                   'sg' => 'Hệ thống Máy tính'],
            ['code' => 'CT305H', 'name' => 'Lập trình mạng',                                  'sg' => 'Hệ thống Máy tính'],
            ['code' => 'CT309H', 'name' => 'Hệ thống hoạch định nguồn lực doanh nghiệp',      'sg' => 'Công nghệ Ứng dụng'],
            ['code' => 'CT311H', 'name' => 'Phát triển ứng dụng Java',                        'sg' => 'Lập trình và Kỹ nghệ Phần mềm'],
            ['code' => 'CT313H', 'name' => 'Công nghệ và dịch vụ Web',                        'sg' => 'Lập trình và Kỹ nghệ Phần mềm'],
        ];

        foreach ($items as $it) {
            $subject = Subject::updateOrCreate(
                ['subject_code' => $it['code']],
                [
                    'name'             => $it['name'],
                    'credits'          => 3,
                    'is_elective'      => true,
                    'requirement_type' => 'none',
                    'skill_group_id'   => $sg($it['sg']),
                    'program_group_id' => $pgChuyenSau,
                ]
            );

            // curriculum_subject (khung + học kỳ + nhóm tự chọn)
            $exists = DB::table('curriculum_subject')
                ->where('curriculum_framework_id', $fw->id)
                ->where('subject_id', $subject->id)->exists();
            if (!$exists) {
                DB::table('curriculum_subject')->insert([
                    'curriculum_framework_id' => $fw->id,
                    'subject_id'              => $subject->id,
                    'semester_id'             => $semId,
                    'elective_group_id'       => $group->id,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);
            } else {
                DB::table('curriculum_subject')
                    ->where('curriculum_framework_id', $fw->id)
                    ->where('subject_id', $subject->id)
                    ->update(['semester_id' => $semId, 'elective_group_id' => $group->id, 'updated_at' => now()]);
            }

            // pivot elective_group_subjects
            $inPivot = DB::table('elective_group_subjects')
                ->where('elective_group_id', $group->id)
                ->where('subject_id', $subject->id)->exists();
            if (!$inPivot) {
                DB::table('elective_group_subjects')->insert([
                    'elective_group_id' => $group->id,
                    'subject_id'        => $subject->id,
                ]);
            }
        }

        $this->command?->info('[ChuyenSau] Đã thêm '.count($items).' môn tự chọn vào nhóm #'.$group->id.' (cần '.$group->required_credits.' TC).');
    }
}
