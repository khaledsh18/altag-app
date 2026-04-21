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

    public $hifzAchievement = null;
    public $reviewAchievement = null;

    public function with()
    {
        $teacher = Auth::guard('teacher')->user();
        $circleIds = $teacher->circles()->pluck('id');
        
        $students = Student::whereIn('circle_id', $circleIds)->get();
        
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
                // "Next Day" theoretically means moving forward in time
                $hasNext = StudentPlanDay::where('student_plan_id', $this->planId)
                    ->where('date', '>', $currentDay->date)
                    ->exists();
                // "Previous Day" means moving backward in time
                $hasPrev = StudentPlanDay::where('student_plan_id', $this->planId)
                    ->where('date', '<', $currentDay->date)
                    ->exists();
            }
        }

        return [
            'students' => $students,
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
                ->where(function($q) {
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

    public function setHifz($val = null)
    {
        $this->hifzAchievement = $val;
        $this->saveAchievements();
    }

    public function setReview($val = null)
    {
        $this->reviewAchievement = $val;
        $this->saveAchievements();
    }

    public function saveAchievements()
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

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('التسميع والمتابعة') }}</flux:heading>
            <flux:subheading>{{ __('اختر الطالب ثم الخطة لعرض المهام المطلوبة وتقييم الإنجاز يومياً.') }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <flux:select wire:model.live="studentId" label="{{ __('الطالب') }}" placeholder="{{ __('اختر الطالب') }}">
            @foreach($students as $student)
                <flux:select.option value="{{ $student->id }}">{{ $student->name }}</flux:select.option>
            @endforeach
        </flux:select>

        @if($studentId)
            <flux:select wire:model.live="planId" label="{{ __('الخطة القرآنية') }}" placeholder="{{ __('اختر الخطة') }}">
                @forelse($plans as $plan)
                    <flux:select.option value="{{ $plan->id }}">
                        @if($plan->plan_type === 'hifz')
                            {{ __('حفظ (تبدأ من ' . $plan->start_date->format('Y/m/d') . ')') }}
                        @elseif($plan->plan_type === 'review')
                            {{ __('مراجعة (تبدأ من ' . $plan->start_date->format('Y/m/d') . ')') }}
                        @else
                            {{ __('حفظ ومراجعة (تبدأ من ' . $plan->start_date->format('Y/m/d') . ')') }}
                        @endif
                    </flux:select.option>
                @empty
                    <flux:select.option disabled>{{ __('لا يوجد خطط للطالب') }}</flux:select.option>
                @endforelse
            </flux:select>
        @endif
    </div>

    @if($currentDay)
        <flux:card class="mt-6 border-zinc-200 dark:border-zinc-700">
            <!-- Header Controls (Prev/Next) -->
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

            <!-- Content & Evaluation -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Hifz Section -->
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
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <button type="button" wire:click="setHifz(3)" class="p-3 rounded-xl border-2 transition-all {{ $hifzAchievement === 3 ? 'border-green-500 bg-green-50 dark:bg-green-500/20 text-green-700 dark:text-green-300' : 'border-zinc-200 dark:border-zinc-700 hover:border-green-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' }} font-bold text-center">ممتاز</button>
                                <button type="button" wire:click="setHifz(2)" class="p-3 rounded-xl border-2 transition-all {{ $hifzAchievement === 2 ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300' : 'border-zinc-200 dark:border-zinc-700 hover:border-blue-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' }} font-bold text-center">جيد</button>
                                <button type="button" wire:click="setHifz(1)" class="p-3 rounded-xl border-2 transition-all {{ $hifzAchievement === 1 ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300' : 'border-zinc-200 dark:border-zinc-700 hover:border-amber-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' }} font-bold text-center">مقبول</button>
                                <button type="button" wire:click="setHifz(null)" class="p-3 rounded-xl border-2 transition-all {{ $hifzAchievement === null ? 'border-red-500 bg-red-50 dark:bg-red-500/20 text-red-700 dark:text-red-300' : 'border-zinc-200 dark:border-zinc-700 hover:border-red-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' }} font-bold text-center">لم يسمع</button>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Review Section -->
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
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <button type="button" wire:click="setReview(3)" class="p-3 rounded-xl border-2 transition-all {{ $reviewAchievement === 3 ? 'border-green-500 bg-green-50 dark:bg-green-500/20 text-green-700 dark:text-green-300' : 'border-zinc-200 dark:border-zinc-700 hover:border-green-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' }} font-bold text-center">ممتاز</button>
                                <button type="button" wire:click="setReview(2)" class="p-3 rounded-xl border-2 transition-all {{ $reviewAchievement === 2 ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300' : 'border-zinc-200 dark:border-zinc-700 hover:border-blue-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' }} font-bold text-center">جيد</button>
                                <button type="button" wire:click="setReview(1)" class="p-3 rounded-xl border-2 transition-all {{ $reviewAchievement === 1 ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300' : 'border-zinc-200 dark:border-zinc-700 hover:border-amber-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' }} font-bold text-center">مقبول</button>
                                <button type="button" wire:click="setReview(null)" class="p-3 rounded-xl border-2 transition-all {{ $reviewAchievement === null ? 'border-red-500 bg-red-50 dark:bg-red-500/20 text-red-700 dark:text-red-300' : 'border-zinc-200 dark:border-zinc-700 hover:border-red-200 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' }} font-bold text-center">لم يسمع</button>
                            </div>
                        </div>
                    </div>
                @endif
                
            </div>
        </flux:card>
    @elseif($planId)
        <div class="mt-8 text-center text-zinc-500 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl py-12 border border-zinc-100 dark:border-zinc-800">
            <flux:icon.document-text class="mx-auto w-12 h-12 mb-4 text-zinc-400" />
            <p>{{ __('لا توجد مهام تسميع متوفرة لهذه الخطة.') }}</p>
        </div>
    @endif
</div>