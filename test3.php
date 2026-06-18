<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$counts = [];
$all = \App\Models\Subject::all();
foreach ($all as $c) {
    $sem = $c->semester_id ?: 'null';
    $counts[$sem] = ($counts[$sem] ?? 0) + 1;
}
ksort($counts);
print_r($counts);
