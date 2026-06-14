<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sub = App\Models\Subject::find(304);
$corequisites = App\Models\SubjectRelation::where('type', 'corequisite')
    ->where(function($query) use ($sub) {
        $query->where('subject_id', $sub->id)
              ->orWhere('related_subject_id', $sub->id);
    })
    ->with(['subject', 'relatedSubject'])
    ->get();
$coreqDetails = [];
foreach ($corequisites as $coreq) {
    $related = ($coreq->subject_id == $sub->id) ? $coreq->relatedSubject : $coreq->subject;
    if ($related) {
        $coreqDetails[] = [
            'id' => $related->id,
            'code' => $related->subject_code,
            'name' => $related->name,
            'credits' => $related->credits,
        ];
    }
}
$sub->corequisites_info = $coreqDetails;
echo json_encode($sub->toArray());
