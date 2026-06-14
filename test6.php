<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new App\Services\SuggestionService();
$result = $service->suggestSubjects(null, 1, '2022-2026', 'Chính quy', []);
echo json_encode($result);
