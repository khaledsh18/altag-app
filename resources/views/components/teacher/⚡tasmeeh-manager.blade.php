<?php

use Livewire\Component;
use App\Models\Student;
use App\Models\StudentPlan;
use App\Models\StudentPlanDay;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new class extends Component {
    public $studentId = null;
    public $planId = null;
    public $dayId = null;

    /** Persisted in DB — also @entangled to Alpine for instant visual feedback */
    public $hifzAchievement = null;
    public $reviewAchievement = null;

    public function with()
    {
        $teacher = Auth::guard('teacher')->user();
        $circleIds = $teacher->circles()->pluck('id');

        $students = Student::whereIn('circle_id', $circleIds)->get();

        $todayStr = \Carbon\Carbon::today()->format('Y-m-d');
        
        $allPlans = StudentPlan::whereIn('student_id', $students->pluck('id'))
            ->where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->where('is_approved', 1)
            ->get()
            ->groupBy('student_id');

        // We need all today's plan days for the active plans to check the colors
        $todaysPlanDays = StudentPlanDay::whereIn('student_plan_id', $allPlans->flatten()->pluck('id'))
            ->whereDate('date', $todayStr)
            ->get()
            ->groupBy(function($day) {
                return $day->plan->student_id;
            });

        $todayAttendances = \App\Models\Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereDate('date', $todayStr)
            ->get()
            ->keyBy('student_id');

        $studentsWithPlansPresent = [];
        $studentsWithPlansAbsent = [];
        $studentsWithoutPlans = [];

        foreach ($students as $student) {
            if (isset($allPlans[$student->id])) {
                $color = 'red';
                
                if (isset($todaysPlanDays[$student->id])) {
                    $days = $todaysPlanDays[$student->id];
                    $hasTasks = false;
                    $totalRequired = 0;
                    $completedCount = 0;
                    $hasAnyAchievement = false;

                    foreach ($days as $day) {
                        if ($day->from_ayah_id) {
                            $hasTasks = true;
                            $totalRequired++;
                            if ($day->hifz_achievement !== null) {
                                $completedCount++;
                                $hasAnyAchievement = true;
                            }
                        }
                        if ($day->review_from_ayah_id) {
                            $hasTasks = true;
                            $totalRequired++;
                            if ($day->review_achievement !== null) {
                                $completedCount++;
                                $hasAnyAchievement = true;
                            }
                        }
                    }

                    if (!$hasTasks) {
                        $color = 'zinc'; // No actual tasks today
                    } elseif ($totalRequired > 0 && $completedCount === $totalRequired) {
                        $color = 'emerald'; // Full
                    } elseif ($hasAnyAchievement) {
                        $color = 'blue'; // Partial
                    } else {
                        $color = 'rose'; // None
                    }
                } else {
                    $color = 'zinc'; // No plan day for today
                }
                
                $student->tasmeeh_color = $color;
                
                $attendanceStatus = isset($todayAttendances[$student->id]) ? $todayAttendances[$student->id]->status : 'present';
                if (in_array($attendanceStatus, ['absent', 'excused'])) {
                    $studentsWithPlansAbsent[] = $student;
                } else {
                    $studentsWithPlansPresent[] = $student;
                }
            } else {
                $studentsWithoutPlans[] = $student;
            }
        }

        $plans = [];
        if ($this->studentId) {
            $plans = StudentPlan::where('student_id', $this->studentId)
                ->where('teacher_id', $teacher->id)
                ->latest()
                ->get();
        }

        $currentDay = null;
        $hasNext = false;
        $hasPrev = false;

        if ($this->dayId) {
            $currentDay = StudentPlanDay::with(['fromAyah.surah', 'toAyah.surah', 'reviewFromAyah.surah', 'reviewToAyah.surah', 'plan'])->find($this->dayId);

            if ($currentDay) {
                $hasNext = StudentPlanDay::where('student_plan_id', $this->planId)
                    ->where('date', '>', $currentDay->date)
                    ->exists();
                $hasPrev = StudentPlanDay::where('student_plan_id', $this->planId)
                    ->where('date', '<', $currentDay->date)
                    ->exists();
            }
        }

        return [
            'studentsWithPlansPresent' => collect($studentsWithPlansPresent),
            'studentsWithPlansAbsent' => collect($studentsWithPlansAbsent),
            'studentsWithoutPlans' => collect($studentsWithoutPlans),
            'plans' => $plans,
            'currentDay' => $currentDay,
            'hasNext' => $hasNext,
            'hasPrev' => $hasPrev,
        ];
    }

    public function updatedStudentId()
    {
        $this->planId = null;
        $this->dayId = null;
        $this->hifzAchievement = null;
        $this->reviewAchievement = null;

        if ($this->studentId) {
            $teacher = Auth::guard('teacher')->user();
            $firstPlan = StudentPlan::where('student_id', $this->studentId)
                ->where('teacher_id', $teacher->id)
                ->latest()
                ->first();

            if ($firstPlan) {
                $this->planId = $firstPlan->id;
                $this->updatedPlanId();
            }
        }
    }

    public function updatedPlanId()
    {
        $this->dayId = null;

        if ($this->planId) {
            $oldestIncomplete = StudentPlanDay::where('student_plan_id', $this->planId)
                ->where(function ($q) {
                    $q->whereNull('hifz_achievement')
                        ->orWhereNull('review_achievement');
                })
                ->orderBy('date', 'asc')
                ->first();

            if ($oldestIncomplete) {
                $this->loadDay($oldestIncomplete->id);
            } else {
                $lastDay = StudentPlanDay::where('student_plan_id', $this->planId)
                    ->orderBy('date', 'desc')
                    ->first();
                if ($lastDay) {
                    $this->loadDay($lastDay->id);
                }
            }
        }
    }

    public function loadDay($id)
    {
        $this->dayId = $id;
        $day = StudentPlanDay::find($id);
        if ($day) {
            $this->hifzAchievement = $day->hifz_achievement;
            $this->reviewAchievement = $day->review_achievement;
        }
    }

    public function previousDay()
    {
        $currentDay = StudentPlanDay::find($this->dayId);
        if ($currentDay) {
            $prev = StudentPlanDay::where('student_plan_id', $this->planId)
                ->where('date', '<', $currentDay->date)
                ->orderBy('date', 'desc')
                ->first();
            if ($prev) {
                $this->loadDay($prev->id);
            }
        }
    }

    public function nextDay()
    {
        $currentDay = StudentPlanDay::find($this->dayId);
        if ($currentDay) {
            $next = StudentPlanDay::where('student_plan_id', $this->planId)
                ->where('date', '>', $currentDay->date)
                ->orderBy('date', 'asc')
                ->first();
            if ($next) {
                $this->loadDay($next->id);
            }
        }
    }

    /**
     * Alpine calls this in the background — no UI blocking.
     * $hifzAchievement is already synced via @entangle before this fires.
     */
    public function saveHifz($val = null)
    {
        $this->hifzAchievement = $val;
        $this->persist();
    }

    /**
     * Alpine calls this in the background — no UI blocking.
     */
    public function saveReview($val = null)
    {
        $this->reviewAchievement = $val;
        $this->persist();
    }

    private function persist()
    {
        if ($this->dayId) {
            StudentPlanDay::where('id', $this->dayId)->update([
                'hifz_achievement' => $this->hifzAchievement,
                'review_achievement' => $this->reviewAchievement,
            ]);
            Flux::toast('تم حفظ التقييم', variant: 'success');
        }
    }
};
?>

{{--
  Alpine state:
    hifz    — entangled with $hifzAchievement   (instant button highlight, background save)
    review  — entangled with $reviewAchievement  (same)

  Livewire fires only on:  updatedStudentId | updatedPlanId | previousDay | nextDay
  (data-loading operations that genuinely need the server)
--}}
<div class="space-y-6"
     x-data="{
         hifz:   @entangle('hifzAchievement'),
         review: @entangle('reviewAchievement'),

         setHifz(val) {
             this.hifz = val;                    /* instant visual */
             $wire.saveHifz(val);               /* background DB save */
         },

         setReview(val) {
             this.review = val;                  /* instant visual */
             $wire.saveReview(val);             /* background DB save */
         },
     }">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('التسميع والمتابعة') }}</flux:heading>
            <flux:subheading>{{ __('اختر الطالب ثم الخطة لعرض المهام المطلوبة وتقييم الإنجاز يومياً.') }}</flux:subheading>
        </div>
    </div>

    {{-- Selects & Student List Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <!-- Students List Sidebar -->
        <div x-data="{ 
                openSection: 1, 
                selectStudent(id) {
                    $wire.set('studentId', id);
                    this.openSection = 0;
                    setTimeout(() => {
                        const el = document.getElementById('grading-area');
                        if(el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
             }" 
             class="lg:col-span-1 flex flex-col gap-4 bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 h-fit max-h-[800px] overflow-y-auto">
            
            <!-- Section 1a: With Plans (Present / Late) -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm">
                <button @click="openSection = openSection === 1 ? 0 : 1" class="w-full flex items-center justify-between p-3 bg-zinc-50/50 dark:bg-zinc-800/30 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="check-circle" variant="micro" class="text-emerald-500" />
                        <span class="font-bold text-sm text-zinc-700 dark:text-zinc-300">{{ __('حاضر / متأخر') }}</span>
                        <span class="text-xs bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded-md text-zinc-600 dark:text-zinc-400">{{ count($studentsWithPlansPresent) }}</span>
                    </div>
                    <flux:icon icon="chevron-down" class="size-4 text-zinc-400 transition-transform" x-bind:class="openSection === 1 ? 'rotate-180' : ''" />
                </button>
                <div x-show="openSection === 1" x-collapse class="p-2 space-y-1.5 border-t border-zinc-100 dark:border-zinc-800">
                    @forelse($studentsWithPlansPresent as $student)
                        <button @click="selectStudent({{ $student->id }})" 
                                class="w-full flex items-center justify-between p-2.5 rounded-xl border text-right transition-colors {{ $studentId == $student->id ? 'bg-indigo-50 border-indigo-200 dark:bg-indigo-900/40 dark:border-indigo-800' : 'bg-white dark:bg-zinc-800 border-transparent hover:border-zinc-200 dark:hover:border-zinc-700' }}">
                            <div class="flex items-center gap-3">
                                <div class="size-2.5 rounded-full bg-{{ $student->tasmeeh_color }}-500 shadow-sm shadow-{{ $student->tasmeeh_color }}-500/30 shrink-0"></div>
                                <span class="font-medium text-sm {{ $studentId == $student->id ? 'text-indigo-700 dark:text-indigo-400' : 'text-zinc-700 dark:text-zinc-300' }} truncate">{{ $student->name }}</span>
                            </div>
                        </button>
                    @empty
                        <div class="text-xs text-center text-zinc-400 py-3">{{ __('لا يوجد طلاب حالياً.') }}</div>
                    @endforelse
                </div>
            </div>

            <!-- Section 1b: With Plans (Absent / Excused) -->
            @if($studentsWithPlansAbsent->isNotEmpty())
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm">
                <button @click="openSection = openSection === 2 ? 0 : 2" class="w-full flex items-center justify-between p-3 bg-zinc-50/50 dark:bg-zinc-800/30 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="x-circle" variant="micro" class="text-rose-500" />
                        <span class="font-bold text-sm text-zinc-700 dark:text-zinc-300">{{ __('غائب / معتذر') }}</span>
                        <span class="text-xs bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded-md text-zinc-600 dark:text-zinc-400">{{ count($studentsWithPlansAbsent) }}</span>
                    </div>
                    <flux:icon icon="chevron-down" class="size-4 text-zinc-400 transition-transform" x-bind:class="openSection === 2 ? 'rotate-180' : ''" />
                </button>
                <div x-show="openSection === 2" x-collapse class="p-2 space-y-1.5 border-t border-zinc-100 dark:border-zinc-800">
                    @forelse($studentsWithPlansAbsent as $student)
                        <button @click="selectStudent({{ $student->id }})" 
                                class="w-full flex items-center justify-between p-2.5 rounded-xl border text-right transition-colors {{ $studentId == $student->id ? 'bg-indigo-50 border-indigo-200 dark:bg-indigo-900/40 dark:border-indigo-800' : 'bg-rose-50 dark:bg-rose-900/10 border-transparent hover:border-rose-200 dark:hover:border-rose-800/50 opacity-75 hover:opacity-100' }}">
                            <div class="flex items-center gap-3">
                                <div class="size-2.5 rounded-full bg-{{ $student->tasmeeh_color }}-500 shadow-sm shadow-{{ $student->tasmeeh_color }}-500/30 shrink-0"></div>
                                <span class="font-medium text-sm {{ $studentId == $student->id ? 'text-indigo-700 dark:text-indigo-400' : 'text-rose-700 dark:text-rose-400' }} truncate">{{ $student->name }}</span>
                            </div>
                        </button>
                    @empty
                    @endforelse
                </div>
            </div>
            @endif

            <!-- Section 2: Without Plans -->
            @if($studentsWithoutPlans->isNotEmpty())
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm">
                <button @click="openSection = openSection === 3 ? 0 : 3" class="w-full flex items-center justify-between p-3 bg-zinc-50/50 dark:bg-zinc-800/30 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="document-minus" variant="micro" class="text-zinc-400" />
                        <span class="font-bold text-sm text-zinc-700 dark:text-zinc-300">{{ __('غير المجدولين') }}</span>
                        <span class="text-xs bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded-md text-zinc-600 dark:text-zinc-400">{{ count($studentsWithoutPlans) }}</span>
                    </div>
                    <flux:icon icon="chevron-down" class="size-4 text-zinc-400 transition-transform" x-bind:class="openSection === 3 ? 'rotate-180' : ''" />
                </button>
                <div x-show="openSection === 3" x-collapse class="p-2 space-y-1.5 border-t border-zinc-100 dark:border-zinc-800">
                    @forelse($studentsWithoutPlans as $student)
                        <div class="flex items-center gap-2">
                            <button @click="selectStudent({{ $student->id }})" 
                                    class="flex-1 flex items-center p-2.5 rounded-xl border text-right transition-colors {{ $studentId == $student->id ? 'bg-indigo-50 border-indigo-200 dark:bg-indigo-900/40 dark:border-indigo-800' : 'bg-zinc-100/50 dark:bg-zinc-800/30 border-transparent hover:border-zinc-200 dark:hover:border-zinc-700' }}">
                                <span class="font-medium text-sm {{ $studentId == $student->id ? 'text-indigo-700 dark:text-indigo-400' : 'text-zinc-500 dark:text-zinc-400' }} truncate">{{ $student->name }}</span>
                            </button>
                            <a href="{{ route('teacher.plan-creator', ['studentId' => $student->id]) }}" 
                               class="shrink-0 p-2.5 text-emerald-600 hover:text-white bg-emerald-50 hover:bg-emerald-500 dark:text-emerald-400 dark:bg-emerald-900/20 dark:hover:bg-emerald-600 rounded-xl transition-colors" 
                               title="{{ __('إنشاء خطة') }}">
                                <flux:icon icon="plus" class="size-4" variant="mini" />
                            </a>
                        </div>
                    @empty
                    @endforelse
                </div>
            </div>
            @endif

        </div>

        <!-- Main Content Area -->
        <div id="grading-area" class="lg:col-span-3 space-y-6 scroll-mt-6">
            @if($studentId)
                @if(count($plans) > 0)
                    <div class="bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <flux:select wire:model.live="planId" label="{{ __('الخطة القرآنية') }}" placeholder="{{ __('اختر الخطة') }}">
                            @foreach($plans as $plan)
                                <flux:select.option value="{{ $plan->id }}">
                                    @if($plan->plan_type === 'hifz')
                                        {{ __('حفظ (تبدأ من ' . $plan->start_date->format('Y/m/d') . ')') }}
                                    @elseif($plan->plan_type === 'review')
                                        {{ __('مراجعة (تبدأ من ' . $plan->start_date->format('Y/m/d') . ')') }}
                                    @else
                                        {{ __('حفظ ومراجعة (تبدأ من ' . $plan->start_date->format('Y/m/d') . ')') }}
                                    @endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    @if($currentDay)
                        <flux:card class="mt-2 border-zinc-200 dark:border-zinc-700">


            {{-- Day navigation — Livewire (loads new day data) --}}
            <div class="flex items-center justify-between mb-8 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                <flux:button wire:click="previousDay" :disabled="!$hasPrev" icon="chevron-right" variant="subtle" size="sm">
                    {{ __('اليوم السابق') }}
                </flux:button>

                <div class="text-center">
                    <div class="font-bold text-lg">{{ $currentDay->day_name }}</div>
                    <div class="text-zinc-500 text-sm dir-ltr">{{ $currentDay->date->format('Y/m/d') }}</div>
                </div>

                <flux:button wire:click="nextDay" :disabled="!$hasNext" icon-trailing="chevron-left" variant="subtle" size="sm">
                    {{ __('اليوم التالي') }}
                </flux:button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                {{-- Hifz Section --}}
                @if($currentDay->plan->plan_type === 'hifz' || $currentDay->plan->plan_type === 'hifz_review')
                    <div class="bg-indigo-50/50 dark:bg-indigo-500/5 rounded-xl border border-indigo-100 dark:border-indigo-500/20 p-5 space-y-5">
                        <div>
                            <flux:heading size="lg" class="text-indigo-600 dark:text-indigo-400 mb-2">{{ __('الحفظ') }}</flux:heading>
                            <p class="text-zinc-700 dark:text-zinc-300 font-medium text-lg leading-relaxed">
                                {{ $currentDay->formatRange('hifz') ?? 'لا يوجد نص محدد' }}
                            </p>
                        </div>

                        <flux:separator />

                        <div>
                            <flux:label class="mb-3 font-semibold">{{ __('تقييم الإنجاز (التسميع)') }}</flux:label>

                            {{-- Alpine controls coloring instantly, saveHifz() saves in background --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <button type="button" @click="setHifz(3)"
                                        :class="hifz === 3
                                            ? 'border-green-500 bg-green-50 dark:bg-green-500/20 text-green-700 dark:text-green-300'
                                            : 'border-zinc-200 dark:border-zinc-700 hover:border-green-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'"
                                        class="p-3 rounded-xl border-2 transition-all font-bold text-center">ممتاز</button>

                                <button type="button" @click="setHifz(2)"
                                        :class="hifz === 2
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300'
                                            : 'border-zinc-200 dark:border-zinc-700 hover:border-blue-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'"
                                        class="p-3 rounded-xl border-2 transition-all font-bold text-center">جيد</button>

                                <button type="button" @click="setHifz(1)"
                                        :class="hifz === 1
                                            ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300'
                                            : 'border-zinc-200 dark:border-zinc-700 hover:border-amber-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'"
                                        class="p-3 rounded-xl border-2 transition-all font-bold text-center">مقبول</button>

                                <button type="button" @click="setHifz(null)"
                                        :class="hifz === null
                                            ? 'border-red-500 bg-red-50 dark:bg-red-500/20 text-red-700 dark:text-red-300'
                                            : 'border-zinc-200 dark:border-zinc-700 hover:border-red-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'"
                                        class="p-3 rounded-xl border-2 transition-all font-bold text-center">لم يسمع</button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Review Section --}}
                @if($currentDay->plan->plan_type === 'review' || $currentDay->plan->plan_type === 'hifz_review')
                    <div class="bg-emerald-50/50 dark:bg-emerald-500/5 rounded-xl border border-emerald-100 dark:border-emerald-500/20 p-5 space-y-5">
                        <div>
                            <flux:heading size="lg" class="text-emerald-600 dark:text-emerald-400 mb-2">{{ __('المراجعة') }}</flux:heading>
                            <p class="text-zinc-700 dark:text-zinc-300 font-medium text-lg leading-relaxed">
                                {{ $currentDay->formatRange('review') ?? 'لا يوجد نص محدد' }}
                            </p>
                        </div>

                        <flux:separator />

                        <div>
                            <flux:label class="mb-3 font-semibold">{{ __('تقييم الإنجاز (التسميع)') }}</flux:label>

                            {{-- Alpine controls coloring instantly, saveReview() saves in background --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <button type="button" @click="setReview(3)"
                                        :class="review === 3
                                            ? 'border-green-500 bg-green-50 dark:bg-green-500/20 text-green-700 dark:text-green-300'
                                            : 'border-zinc-200 dark:border-zinc-700 hover:border-green-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'"
                                        class="p-3 rounded-xl border-2 transition-all font-bold text-center">ممتاز</button>

                                <button type="button" @click="setReview(2)"
                                        :class="review === 2
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300'
                                            : 'border-zinc-200 dark:border-zinc-700 hover:border-blue-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'"
                                        class="p-3 rounded-xl border-2 transition-all font-bold text-center">جيد</button>

                                <button type="button" @click="setReview(1)"
                                        :class="review === 1
                                            ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300'
                                            : 'border-zinc-200 dark:border-zinc-700 hover:border-amber-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'"
                                        class="p-3 rounded-xl border-2 transition-all font-bold text-center">مقبول</button>

                                <button type="button" @click="setReview(null)"
                                        :class="review === null
                                            ? 'border-red-500 bg-red-50 dark:bg-red-500/20 text-red-700 dark:text-red-300'
                                            : 'border-zinc-200 dark:border-zinc-700 hover:border-red-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'"
                                        class="p-3 rounded-xl border-2 transition-all font-bold text-center">لم يسمع</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </flux:card>

        @elseif($planId)
            <div class="mt-8 text-center text-zinc-500 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl py-12 border border-zinc-100 dark:border-zinc-800">
                <flux:icon icon="document-text" class="mx-auto w-12 h-12 mb-4 text-zinc-400" />
                <p>{{ __('لا توجد مهام تسميع متوفرة لهذه الخطة.') }}</p>
            </div>
        @endif

    @else
        <div class="flex flex-col items-center justify-center p-12 bg-zinc-50/50 dark:bg-zinc-900/50 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl text-center h-full min-h-[400px]">
            <flux:icon icon="document-text" class="size-16 text-zinc-300 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="text-zinc-500 dark:text-zinc-400 mb-2">{{ __('لا توجد خطط لهذا الطالب') }}</flux:heading>
            <p class="text-zinc-400 dark:text-zinc-500 text-sm max-w-sm mb-6">{{ __('قم بإنشاء خطة قرآنية للطالب للبدء بتقييم التسميع والمراجعة.') }}</p>
            <flux:button href="{{ route('teacher.plan-creator', ['studentId' => $studentId]) }}" variant="primary" icon="plus">
                {{ __('إنشاء خطة جديدة') }}
            </flux:button>
        </div>
    @endif

@else
    <div class="flex flex-col items-center justify-center p-12 bg-zinc-50/50 dark:bg-zinc-900/50 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl text-center h-full min-h-[400px]">
        <flux:icon icon="user-group" class="size-16 text-zinc-300 dark:text-zinc-600 mb-4" />
        <flux:heading size="lg" class="text-zinc-500 dark:text-zinc-400 mb-2">{{ __('اختر طالباً للبدء') }}</flux:heading>
        <p class="text-zinc-400 dark:text-zinc-500 text-sm max-w-sm">{{ __('قم باختيار أحد الطلاب من القائمة الجانبية لعرض خطته القرآنية والبدء بتقييم التسميع والمراجعة.') }}</p>
    </div>
@endif
        </div>
    </div>
</div>