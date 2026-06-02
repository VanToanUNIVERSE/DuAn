<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tp = \App\Models\TrainingProgram::where('academic_year', '2024-2028')->where('program_type', 'Chính quy')->first();
echo json_encode($tp ? $tp->curriculumFrameworks()->first() : null);
