<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\App\Models\SkillGroup::where('name', 'Đồ án Chuyên ngành')->update(['name' => 'Đồ án / Thực tập / Khóa luận']);
echo 'SUCCESS';
