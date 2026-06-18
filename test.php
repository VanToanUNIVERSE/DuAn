<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$maxSem = \App\Models\Subject::max('semester_id');
echo "Max semester_id: " . $maxSem . "\n";
