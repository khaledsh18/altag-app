<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = \App\Models\Student::where('name', 'like', '%عبيد علي عاطف%')->first();
if ($student) {
    echo "Student ID: " . $student->id . "\n";
    $days = \App\Models\StudentPlanDay::whereHas('plan', function($q) use ($student) {
        $q->where('student_id', $student->id);
    })->orderBy('date', 'desc')->take(10)->get();
    
    foreach ($days as $day) {
        echo "ID: " . $day->id . " | Date: " . $day->date . " | Hifz: " . $day->hifz_achievement . " | HifzGradedAt: " . $day->hifz_graded_at . "\n";
    }
} else {
    echo "Student not found.\n";
}
