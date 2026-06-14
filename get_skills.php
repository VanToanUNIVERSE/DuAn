<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$skills = \App\Models\SkillGroup::all()->pluck('name', 'id')->toArray();
echo json_encode($skills, JSON_UNESCAPED_UNICODE);
