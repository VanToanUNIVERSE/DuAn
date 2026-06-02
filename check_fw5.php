<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fw = \App\Models\CurriculumFramework::find(5);
echo json_encode($fw->semesters()->with('subjects')->get());
