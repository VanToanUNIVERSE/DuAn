<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;

class FacultyMajorClassSeeder extends Seeder
{
    /**
     * Tạo Khoa → Chuyên ngành → Lớp và gán sinh viên (từ UserSeeder) vào lớp.
     * Idempotent (updateOrCreate) nên chạy lại nhiều lần vẫn an toàn.
     */
    public function run(): void
    {
        // ── Khoa ──────────────────────────────────────────────────────────────
        $facCNTT = Faculty::updateOrCreate(['name' => 'Công nghệ thông tin']);

        // ── Chuyên ngành (thuộc khoa) ─────────────────────────────────────────
        $mCNTT = Major::updateOrCreate(['name' => 'Công nghệ thông tin', 'faculty_id' => $facCNTT->id]);
        $mKHMT = Major::updateOrCreate(['name' => 'Khoa học máy tính',   'faculty_id' => $facCNTT->id]);
        $mKTPM = Major::updateOrCreate(['name' => 'Kỹ thuật phần mềm',   'faculty_id' => $facCNTT->id]);

        // ── Lớp (thuộc chuyên ngành + niên khóa) ──────────────────────────────
        $cohort = '2024-2028';
        $clsCNTT = SchoolClass::updateOrCreate(['name' => 'CNTT2024A'], ['major_id' => $mCNTT->id, 'cohort' => $cohort]);
        $clsKHMT = SchoolClass::updateOrCreate(['name' => 'KHMT2024A'], ['major_id' => $mKHMT->id, 'cohort' => $cohort]);
        $clsKTPM = SchoolClass::updateOrCreate(['name' => 'KTPM2024A'], ['major_id' => $mKTPM->id, 'cohort' => $cohort]);

        // ── Gán tài khoản ADMIN vào 1 lớp để xem được Lớp/Ngành/Khoa ở sidebar ─
        // (admin là tài khoản hệ thống; sĩ số "10 SV/lớp" là các SV do MajorStudentsSeeder
        //  sinh ra). Các tài khoản test SV001-009 KHÔNG gán lớp để khỏi làm lệch sĩ số.
        User::where('email', 'admin@test.com')->update(['class_id' => $clsCNTT->id]);
    }
}
