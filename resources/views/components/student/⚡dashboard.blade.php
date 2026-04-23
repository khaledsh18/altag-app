<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentPlanDay;
use Carbon\Carbon;

new class extends Component {
    public function with()
    {
        $student = Auth::guard('student')->user();
        $todayStr = Carbon::now()->format('Y-m-d');
        $last30Start = Carbon::now()->subDays(30)->format('Y-m-d');

        // Fetch Earliest Pending Missions (one per active approved plan)
        $activeApprovedPlans = \App\Models\StudentPlan::where('student_id', $student->id)
            ->where('status', 'active')
            ->where('is_approved', 1)
            ->get();

        $pendingMissions = [];
        foreach ($activeApprovedPlans as $plan) {
            $mission = StudentPlanDay::with([
                'fromAyah.surah',
                'toAyah.surah',
                'reviewFromAyah.surah',
                'reviewToAyah.surah'
            ])
            ->where('student_plan_id', $plan->id)
            ->where(function($q) {
                $q->whereNull('hifz_achievement')->orWhereNull('review_achievement');
            })
            ->orderBy('date', 'asc')
            ->first();

            if ($mission) {
                $mission->setRelation('plan', $plan);
                $pendingMissions[] = $mission;
            }
        }

        // Fetch Stats
        $recentDays = StudentPlanDay::whereHas('plan', fn($q) => $q->where('student_id', $student->id))
            ->where('date', '>=', $last30Start)
            ->get();

        $excellent = 0;
        $good = 0;
        $acceptable = 0;

        foreach ($recentDays as $day) {
            $h = $day->hifz_achievement;
            $r = $day->review_achievement;
            
            if ($h == 3 || $r == 3) $excellent++;
            elseif ($h == 2 || $r == 2) $good++;
            elseif ($h == 1 || $r == 1) $acceptable++;
        }

        // Discipline Stats
        $calcPeriod = (int) \App\Models\Setting::getVal('calculation_period_days', 30);
        $absenceLimit = (int) \App\Models\Setting::getVal('absence_limit', 3);
        $latenessLimit = (int) \App\Models\Setting::getVal('lateness_limit', 5);

        $periodStart = Carbon::now()->subDays($calcPeriod)->format('Y-m-d');
        
        $absences = \App\Models\Attendance::where('student_id', $student->id)
            ->where('date', '>=', $periodStart)
            ->where('status', 'absent')
            ->count();
            
        $lateness = \App\Models\Attendance::where('student_id', $student->id)
            ->where('date', '>=', $periodStart)
            ->where('status', 'late')
            ->count();

        return [
            'student' => $student,
            'todayStr' => $todayStr,
            'pendingMissions' => $pendingMissions,
            'excellent' => $excellent,
            'good' => $good,
            'acceptable' => $acceptable,
            'calcPeriod' => $calcPeriod,
            'absenceLimit' => $absenceLimit,
            'latenessLimit' => $latenessLimit,
            'absences' => $absences,
            'lateness' => $lateness,
        ];
    }
    
    protected function getHijriLabel(\DateTimeInterface|string $date)
    {
        $parsed = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        $formatter = new \IntlDateFormatter(
            'ar_SA@calendar=islamic-umalqura',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            'Asia/Riyadh',
            \IntlDateFormatter::TRADITIONAL,
            'd MMMM yyyy'
        );

        return $formatter->format($parsed->getTimestamp());
    }
};
?>

<div class="space-y-8" dir="rtl">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold text-emerald-700 dark:text-emerald-400">
                {{ __('رحلتي مع القرآن') }}
            </flux:heading>
            <flux:subheading class="text-zinc-500 dark:text-zinc-400 mt-1">
                {{ __('مرحباً بك يا :name، دعنا نواصل ما بدأناه!', ['name' => $student->name]) }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" href="{{ route('student.plan-creator') }}">
            {{ __('إنشاء مسار جديد') }}
        </flux:button>
    </div>

    <!-- Pending Missions Widget -->
    @if(count($pendingMissions) > 0)
        <flux:card class="bg-gradient-to-br from-emerald-500 to-teal-600 border-none text-white overflow-hidden relative shadow-lg shadow-emerald-600/20 p-0">
            <div class="absolute -top-10 -right-10 p-4 opacity-10 pointer-events-none">
                <flux:icon icon="book-open" class="w-48 h-48" />
            </div>
            
            <div class="relative z-10 p-6">
                <div class="flex items-center gap-3 mb-6 border-b border-white/20 pb-4">
                    <div class="bg-white/20 p-2 rounded-lg">
                        <flux:icon icon="flag" class="size-6 text-white" />
                    </div>
                    <h2 class="text-xl font-bold">{{ __('المهام القادمة') }}</h2>
                </div>

                <div class="space-y-6">
                    @foreach($pendingMissions as $pendingMission)
                        @php
                            $isToday = $pendingMission->date === $todayStr;
                        @endphp
                        <div class="bg-white/10 rounded-2xl p-5 backdrop-blur-sm border border-white/20">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 border-b border-white/10 pb-3">
                                <div>
                                    <div class="font-bold text-lg text-white">
                                        {{ $pendingMission->plan->description ?? __('خطة بدون عنوان') }}
                                    </div>
                                    <div class="text-sm text-emerald-100 flex items-center gap-2 mt-1">
                                        <flux:icon icon="calendar" class="size-4" />
                                        <span>{{ $isToday ? __('مهمة اليوم') : __('مهمة فائتة أو قادمة') }} - {{ $pendingMission->day_name }} {{ $this->getHijriLabel($pendingMission->date) }}</span>
                                    </div>
                                </div>
                                <div class="shrink-0 bg-white/20 px-3 py-1 rounded-full text-xs font-bold text-white uppercase tracking-wide">
                                    {{ $pendingMission->plan->plan_type === 'hifz' ? __('حفظ') : ($pendingMission->plan->plan_type === 'review' ? __('مراجعة') : __('حفظ ومراجعة')) }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($pendingMission->from_ayah_id && $pendingMission->to_ayah_id)
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-emerald-100 text-sm">{{ __('مقرر الحفظ') }}</div>
                                        @if(is_null($pendingMission->hifz_achievement))
                                            <span class="bg-white/20 text-[10px] px-2 py-0.5 rounded text-white">{{ __('بانتظار التسميع') }}</span>
                                        @endif
                                    </div>
                                    <div class="text-lg font-bold">
                                        {{ $pendingMission->fromAyah->surah->name_arabic }} ({{ $pendingMission->fromAyah->verse_number }}) - 
                                        {{ $pendingMission->toAyah->surah->name_arabic }} ({{ $pendingMission->toAyah->verse_number }})
                                    </div>
                                </div>
                                @endif

                                @if($pendingMission->review_from_ayah_id && $pendingMission->review_to_ayah_id)
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-emerald-100 text-sm">{{ __('مقرر المراجعة') }}</div>
                                        @if(is_null($pendingMission->review_achievement))
                                            <span class="bg-white/20 text-[10px] px-2 py-0.5 rounded text-white">{{ __('بانتظار التسميع') }}</span>
                                        @endif
                                    </div>
                                    <div class="text-lg font-bold">
                                        {{ $pendingMission->reviewFromAyah->surah->name_arabic }} ({{ $pendingMission->reviewFromAyah->verse_number }}) - 
                                        {{ $pendingMission->reviewToAyah->surah->name_arabic }} ({{ $pendingMission->reviewToAyah->verse_number }})
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6 flex justify-end">
                    <flux:button href="{{ route('student.plan') }}" variant="filled" class="bg-white text-emerald-600 hover:bg-emerald-50">
                        {{ __('الانتقال إلى خططي') }}
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @else
        <flux:card class="border-t-4 border-t-zinc-400 border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
            <div class="flex flex-col items-center justify-center py-8 text-center space-y-4">
                <div class="bg-zinc-200 dark:bg-zinc-800 p-4 rounded-full text-zinc-500">
                    <flux:icon icon="sparkles" class="size-8" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-zinc-700 dark:text-zinc-300">{{ __('لا توجد مهام مجدولة لك اليوم') }}</h3>
                    <p class="text-zinc-500 text-sm mt-1 max-w-sm">{{ __('اغتنم هذا اليوم في مراجعة ما حفظته مسبقاً وثبّت جذور القرآن في قلبك.') }}</p>
                </div>
            </div>
        </flux:card>
    @endif

    <!-- Performance Widgets -->
    <div>
        <flux:heading size="lg" class="mb-4">{{ __('إنجازاتي في آخر 30 يوماً') }}</flux:heading>
        <div class="grid grid-cols-3 gap-4">
            
            <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex flex-col items-center justify-center text-center">
                <div class="text-blue-500 mb-2"><flux:icon icon="star" variant="solid" class="size-8" /></div>
                <div class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ $excellent }}</div>
                <div class="text-xs text-zinc-500 mt-1">{{ __('ممتاز') }}</div>
            </div>

            <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex flex-col items-center justify-center text-center">
                <div class="text-green-500 mb-2"><flux:icon icon="check-badge" variant="solid" class="size-8" /></div>
                <div class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ $good }}</div>
                <div class="text-xs text-zinc-500 mt-1">{{ __('جيد جداً') }}</div>
            </div>

            <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex flex-col items-center justify-center text-center">
                <div class="text-amber-500 mb-2"><flux:icon icon="hand-thumb-up" variant="solid" class="size-8" /></div>
                <div class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ $acceptable }}</div>
                <div class="text-xs text-zinc-500 mt-1">{{ __('مقبول') }}</div>
            </div>

        </div>
    </div>

    <!-- Discipline Widget -->
    <div>
        <flux:heading size="lg" class="mb-4">{{ __('الانضباط الحضوري (خلال آخر :days يوماً)', ['days' => $calcPeriod]) }}</flux:heading>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="text-red-500 bg-red-50 dark:bg-red-900/40 p-3 rounded-xl">
                        <flux:icon icon="x-circle" variant="solid" class="size-6" />
                    </div>
                    <div>
                        <div class="font-bold text-zinc-800 dark:text-zinc-100">{{ __('الغيابات') }}</div>
                        <div class="text-xs text-zinc-500">{{ __('الحد الأقصى:') }} {{ $absenceLimit }}</div>
                    </div>
                </div>
                <div class="text-3xl font-black {{ $absences >= $absenceLimit ? 'text-red-600' : 'text-zinc-700 dark:text-zinc-300' }}">
                    {{ $absences }}<span class="text-lg text-zinc-400 font-normal">/{{ $absenceLimit }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="text-amber-500 bg-amber-50 dark:bg-amber-900/40 p-3 rounded-xl">
                        <flux:icon icon="clock" variant="solid" class="size-6" />
                    </div>
                    <div>
                        <div class="font-bold text-zinc-800 dark:text-zinc-100">{{ __('التأخر') }}</div>
                        <div class="text-xs text-zinc-500">{{ __('الحد الأقصى:') }} {{ $latenessLimit }}</div>
                    </div>
                </div>
                <div class="text-3xl font-black {{ $lateness >= $latenessLimit ? 'text-amber-600' : 'text-zinc-700 dark:text-zinc-300' }}">
                    {{ $lateness }}<span class="text-lg text-zinc-400 font-normal">/{{ $latenessLimit }}</span>
                </div>
            </div>

        </div>
        
        @if($absences >= $absenceLimit || $lateness >= $latenessLimit)
            <div class="mt-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-xl border border-red-200 dark:border-red-800/50 flex items-start gap-3">
                <flux:icon icon="exclamation-triangle" variant="solid" class="size-5 mt-0.5" />
                <div class="text-sm">
                    <strong>{{ __('تنبيه إداري:') }}</strong> {{ __('لقد تجاوزت الحد المسموح به للغياب أو التأخر، يرجى الالتزام بالحضور لتفادي الإجراءات الإدارية.') }}
                </div>
            </div>
        @endif
    </div>
</div>
