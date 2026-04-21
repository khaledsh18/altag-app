<?php

use App\Livewire\Auth\Manager\Login;
use App\Livewire\Auth\Manager\Register;
use App\Livewire\Manager\PendingApprovals;
use App\Models\Student;
use App\Models\StudentPlan;
use App\Models\Surah;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/pending-approval', fn () => view('pending-approval'))
    ->middleware('auth:manager,supervisor,teacher,student,guardian')
    ->name('pending-approval');

Route::post('logout', function (Request $request) {
    $guard = request()->route('guard');

    if ($guard) {
        auth()->guard($guard)->logout();
    } else {
        $guards = ['student', 'manager', 'supervisor', 'teacher', 'guardian', 'web'];

        foreach ($guards as $guard) {
            if (auth()->guard($guard)->check()) {
                auth()->guard($guard)->logout();
            }
        }
    }

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');

$roles = [
    'manager' => 'مدير',
    'supervisor' => 'مشرف',
    'teacher' => 'معلم',
    'student' => 'طالب',
    'guardian' => 'ولي أمر',
];

Route::middleware('auth:manager,supervisor,teacher,student,guardian')->group(function () use ($roles) {
    Route::get('dashboard', function () use ($roles) {
        foreach (array_keys($roles) as $roleKey) {
            if (auth()->guard($roleKey)->check()) {
                return redirect()->route("{$roleKey}.dashboard");
            }
        }

        return redirect()->route('home');
    })->name('dashboard');
});

Route::middleware(['auth:manager', 'approved'])->prefix('manager')->name('manager.')->group(function () {
    Route::livewire('/pending-approvals', PendingApprovals::class)->name('pending-approvals');
    Route::view('/stages', 'manager.stages')->name('stages');
    Route::view('/circles', 'manager.circles')->name('circles');
    Route::view('/supervisors', 'manager.supervisors')->name('supervisors');
    Route::view('/teachers', 'manager.teachers')->name('teachers');
    Route::view('/students', 'manager.students')->name('students');
    Route::view('/guardians', 'manager.guardians')->name('guardians');
    Route::view('/attendance-reports', 'manager.attendance-reports')->name('attendance-reports');
    Route::view('/yearly-attendance', 'manager.yearly-attendance')->name('yearly-attendance');
    Route::view('/attendance/{circleId}/{date}', 'manager.student-attendance-list')->name('attendance-list');
    Route::view('/ai-analysis', 'manager.ai-analysis')->name('ai-analysis');
    Route::view('/quran-editor', 'manager.quran-editor')->name('quran-editor');
    Route::view('/settings', 'manager.settings')->name('settings');
    Route::view('/exceeded-limits', 'manager.exceeded-limits')->name('exceeded-limits');
});

// القاسم المشترك لمسارات الضيوف (Guest Routes) لكل دور
Route::middleware('guest:manager')->prefix('manager')->name('manager.')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('guest:supervisor')->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::get('/login', App\Livewire\Auth\Supervisor\Login::class)->name('login');
    Route::get('/register', App\Livewire\Auth\Supervisor\Register::class)->name('register');
});

Route::middleware('guest:teacher')->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/login', App\Livewire\Auth\Teacher\Login::class)->name('login');
    Route::get('/register', App\Livewire\Auth\Teacher\Register::class)->name('register');
});

Route::middleware('guest:student')->prefix('student')->name('student.')->group(function () {
    Route::get('/login', App\Livewire\Auth\Student\Login::class)->name('login');
    Route::get('/register', App\Livewire\Auth\Student\Register::class)->name('register');
});

Route::middleware('guest:guardian')->prefix('parent')->name('parent.')->group(function () {
    Route::get('/login', App\Livewire\Auth\Guardian\Login::class)->name('login');
    Route::get('/register', App\Livewire\Auth\Guardian\Register::class)->name('register');
});

// مسارات لوحة التحكم (Dashboard Routes) لكل دور
Route::middleware(['auth:manager', 'approved'])->get('/manager/dashboard', fn () => view('manager.dashboard'))->name('manager.dashboard');
Route::middleware(['auth:supervisor', 'approved'])->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::get('/dashboard', fn () => view('supervisor.dashboard'))->name('dashboard');
    Route::view('/exceeded-limits', 'supervisor.exceeded-limits')->name('exceeded-limits');
});
Route::middleware(['auth:teacher', 'approved'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', fn () => view('teacher.dashboard'))->name('dashboard');
    Route::view('/attendance', 'teacher.attendance')->name('attendance');
    Route::view('/discipline', 'teacher.discipline')->name('discipline');
    Route::view('/quranic-discipline', 'teacher.quranic-discipline')->name('quranic-discipline');
    Route::view('/students', 'teacher.students')->name('students');
    Route::view('/plan-creator', 'teacher.plan-creator')->name('plan-creator');
    Route::view('/student-plans', 'teacher.student-plans')->name('student-plans');
    Route::view('/tasmeeh', 'teacher.tasmeeh')->name('tasmeeh');
    Route::view('/exceeded-limits', 'teacher.exceeded-limits')->name('exceeded-limits');
    Route::get('/student-plans/{id}/print', function ($id) {
        $plan = StudentPlan::with([
            'student.circle',
            'days.fromAyah.surah',
            'days.toAyah.surah',
            'days.reviewFromAyah.surah',
            'days.reviewToAyah.surah',
        ])->findOrFail($id);

        if ($plan->teacher_id !== auth()->guard('teacher')->id()) {
            abort(403);
        }

        return view('teacher.print-plan', compact('plan'));
    })->name('print-plan');
});
Route::middleware(['auth:student', 'approved'])->get('/student/dashboard', fn () => view('student.dashboard'))->name('student.dashboard');
Route::view('/student/complete-profile', 'student.complete-profile')->middleware(['auth:student'])->name('student.complete-profile');
Route::view('/teacher/complete-profile', 'teacher.complete-profile')->middleware(['auth:teacher'])->name('teacher.complete-profile');
Route::middleware(['auth:guardian', 'approved'])->get('/parent/dashboard', fn () => view('guardian.dashboard'))->name('guardian.dashboard');

// Magic Link Routes
Route::get('/magic/{token}', function ($token) {
    $student = Student::where('access_token', $token)->firstOrFail();

    auth()->guard('student')->login($student);

    if (! $student->is_data_completed) {
        return redirect()->route('student.complete-profile');
    }

    return redirect()->route('student.dashboard');
})->name('magic-link');

Route::get('/teacher-magic/{token}', function ($token) {
    $teacher = Teacher::where('access_token', $token)->firstOrFail();

    auth()->guard('teacher')->login($teacher);

    if (! $teacher->is_data_completed) {
        return redirect()->route('teacher.complete-profile');
    }

    return redirect()->route('teacher.dashboard');
})->name('teacher.magic-link');

Route::get('/magic/{token}/login-as', function ($token) {
    if (! auth()->guard('teacher')->check()) {
        abort(403);
    }

    $student = Student::where('access_token', $token)->firstOrFail();
    auth()->guard('student')->login($student);

    return redirect()->route('student.dashboard');
})->name('magic-link.login-as');

Route::get('/quran-json', function () {
    return response()->json(Surah::with('ayahs')->get(), 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
});

Route::get('/test', function () {})->name('test');
require __DIR__.'/settings.php';
