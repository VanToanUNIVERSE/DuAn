<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SkillGroup;
use App\Models\Subject;

$mapping = [
    'Khoa học Cơ bản' => [1],
    'Lý luận Chính trị' => [13],
    'Ngoại ngữ - Kỹ năng' => [8, 14],
    'Hệ thống Máy tính' => [3, 4, 11],
    'Lập trình và Kỹ nghệ Phần mềm' => [2, 6],
    'Khoa học Dữ liệu' => [5, 7],
    'Công nghệ Ứng dụng' => [9, 10],
    'Đồ án Chuyên ngành' => [12]
];

DB::beginTransaction();
try {
    $oldGroupIds = [];
    foreach ($mapping as $name => $oldIds) {
        $oldGroupIds = array_merge($oldGroupIds, $oldIds);
        
        $group = SkillGroup::create(['name' => $name, 'description' => '']);
        
        Subject::whereIn('skill_group_id', $oldIds)->update(['skill_group_id' => $group->id]);
    }
    
    SkillGroup::whereIn('id', $oldGroupIds)->delete();
    
    DB::commit();
    echo "SUCCESS: Converted 14 skill groups into 8 core groups.";
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage();
}
