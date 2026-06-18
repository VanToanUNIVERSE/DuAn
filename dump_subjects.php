<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$curriculumSubjects = \App\Models\CurriculumSubject::with('semester', 'subject')->get();
$dump = [];
foreach ($curriculumSubjects as $cs) {
    if ($cs->subject->name == 'Logic học đại cương') {
        $dump[] = [
            'name' => $cs->subject->name,
            'sem_name' => $cs->semester->name ?? 'null',
            'sem_id' => $cs->semester_id
        ];
    }
}
echo json_encode($dump, JSON_PRETTY_PRINT);
