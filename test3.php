<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new App\Services\SuggestionService();
$result = $service->suggestSubjects(null, 1, '2022-2026', 'Chính quy', []);
foreach($result as $subject) {
    if (strpos($subject->name, 'Lập trình căn bản') !== false) {
        echo "SUBJECT: " . $subject->name . "\n";
        echo "COREQS: " . json_encode($subject->corequisites_info) . "\n";
    }
}
