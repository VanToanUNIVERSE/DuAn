<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectRelationSeeder extends Seeder
{
    public function run(): void
    {
        // Tra cứu id theo tên môn học
        $getId = fn(string $name) => DB::table('subjects')->where('name', $name)->value('id');

        $lpCB   = $getId('Lập trình căn bản');
        $oop    = $getId('Lập trình hướng đối tượng');
        $ctdl   = $getId('Cấu trúc dữ liệu và giải thuật');
        $web    = $getId('Lập trình Web');
        $mobile = $getId('Phát triển ứng dụng di động');
        $ktmt   = $getId('Kiến trúc máy tính');
        $hdt    = $getId('Hệ điều hành');
        $mmt    = $getId('Mạng máy tính');
        $attt   = $getId('An toàn thông tin');
        $csdl   = $getId('Cơ sở dữ liệu');
        $csdlnc = $getId('Cơ sở dữ liệu nâng cao');
        $cnpm   = $getId('Công nghệ phần mềm');
        $pthttt = $getId('Phân tích và thiết kế hệ thống');
        $ktpm   = $getId('Kiểm thử phần mềm');
        $ttnt   = $getId('Trí tuệ nhân tạo');
        $ml     = $getId('Học máy');

        // type = 'prerequisite': muốn học subject_id thì phải học xong related_subject_id trước
        $relations = [
            // Lập trình hướng đối tượng → tiên quyết: Lập trình căn bản
            ['subject_id' => $oop,    'related_subject_id' => $lpCB,   'type' => 'prerequisite'],
            // Cấu trúc dữ liệu & giải thuật → tiên quyết: Lập trình căn bản
            ['subject_id' => $ctdl,   'related_subject_id' => $lpCB,   'type' => 'prerequisite'],
            // Lập trình Web → tiên quyết: Lập trình hướng đối tượng
            ['subject_id' => $web,    'related_subject_id' => $oop,    'type' => 'prerequisite'],
            // Phát triển ứng dụng di động → tiên quyết: Lập trình hướng đối tượng
            ['subject_id' => $mobile, 'related_subject_id' => $oop,    'type' => 'prerequisite'],
            // Hệ điều hành → tiên quyết: Kiến trúc máy tính
            ['subject_id' => $hdt,    'related_subject_id' => $ktmt,   'type' => 'prerequisite'],
            // Mạng máy tính → tiên quyết: Hệ điều hành
            ['subject_id' => $mmt,    'related_subject_id' => $hdt,    'type' => 'prerequisite'],
            // An toàn thông tin → tiên quyết: Mạng máy tính
            ['subject_id' => $attt,   'related_subject_id' => $mmt,    'type' => 'prerequisite'],
            // Cơ sở dữ liệu nâng cao → tiên quyết: Cơ sở dữ liệu
            ['subject_id' => $csdlnc, 'related_subject_id' => $csdl,   'type' => 'prerequisite'],
            // Công nghệ phần mềm → tiên quyết: Lập trình hướng đối tượng
            ['subject_id' => $cnpm,   'related_subject_id' => $oop,    'type' => 'prerequisite'],
            // Phân tích & thiết kế hệ thống → tiên quyết: Công nghệ phần mềm
            ['subject_id' => $pthttt, 'related_subject_id' => $cnpm,   'type' => 'prerequisite'],
            // Kiểm thử phần mềm → tiên quyết: Công nghệ phần mềm
            ['subject_id' => $ktpm,   'related_subject_id' => $cnpm,   'type' => 'prerequisite'],
            // Trí tuệ nhân tạo → tiên quyết: Cấu trúc dữ liệu & giải thuật
            ['subject_id' => $ttnt,   'related_subject_id' => $ctdl,   'type' => 'prerequisite'],
            // Học máy → tiên quyết: Trí tuệ nhân tạo
            ['subject_id' => $ml,     'related_subject_id' => $ttnt,   'type' => 'prerequisite'],
        ];

        foreach ($relations as $relation) {
            DB::table('subject_relations')->insert(array_merge($relation, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
