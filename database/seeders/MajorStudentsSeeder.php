<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;

class MajorStudentsSeeder extends Seeder
{
    /**
     * Sinh dữ liệu: mỗi chuyên ngành 20 sinh viên, chia 2 lớp × 10 SV.
     * (3 ngành → 60 SV, 6 lớp). Idempotent theo student_code.
     * CNTT là ngành trọng tâm; KTPM/KHMT là dữ liệu cho hướng phát triển.
     */
    public function run(): void
    {
        $surnames  = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi'];
        $middles   = ['Văn', 'Thị', 'Hữu', 'Đức', 'Minh', 'Quốc', 'Thanh', 'Gia', 'Hoàng', 'Thành'];
        $givens    = ['An', 'Bình', 'Cường', 'Dũng', 'Phong', 'Giang', 'Khánh', 'Lan', 'Mai', 'Nam',
                      'Oanh', 'Phúc', 'Quân', 'Sơn', 'Tâm', 'Uyên', 'Vy', 'Yến', 'Hải', 'Linh'];

        $faculty = Faculty::firstOrCreate(['name' => 'Công nghệ thông tin']);

        // [prefix mã lớp => tên ngành]
        $majorDefs = [
            'CNTT' => 'Công nghệ thông tin',
            'KTPM' => 'Kỹ thuật phần mềm',
            'KHMT' => 'Khoa học máy tính',
        ];
        $cohort = '2024-2028';
        $idx = 0;

        foreach ($majorDefs as $prefix => $majorName) {
            $major = Major::firstOrCreate(['name' => $majorName, 'faculty_id' => $faculty->id]);

            foreach (['A', 'B'] as $cl) {
                $class = SchoolClass::updateOrCreate(
                    ['name' => "{$prefix}2024{$cl}"],
                    ['major_id' => $major->id, 'cohort' => $cohort]
                );

                for ($i = 1; $i <= 10; $i++) {
                    $code  = sprintf('%s24%s%02d', $prefix, $cl, $i); // vd CNTT24A01
                    $name  = $surnames[$idx % count($surnames)] . ' '
                           . $middles[$idx % count($middles)] . ' '
                           . $givens[$idx % count($givens)];

                    User::updateOrCreate(
                        ['student_code' => $code],
                        [
                            'username'  => strtolower($code),
                            'email'     => strtolower($code) . '@sv.test',
                            'password'  => 'password',   // cast 'hashed' tự băm
                            'fullName'  => $name,
                            'class_id'  => $class->id,
                            'is_admin'  => false,
                        ]
                    );
                    $idx++;
                }
            }
        }
    }
}
