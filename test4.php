<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\StudyPlanService();
$plan = $service->generatePlan(1, 'Test Normal', 'normal');
echo "Target semester count: " . $plan->target_semester_count . "\n";
foreach ($plan->semesters as $sem) {
    echo "Semester " . $sem->semester_index . ": " . count($sem->subjects) . " subjects\n";
}
