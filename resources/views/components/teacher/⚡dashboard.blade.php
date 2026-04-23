<?php

use Livewire\Component;
use App\Models\Student;
use App\Models\StudentPlanDay;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public function with()
    {
        $teacher = Auth::guard('teacher')->user();
        $circleIds = $teacher->circles()->pluck('id');

        $last30Start = Carbon::now()->subDays(30)->format('Y-m-d');
        $last30End = Carbon::now()->format('Y-m-d');
        
        // 1. Top Attendance (last 30 days)
        $studentsLast30Days = Student::whereIn('circle_id', $circleIds)
            ->with(['attendances' => function($q) use ($last30Start, $last30End) {
                $q->whereBetween('date', [$last30Start, $last30End]);
            }])
            ->get()
            ->map(function ($student) {
                return [
                    'name' => $student->name,
                    'present_count' => $student->attendances->where('status', 'present')->count(),
                ];
            });

        $topAttendance = $studentsLast30Days->where('present_count', '>', 0)->sortByDesc('present_count')->take(5)->values();

        // 2. Top Quranic Discipline (last 30 days)
        $studentIds = \App\Models\StudentPlan::where('teacher_id', $teacher->id)->pluck('student_id')->unique();
        $planDays = StudentPlanDay::with('plan.student')
            ->whereHas('plan', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->where('date', '>=', $last30Start)
            ->where(function($query) use ($last30End) {
                $query->where('date', '<=', $last30End)
                      ->orWhereNotNull('hifz_achievement')
                      ->orWhereNotNull('review_achievement');
            })
            ->get();

        $studentsExcellent = [];
        // Initialize
        foreach ($studentIds as $sId) {
            $studentsExcellent[$sId] = [
                'name' => '', // Will be filled dynamically below
                'excellent_count' => 0
            ];
        }

        foreach ($planDays as $day) {
            $sId = $day->plan->student_id;
            if (!isset($studentsExcellent[$sId])) continue;
            
            $studentsExcellent[$sId]['name'] = $day->plan->student->name;
            
            // Only count today or past dates (or early recitations)
            $dateStr = \Carbon\Carbon::parse($day->date)->format('Y-m-d');
            $effectiveDateStr = $dateStr > $last30End ? $last30End : $dateStr;
            
            if ($effectiveDateStr >= $last30Start && $effectiveDateStr <= $last30End) {
                if ($day->plan->plan_type === 'hifz' || $day->plan->plan_type === 'hifz_review') {
                    if ($day->hifz_achievement == 3) $studentsExcellent[$sId]['excellent_count']++;
                }
                if ($day->plan->plan_type === 'review' || $day->plan->plan_type === 'hifz_review') {
                    if ($day->review_achievement == 3) $studentsExcellent[$sId]['excellent_count']++;
                }
            }
        }

        // Filter and sort top excellent
        $topExcellent = collect($studentsExcellent)
            ->filter(fn($s) => $s['excellent_count'] > 0 && !empty($s['name']))
            ->sortByDesc('excellent_count')
            ->take(5)
            ->values();

        return [
            'topAttendance' => $topAttendance,
            'topExcellent' => $topExcellent,
            'studentsCount' => Student::whereIn('circle_id', $circleIds)->count(),
        ];
    }
};
?>

<div class="space-y-8" dir="rtl">
    <!-- Header Section -->
    <div>
        <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-white">
            {{ __('لوحة تحكم التعليم') }}
        </flux:heading>
        <flux:subheading class="text-zinc-500 dark:text-zinc-400">
            {{ __('مرحباً بك مجدداً في نظام إدارة مجمع التاج القرآني') }}
        </flux:subheading>
    </div>

    <!-- Quick CTA: Attendance -->
    <a href="{{ route('teacher.attendance') }}" class="block w-full transition-transform hover:-translate-y-1">
        <div class="bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 rounded-2xl p-6 shadow-md shadow-indigo-600/20 text-white flex flex-col sm:flex-row items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-3 rounded-full">
                    <flux:icon icon="clipboard-document-check" class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h2 class="text-xl font-bold">{{ __('التحضير اليومي') }}</h2>
                    <p class="text-indigo-100 text-sm mt-1 mb-0">{{ __('سجل حضور وغياب وتأخر طلابك لهذا اليوم') }}</p>
                </div>
            </div>
            <flux:button variant="filled" class="bg-white text-indigo-600 hover:bg-zinc-50">{{ __('ابدأ التحضير الآن') }}</flux:button>
        </div>
    </a>

    <!-- Positive Snapshots Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Top Attendance Snapshot -->
        <flux:card class="border-t-4 border-t-green-500 overflow-hidden relative">
            <div class="absolute top-0 right-0 p-4 opacity-5">
                <flux:icon icon="check-badge" class="w-24 h-24 text-green-500" />
            </div>
            <div class="relative z-10 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                        <flux:icon icon="check-badge" class="w-5 h-5 flex-shrink-0" />
                        <h3 class="font-bold text-sm">{{ __('التميز الحضوري') }}</h3>
                    </div>
                    <a href="{{ route('teacher.discipline') }}" class="text-xs text-indigo-500 hover:underline">{{ __('التفاصيل') }}</a>
                </div>
                <div class="text-xs text-zinc-500">{{ __('فرسان الحضور خلال آخر 30 يوماً') }}</div>

                <div class="space-y-3 mt-4">
                    @forelse($topAttendance as $stat)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate pr-2">{{ $stat['name'] }}</span>
                            <flux:badge color="green" size="sm" inset="top bottom">{{ $stat['present_count'] }} {{ __('حضور') }}</flux:badge>
                        </div>
                    @empty
                        <div class="text-xs text-zinc-400 text-center py-4">{{ __('لا يوجد بيانات كافية') }}</div>
                    @endforelse
                </div>
            </div>
        </flux:card>

        <!-- Top Quranic Discipline Snapshot -->
        <flux:card class="border-t-4 border-t-blue-500 overflow-hidden relative">
            <div class="absolute top-0 right-0 p-4 opacity-5">
                <flux:icon icon="star" class="w-24 h-24 text-blue-500" />
            </div>
            <div class="relative z-10 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <flux:icon icon="star" class="w-5 h-5 flex-shrink-0" />
                        <h3 class="font-bold text-sm">{{ __('التميز القرآني') }}</h3>
                    </div>
                    <a href="{{ route('teacher.quranic-discipline') }}" class="text-xs text-indigo-500 hover:underline">{{ __('التفاصيل') }}</a>
                </div>
                <div class="text-xs text-zinc-500">{{ __('فرسان التسميع بتقدير ممتاز خلال آخر 30 يوماً') }}</div>

                <div class="space-y-3 mt-4">
                    @forelse($topExcellent as $stat)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate pr-2">{{ $stat['name'] }}</span>
                            <flux:badge color="blue" size="sm" inset="top bottom">{{ $stat['excellent_count'] }} {{ __('امتياز') }}</flux:badge>
                        </div>
                    @empty
                        <div class="text-xs text-zinc-400 text-center py-4">{{ __('لا يوجد بيانات كافية') }}</div>
                    @endforelse
                </div>
            </div>
        </flux:card>

    </div>

    <!-- Secondary Shortcuts Grid -->
    <div>
        <flux:heading size="lg" class="mb-4">{{ __('روابط سريعة') }}</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <a href="{{ route('teacher.tasmeeh') }}" class="group bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs hover:border-indigo-500/50 hover:shadow-md transition-all h-36 flex flex-col justify-center items-center text-center">
                <flux:icon icon="book-open" class="size-8 text-indigo-500 mb-2 group-hover:scale-110 transition-transform" />
                <flux:heading size="md" class="group-hover:text-indigo-600 transition-colors">{{ __('التسميع اليومي') }}</flux:heading>
                <flux:subheading class="text-xs mt-1">{{ __('متابعة تسميع المهام اليومية للطلاب') }}</flux:subheading>
            </a>

            <a href="{{ route('teacher.plan-creator') }}" class="group bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs hover:border-indigo-500/50 hover:shadow-md transition-all h-36 flex flex-col justify-center items-center text-center">
                <flux:icon icon="calendar-days" class="size-8 text-emerald-500 mb-2 group-hover:scale-110 transition-transform" />
                <flux:heading size="md" class="group-hover:text-indigo-600 transition-colors">{{ __('منشئ الخطط') }}</flux:heading>
                <flux:subheading class="text-xs mt-1">{{ __('إنشاء وتوزيع مسارات الحفظ والمراجعة') }}</flux:subheading>
            </a>
            
            <a href="{{ route('teacher.students') }}" class="group bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs hover:border-indigo-500/50 hover:shadow-md transition-all h-36 flex flex-col justify-center items-center text-center">
                <flux:icon icon="users" class="size-8 text-amber-500 mb-2 group-hover:scale-110 transition-transform" />
                <flux:heading size="md" class="group-hover:text-indigo-600 transition-colors">{{ __('طلابي') }}</flux:heading>
                <flux:subheading class="text-xs mt-1">{{ __('إدارة قائمة الطلاب ( ' . $studentsCount . ' طالب )') }}</flux:subheading>
            </a>

        </div>
    </div>
</div>
