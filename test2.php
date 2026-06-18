<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cs = \App\Models\CurriculumSubject::with('semester')->get();
$counts = [];
foreach ($cs as $c) {
    $sem = $c->semester ? $c->semester->name : 'null';
    $counts[$sem] = ($counts[$sem] ?? 0) + 1;
}
ksort($counts);
print_r($counts);
