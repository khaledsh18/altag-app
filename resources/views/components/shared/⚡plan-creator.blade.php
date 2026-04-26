<?php

use Livewire\Component;
use App\Models\Student;
use App\Models\Surah;
use App\Models\Ayah;
use App\Models\StudentPlan;
use App\Models\StudentPlanDay;
use App\Services\QuranPlanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

new class extends Component {
    public $userLevel; // 'teacher' or 'student'
    #[Url]
    public $edit = null;

    #[Url]
    public $studentId;
    public $startDate;
    public $daysCount = 16;
    public $activeDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday'];
    public $description;
    public $planType = 'hifz_review';

    public $planDays = [];
    public $allSurahs = [];
    public $fillDirection = 'reverse';
    public $fillTarget = 'hifz';
    public $bulkStartSurah;
    public $bulkStartVerse;
    
    public $selectAll = false;
    public $selectionStart = null;

    // Wizard state
    public $step = 1;
    public $isGenerated = false;
    public $memorizedUpToSurah = 114;
    public $memorizedUpToVerse = 1;

    public function mount()
    {
        $this->userLevel = Auth::guard('student')->check() ? 'student' : 'teacher';
        $this->allSurahs = Surah::orderBy('id')->get();
        $this->bulkStartSurah = 114;
        $this->bulkStartVerse = 1;
        $this->memorizedUpToSurah = 114;

        if ($this->edit) {
            $plan = StudentPlan::with('days.fromAyah', 'days.toAyah', 'days.reviewFromAyah', 'days.reviewToAyah')->findOrFail($this->edit);
            $this->studentId = $plan->student_id;
            $this->startDate = $plan->start_date->format('Y-m-d');
            $this->daysCount = $plan->days_count;
            $this->activeDays = $plan->active_days ?? [];
            $this->description = $plan->description;
            $this->planType = $plan->plan_type;
            
            $this->isGenerated = true;
            $this->step = 7;

            $this->planDays = $plan->days->map(function ($d) {
                return [
                    'id' => $d->id,
                    'date' => $d->date->toDateString(),
                    'hijri' => $this->getHijriLabel($d->date),
                    'day_name_ar' => $d->day_name,
                    'from_surah_id' => $d->fromAyah?->surah_id,
                    'from_verse' => $d->fromAyah?->verse_number,
                    'to_surah_id' => $d->toAyah?->surah_id,
                    'to_verse' => $d->toAyah?->verse_number,
                    'review_from_surah_id' => $d->reviewFromAyah?->surah_id,
                    'review_from_verse' => $d->reviewFromAyah?->verse_number,
                    'review_to_surah_id' => $d->reviewToAyah?->surah_id,
                    'review_to_verse' => $d->reviewToAyah?->verse_number,
                    'selected' => false,
                ];
            })->toArray();
        } else {
            $this->startDate = now()->format('Y-m-d');
            if ($this->userLevel === 'teacher') {
                $teacher = Auth::guard('teacher')->user();
                if (!$this->studentId) {
                    $this->studentId = Student::where('circle_id', $teacher->circles()->first()?->id ?? 0)->first()->id ?? null;
                }
                $this->step = 1;
            } else {
                $this->studentId = Auth::guard('student')->id();
                $this->step = 2; // skip student selection for student
            }
        }
    }

    public function nextStep()
    {
        if ($this->step == 1 && $this->userLevel == 'teacher') {
            $this->validate(['studentId' => 'required'], ['studentId.required' => 'يرجى اختيار الطالب أولاً.']);
        } elseif ($this->step == 4) {
            $this->validate([
                'startDate' => 'required|date',
                'daysCount' => 'required|integer|min:1|max:365',
            ]);
        } elseif ($this->step == 5) {
            $this->validate(['activeDays' => 'required|array|min:1'], ['activeDays.required' => 'يجب اختيار يوم واحد على الأقل.']);
        }
        $this->step++;
    }

    public function prevStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
        if ($this->step == 1 && $this->userLevel == 'student') {
            // Cannot go back to step 1 if student
            $this->step = 2;
        }
    }

    public function resetPlan()
    {
        $this->isGenerated = false;
        $this->step = $this->userLevel === 'teacher' ? 1 : 2;
        $this->planDays = [];
    }

    public function updatedBulkStartSurah()
    {
        $this->bulkStartVerse = 1;
    }

    public function updatedMemorizedUpToSurah()
    {
        $this->memorizedUpToVerse = 1;
    }

    public function updatedPlanDays($value, $key)
    {
        if (
            str_ends_with($key, 'from_surah_id') || str_ends_with($key, 'to_surah_id') ||
            str_ends_with($key, 'review_from_surah_id') || str_ends_with($key, 'review_to_surah_id')
        ) {
            $parts = explode('.', $key);
            $index = $parts[0];
            $field = $parts[1];

            $verseField = str_replace('_surah_id', '_verse', $field);
            $this->planDays[$index][$verseField] = 1;
        }
    }

    public function updatedSelectAll($value)
    {
        foreach ($this->planDays as &$day) {
            $day['selected'] = $value;
        }
    }

    public function toggleDaySelection($index)
    {
        if (!isset($this->planDays[$index])) return;

        if ($this->selectionStart === null) {
            $this->selectionStart = $index;
            $this->planDays[$index]['selected'] = !$this->planDays[$index]['selected'];
        } else {
            $start = min($this->selectionStart, $index);
            $end = max($this->selectionStart, $index);
            $targetValue = $this->planDays[$this->selectionStart]['selected'];

            for ($i = $start; $i <= $end; $i++) {
                $this->planDays[$i]['selected'] = $targetValue;
            }
            $this->selectionStart = null;
        }
    }

    public function with()
    {
        $students = [];
        if ($this->userLevel === 'teacher') {
            $teacher = Auth::guard('teacher')->user();
            $circleIds = $teacher->circles->pluck('id');
            $students = Student::whereIn('circle_id', $circleIds)->orderBy('name')->get();
        }

        return [
            'students' => $students,
        ];
    }

    public function generateDays()
    {
        $this->validate([
            'studentId' => 'required',
            'startDate' => 'required|date',
            'daysCount' => 'required|integer|min:1|max:100',
            'activeDays' => 'required|array|min:1',
        ]);

        if ($this->planType === 'review') {
            if ($this->fillDirection === 'reverse') {
                // Starts reviewing from the LAST surah (An-Nas) going down to the memorized bound
                $this->bulkStartSurah = 114;
            } else {
                $this->bulkStartSurah = 1;
            }
            $this->bulkStartVerse = 1;
        }

        $this->planDays = [];
        $currentDate = Carbon::parse($this->startDate);
        $count = 0;

        $ayah = Ayah::where('surah_id', $this->bulkStartSurah)
            ->where('verse_number', $this->bulkStartVerse)
            ->first() ?: Ayah::first();

        $surahId = $ayah->surah_id ?? 114;
        $verseNum = $ayah->verse_number ?? 1;

        while ($count < $this->daysCount) {
            $dayOfWeek = $currentDate->format('l');
            if (in_array($dayOfWeek, $this->activeDays)) {

                $this->planDays[] = [
                    'date' => $currentDate->toDateString(),
                    'hijri' => $this->getHijriLabel($currentDate),
                    'day_name_ar' => $this->translateDay($dayOfWeek),
                    'from_surah_id' => $surahId,
                    'from_verse' => $verseNum,
                    'to_surah_id' => $surahId,
                    'to_verse' => $verseNum,
                    'review_from_surah_id' => $surahId,
                    'review_from_verse' => $verseNum,
                    'review_to_surah_id' => $surahId,
                    'review_to_verse' => $verseNum,
                    'selected' => false,
                ];
                $count++;
            }
            $currentDate->addDay();
        }

        $this->isGenerated = true;
        $this->step = 7;
    }

    public function fillSelected($type, $target = null, array $selectedIndices = [])
    {
        foreach ($this->planDays as $index => &$day) {
            $day['selected'] = in_array($index, $selectedIndices);
        }
        unset($day);

        $target = $target ?? $this->fillTarget;
        
        if ($this->planType === 'review') {
            $target = 'review';
        } elseif ($this->planType === 'hifz') {
            $target = 'hifz';
        }

        $service = app(QuranPlanService::class);
        $lastDayStart = null;
        $lastDayEnd = null;
        $fixedReviewStart = null;

        $fromSurahKey = $target === 'review' ? 'review_from_surah_id' : 'from_surah_id';
        $fromVerseKey = $target === 'review' ? 'review_from_verse' : 'from_verse';
        $toSurahKey = $target === 'review' ? 'review_to_surah_id' : 'to_surah_id';
        $toVerseKey = $target === 'review' ? 'review_to_verse' : 'to_verse';

        if ($target === 'review') {
            foreach ($this->planDays as $day) {
                if ($day['selected']) {
                    $fixedReviewStart = Ayah::where('surah_id', $day['review_from_surah_id'])
                        ->where('verse_number', $day['review_from_verse'])
                        ->first();
                    break;
                }
            }
        }

        $resetNextReview = false;

        foreach ($this->planDays as &$day) {
            if (!$day['selected']) {
                $lastDayStart = Ayah::where('surah_id', $day[$fromSurahKey])
                    ->where('verse_number', $day[$fromVerseKey])
                    ->first();
                $lastDayEnd = Ayah::where('surah_id', $day[$toSurahKey])
                    ->where('verse_number', $day[$toVerseKey])
                    ->first();
                continue;
            }

            if ($target === 'review') {
                $maxPossibleEnd = null;

                if ($this->planType === 'hifz_review') {
                    $hifzStartAyah = Ayah::where('surah_id', $day['from_surah_id'])
                        ->where('verse_number', $day['from_verse'])
                        ->first();

                    if (!$hifzStartAyah || !$fixedReviewStart) {
                        continue;
                    }

                    $maxPossibleEnd = $service->getAyahBefore($hifzStartAyah, $this->fillDirection);
                } else {
                    // Pure Review bounds based on exact memorized Ayah
                    $maxPossibleEnd = Ayah::where('surah_id', $this->memorizedUpToSurah)
                        ->where('verse_number', $this->memorizedUpToVerse)
                        ->first();
                    if (!$fixedReviewStart) continue;
                }

                // 1. Determine the Start of this day's review
                if ($type === 'all_previous') {
                    $actualStart = $fixedReviewStart;
                    $targetReviewEnd = $maxPossibleEnd;
                } else {
                    if ($resetNextReview) {
                        $actualStart = $fixedReviewStart;
                        $resetNextReview = false;
                    } elseif ($lastDayEnd) {
                        $actualStart = $service->getNextStartAyah($lastDayStart, $lastDayEnd, $type, $this->fillDirection);
                    } else {
                        $actualStart = $fixedReviewStart;
                    }

                    if (!$actualStart) {
                        $actualStart = $fixedReviewStart;
                    }

                    // Ensure Start is not already beyond limit
                    if ($maxPossibleEnd && $service->isExceeding($actualStart, $maxPossibleEnd, $this->fillDirection)) {
                        $actualStart = $maxPossibleEnd;
                        $resetNextReview = true;
                    }

                    // 2. Determine the End of this day's review based on volume
                    $targetReviewEnd = $service->getEndAyah($actualStart, $type, $this->fillDirection);

                    // 3. Cap the End so it doesn't overlap limits
                    if ($maxPossibleEnd && $service->isExceeding($targetReviewEnd, $maxPossibleEnd, $this->fillDirection)) {
                        $targetReviewEnd = $maxPossibleEnd;
                        $resetNextReview = true;
                    }
                }

                $day['review_from_surah_id'] = $actualStart->surah_id;
                $day['review_from_verse'] = $actualStart->verse_number;
                $day['review_to_surah_id'] = $targetReviewEnd->surah_id;
                $day['review_to_verse'] = $targetReviewEnd->verse_number;

                $lastDayStart = $actualStart;
                $lastDayEnd = $targetReviewEnd;
                continue;
            }

            if ($lastDayStart && $lastDayEnd) {
                $start = $service->getNextStartAyah($lastDayStart, $lastDayEnd, $type, $this->fillDirection);
                if ($start) {
                    $day[$fromSurahKey] = $start->surah_id;
                    $day[$fromVerseKey] = $start->verse_number;
                }
            }

            $currentStart = Ayah::where('surah_id', $day[$fromSurahKey])
                ->where('verse_number', $day[$fromVerseKey])
                ->first();

            if ($currentStart) {
                $hifzStartAyah = null;
                if ($target === 'review' && $this->planType === 'hifz_review') {
                    $hifzStartAyah = Ayah::where('surah_id', $day['from_surah_id'])
                        ->where('verse_number', $day['from_verse'])
                        ->first();
                }

                $end = $service->getEndAyah($currentStart, $type, $this->fillDirection, $hifzStartAyah);

                $day[$toSurahKey] = $end->surah_id;
                $day[$toVerseKey] = $end->verse_number;

                $lastDayStart = $currentStart;
                $lastDayEnd = $end;
            }
        }
    }

    public function save()
    {
        $this->validate([
            'studentId' => 'required',
            'planDays' => 'required|array|min:1',
        ]);

        if ($this->edit) {
            $plan = StudentPlan::findOrFail($this->edit);
            $plan->update([
                'start_date' => $this->startDate,
                'days_count' => $this->daysCount,
                'active_days' => $this->activeDays,
                'description' => $this->description,
                'plan_type' => $this->planType,
            ]);

            $existingIds = collect($this->planDays)->pluck('id')->filter()->toArray();
            $plan->days()->whereNotIn('id', $existingIds)->delete();
        } else {
            $student = Student::findOrFail($this->studentId);
            $teacherId = $this->userLevel === 'teacher' ? Auth::guard('teacher')->id() : $student->circle?->teachers()->first()?->id;

            $plan = StudentPlan::create([
                'student_id' => $this->studentId,
                'teacher_id' => $teacherId, // Can be null if the student has no circle
                'start_date' => $this->startDate,
                'days_count' => $this->daysCount,
                'active_days' => $this->activeDays,
                'description' => $this->description,
                'plan_type' => $this->planType,
                'status' => 'active',
                'is_approved' => $this->userLevel === 'teacher',
                'created_by_role' => $this->userLevel,
            ]);
        }

        foreach ($this->planDays as $dayData) {
            $from = null;
            $to = null;
            $revFrom = null;
            $revTo = null;

            if (in_array($this->planType, ['hifz', 'hifz_review'])) {
                $from = Ayah::where('surah_id', $dayData['from_surah_id'])->where('verse_number', $dayData['from_verse'])->first();
                $to = Ayah::where('surah_id', $dayData['to_surah_id'])->where('verse_number', $dayData['to_verse'])->first();
            }

            if (in_array($this->planType, ['review', 'hifz_review'])) {
                $revFrom = Ayah::where('surah_id', $dayData['review_from_surah_id'])->where('verse_number', $dayData['review_from_verse'])->first();
                $revTo = Ayah::where('surah_id', $dayData['review_to_surah_id'])->where('verse_number', $dayData['review_to_verse'])->first();
            }

            $dayAttributes = [
                'date' => $dayData['date'],
                'day_name' => $dayData['day_name_ar'],
                'from_ayah_id' => $from?->id,
                'to_ayah_id' => $to?->id,
                'review_from_ayah_id' => $revFrom?->id,
                'review_to_ayah_id' => $revTo?->id,
            ];

            if (isset($dayData['id'])) {
                $plan->days()->where('id', $dayData['id'])->update($dayAttributes);
            } else {
                $plan->days()->create($dayAttributes);
            }
        }

        if ($this->userLevel === 'student') {
            return redirect()->route('student.plan')->with('success', 'تم الحفظ وسيتم عرضها على المعلم للاعتماد');
        }

        return redirect()->route('teacher.student-plans')->with('success', 'تم حفظ الخطة بنجاح');
    }

    protected function getHijriLabel(\DateTimeInterface $date)
    {
        $formatter = new \IntlDateFormatter(
            'ar_SA@calendar=islamic-umalqura',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            'Asia/Riyadh',
            \IntlDateFormatter::TRADITIONAL,
            'd MMMM yyyy'
        );

        return $formatter->format($date->getTimestamp());
    }

    protected function translateDay($day)
    {
        $days = [
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت',
        ];

        return $days[$day] ?? $day;
    }
};
?>

<div class="space-y-6" x-data="{
        days:          $wire.entangle('planDays'),
        planType:      $wire.entangle('planType').live,
        fillDirection: $wire.entangle('fillDirection').live,
        fillTarget:    $wire.entangle('fillTarget').live,
        
        selected:       [],
        selectAll:      false,
        selectionStart: null,
        filling:        false,
        
        init() {
            this.selected = Array((this.days && this.days.length) ? this.days.length : 0).fill(false);
        },
        toggleAll() {
            this.selectAll = ! this.selectAll;
            this.selected = this.selected.map(() => this.selectAll);
        },
        toggleDay(index) {
            if (this.selected.length === 0 && this.days && this.days.length > 0) {
                this.init();
            }
            if (this.selectionStart === null) {
                this.selectionStart = index;
                this.selected[index] = !this.selected[index];
            } else {
                const start   = Math.min(this.selectionStart, index);
                const end     = Math.max(this.selectionStart, index);
                const desired = this.selected[this.selectionStart];
                for (let i = start; i <= end; i++) {
                    this.selected[i] = desired;
                }
                this.selectionStart = null;
            }
        },
        doFill(type) {
            this.filling = true;
            const indices = this.selected.reduce((acc, v, i) => { if (v) acc.push(i); return acc; }, []);
            $wire.fillSelected(type, this.fillTarget, indices).then(() => { this.filling = false; });
        },
    }">

    <!-- WIZARD UI -->
    @if(!$isGenerated)
        <flux:card class="max-w-2xl mx-auto p-0 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm relative">
            <!-- Header bar with Progress -->
            <div class="bg-zinc-50 dark:bg-zinc-800/50 p-6 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <flux:heading size="xl" level="1">{{ __('إعداد الخطة الدراسية') }}</flux:heading>
                        <flux:subheading>{{ __('معالج إنشاء الجدول بخطوات بسيطة') }}</flux:subheading>
                    </div>
                    <div class="text-xs font-bold text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded-full">
                        {{ __('خطوة') }} {{ $step }} {{ __('من') }} 6
                    </div>
                </div>
                <div class="relative w-full h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded overflow-hidden mt-4">
                    <div class="absolute top-0 bottom-0 right-0 bg-indigo-500 transition-all duration-300" style="width: {{ ($step / 6) * 100 }}%"></div>
                </div>
            </div>

            <div class="p-6 h-[400px] flex flex-col justify-center">
                <!-- STEP 1: Student -->
                @if($step == 1 && $userLevel == 'teacher')
                    <div class="space-y-6 text-center animate-in fade-in zoom-in duration-300">
                        <div class="mx-auto bg-indigo-50 dark:bg-zinc-800 w-16 h-16 rounded-full flex items-center justify-center text-indigo-500 mb-4">
                            <flux:icon icon="user" class="size-8" />
                        </div>
                        <flux:heading size="lg" class="mb-4">{{ __('لمن تريد إنشاء الخطة؟') }}</flux:heading>
                        <div class="max-w-md mx-auto text-right">
                            <flux:select wire:model="studentId" placeholder="{{ __('اختر الطالب') }}">
                                @foreach($students as $student)
                                    <flux:select.option value="{{ $student->id }}">{{ $student->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                @endif

                <!-- STEP 2: Plan Type -->
                @if($step == 2)
                    <div class="space-y-6 text-center animate-in fade-in zoom-in duration-300">
                        <div class="mx-auto bg-emerald-50 dark:bg-zinc-800 w-16 h-16 rounded-full flex items-center justify-center text-emerald-500 mb-4">
                            <flux:icon icon="rectangle-stack" class="size-8" />
                        </div>
                        <flux:heading size="lg" class="mb-6">{{ __('حدد نوع المسار القرآني') }}</flux:heading>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-lg mx-auto">
                            <button @click="planType = 'hifz'" :class="planType === 'hifz' ? 'ring-2 ring-emerald-500 shadow-md bg-emerald-50 dark:bg-emerald-900/20' : 'border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 pointer-events-auto'" class="flex flex-col items-center p-4 rounded-xl transition-all cursor-pointer">
                                <flux:icon icon="book-open" class="size-8 text-emerald-600 dark:text-emerald-400 mb-2" />
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ __('حفظ فقط') }}</span>
                            </button>
                            <button @click="planType = 'hifz_review'" :class="planType === 'hifz_review' ? 'ring-2 ring-indigo-500 shadow-md bg-indigo-50 dark:bg-indigo-900/20' : 'border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800'" class="flex flex-col items-center p-4 rounded-xl transition-all cursor-pointer">
                                <flux:icon icon="document-duplicate" class="size-8 text-indigo-600 dark:text-indigo-400 mb-2" />
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ __('حفظ ومراجعة') }}</span>
                            </button>
                            <button @click="planType = 'review'" :class="planType === 'review' ? 'ring-2 ring-amber-500 shadow-md bg-amber-50 dark:bg-amber-900/20' : 'border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800'" class="flex flex-col items-center p-4 rounded-xl transition-all cursor-pointer">
                                <flux:icon icon="arrow-path" class="size-8 text-amber-600 dark:text-amber-400 mb-2" />
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ __('مراجعة فقط') }}</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- STEP 3: Direction -->
                @if($step == 3)
                    <div class="space-y-6 text-center animate-in fade-in zoom-in duration-300">
                        <div class="mx-auto bg-blue-50 dark:bg-zinc-800 w-16 h-16 rounded-full flex items-center justify-center text-blue-500 mb-4">
                            <flux:icon icon="arrows-up-down" class="size-8" />
                        </div>
                        <flux:heading size="lg" class="mb-6">{{ __('حدد اتجاه الحفظ / المراجعة') }}</flux:heading>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-md mx-auto">
                            <button @click="fillDirection = 'forward'" :class="fillDirection === 'forward' ? 'ring-2 ring-blue-500 shadow-md bg-blue-50 dark:bg-blue-900/20' : 'border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800'" class="flex flex-col items-center p-4 rounded-xl transition-all cursor-pointer text-right relative overflow-hidden">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ __('تصاعدي') }}</span>
                                    <flux:icon icon="arrow-up" class="size-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-xs text-zinc-500">{{ __('مثال: من الفاتحة إلى البقرة') }}</span>
                            </button>
                            <button @click="fillDirection = 'reverse'" :class="fillDirection === 'reverse' ? 'ring-2 ring-blue-500 shadow-md bg-blue-50 dark:bg-blue-900/20' : 'border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800'" class="flex flex-col items-center p-4 rounded-xl transition-all cursor-pointer text-right relative overflow-hidden">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ __('تنازلي') }}</span>
                                    <flux:icon icon="arrow-down" class="size-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-xs text-zinc-500">{{ __('مثال: من الناس إلى البقرة') }}</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- STEP 4: Dates & Count -->
                @if($step == 4)
                    <div class="space-y-6 text-center animate-in fade-in zoom-in duration-300">
                        <div class="mx-auto bg-rose-50 dark:bg-zinc-800 w-16 h-16 rounded-full flex items-center justify-center text-rose-500 mb-4">
                            <flux:icon icon="calendar-days" class="size-8" />
                        </div>
                        <flux:heading size="lg" class="mb-6">{{ __('من متى تبدأ الخطة؟ وما مدتها؟') }}</flux:heading>
                        
                        <div class="max-w-md mx-auto space-y-4 text-right">
                            <div class="space-y-1">
                                <flux:label>{{ __('تاريخ البدء') }}</flux:label>
                                <livewire:teacher.hijri-datepicker wire:model="startDate" />
                            </div>
                            <flux:input type="number" min="1" max="365" label="{{ __('عدد الأيام المراد جدولتها') }}" wire:model="daysCount" placeholder="مثال: 16" />
                        </div>
                    </div>
                @endif

                <!-- STEP 5: Active Days -->
                @if($step == 5)
                    <div class="space-y-6 text-center animate-in fade-in zoom-in duration-300">
                        <div class="mx-auto bg-purple-50 dark:bg-zinc-800 w-16 h-16 rounded-full flex items-center justify-center text-purple-500 mb-4">
                            <flux:icon icon="calendar" class="size-8" />
                        </div>
                        <flux:heading size="lg" class="mb-6">{{ __('أيام التسميع خلال الأسبوع') }}</flux:heading>
                        
                        <div class="max-w-md mx-auto text-right bg-zinc-50 dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $d)
                                    <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 p-2 rounded-lg border border-zinc-100 dark:border-zinc-800 shadow-sm">
                                        <flux:checkbox wire:model="activeDays" value="{{ $d }}" id="day-{{ $d }}" />
                                        <flux:label for="day-{{ $d }}" class="text-sm cursor-pointer">{{ $this->translateDay($d) }}</flux:label>
                                    </div>
                                @endforeach
                            </div>
                            @error('activeDays')
                                <div class="text-red-500 text-xs mt-2 text-center">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endif

                <!-- STEP 6: Starting Surah / Memorized -->
                @if($step == 6)
                    <div class="space-y-6 text-center animate-in fade-in zoom-in duration-300">
                        <div class="mx-auto bg-teal-50 dark:bg-zinc-800 w-16 h-16 rounded-full flex items-center justify-center text-teal-500 mb-4">
                            <flux:icon icon="map-pin" class="size-8" />
                        </div>
                        
                        <template x-if="planType === 'review'">
                            <div>
                                <flux:heading size="lg" class="mb-2">{{ __('إلى أين يحفظ الطالب؟') }}</flux:heading>
                                <p class="text-sm text-zinc-500 mb-6 px-4">{{ __('هذا سيمثل الحاجز أو النهاية التي تتوقف عندها خطة المراجعة ولن تتجاوزها.') }}</p>
                                <div class="max-w-md mx-auto text-right space-y-4 bg-zinc-50 dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
                                    <flux:select wire:model.live="memorizedUpToSurah" label="{{ __('غيباً وإتقاناً حتى سورة:') }}">
                                        @foreach($allSurahs as $surah)
                                            <flux:select.option value="{{ $surah->id }}">{{ $surah->name_arabic }}</flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <div>
                                        <flux:label>{{ __('وحتى آية:') }}</flux:label>
                                        <select wire:model="memorizedUpToVerse" class="w-full text-sm p-2 border border-zinc-200 rounded-lg dark:bg-zinc-900 dark:border-zinc-700">
                                            @php
                                                $memSurah = $allSurahs->find($memorizedUpToSurah);
                                                $memCount = $memSurah?->verses_count ?? 1;
                                            @endphp
                                            @for($i = 1; $i <= $memCount; $i++)
                                                <option value="{{ $i }}">{{ __('آية') }} {{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="planType !== 'review'">
                            <div>
                                <flux:heading size="lg" class="mb-2">{{ __('ما هي نقطة البداية الافتراضية للجدول؟') }}</flux:heading>
                                <p class="text-sm text-zinc-500 mb-6 px-4">{{ __('سيتم ملء اليوم الأول بهذه السورة ويمكنك إكمال الجدول تلقائياً منها.') }}</p>
                                <div class="max-w-md mx-auto text-right space-y-4 bg-zinc-50 dark:bg-zinc-800 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
                                    <flux:select wire:model.live="bulkStartSurah" label="{{ __('السورة') }}">
                                        @foreach($allSurahs as $surah)
                                            <flux:select.option value="{{ $surah->id }}">{{ $surah->name_arabic }}</flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <div>
                                        <flux:label>{{ __('الآية') }}</flux:label>
                                        <select wire:model="bulkStartVerse" class="w-full text-sm p-2 border border-zinc-200 rounded-lg dark:bg-zinc-900 dark:border-zinc-700">
                                            @php
                                                $startSurah = $allSurahs->find($bulkStartSurah);
                                                $startCount = $startSurah?->verses_count ?? 1;
                                            @endphp
                                            @for($i = 1; $i <= $startCount; $i++)
                                                <option value="{{ $i }}">{{ __('آية') }} {{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @endif
            </div>

            <!-- Footer Toolbar -->
            <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-zinc-800 flex justify-between items-center transition-all">
                <flux:button variant="ghost" icon="arrow-right" class="" wire:click="prevStep" :disabled="$step == 1 || ($step == 2 && $userLevel == 'student')">
                    {{ __('السابق') }}
                </flux:button>
                
                @if($step < 6)
                    <flux:button variant="primary" wire:click="nextStep" class="min-w-[120px]">
                        {{ __('التالي') }}
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="generateDays" icon="sparkles" class="min-w-[120px] bg-indigo-600 hover:bg-indigo-700 border-none">
                        {{ __('توليد وتأكيد الجدول') }}
                    </flux:button>
                @endif
            </div>
        </flux:card>
    @else
        <!-- GENERATED STATE -->

        <!-- Summary Bar -->
        <flux:card class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50/50 dark:bg-emerald-900/10 mb-6">
            <div class="flex items-center gap-4">
                <div class="bg-emerald-100 dark:bg-emerald-800 w-12 h-12 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-300 shrink-0">
                    <flux:icon icon="document-check" class="size-6" />
                </div>
                <div>
                    <h3 class="font-bold text-zinc-900 dark:text-zinc-100 text-lg">{{ __('تم توليد مسودة الجدول بنجاح!') }}</h3>
                    <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        <flux:badge color="zinc" size="sm">{{ $planType === 'hifz' ? __('مسار حفظ') : ($planType === 'review' ? __('مسار مراجعة') : __('حفظ ومراجعة')) }}</flux:badge>
                        <flux:badge color="zinc" size="sm">{{ $fillDirection === 'forward' ? __('تصاعدي') : __('تنازلي') }}</flux:badge>
                        <span class="flex items-center gap-1"><flux:icon icon="calendar" class="size-3"/> {{ $daysCount }} يوم</span>
                    </div>
                </div>
            </div>
            
            <flux:button wire:click="resetPlan" variant="ghost" icon="arrow-path" class="text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/40">
                {{ __('إعادة ضبط وملء من جديد') }}
            </flux:button>
        </flux:card>

        <!-- TABLE SECTION -->
        <div class="space-y-4 h-[80vh]">
            @if(count($planDays) > 0)
                <flux:card class="p-0 overflow-hidden flex flex-col h-[calc(100vh-250px)]">

                    {{-- Toolbar --}}
                    <div class="p-4 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/90 shrink-0 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <flux:heading size="sm" class="flex items-center gap-2">
                                <flux:icon icon="bolt" class="size-4 text-indigo-500" />
                                {{ __('أدوات الملء التلقائي (التحديد)') }}
                            </flux:heading>

                            {{-- fillTarget — Alpine only --}}
                            <div x-show="planType === 'hifz_review'" class="flex gap-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg p-0.5">
                                <button @click="fillTarget = 'hifz'" :class="fillTarget === 'hifz' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-zinc-500 dark:text-zinc-400'" class="px-3 flex-1  py-2 text-lg font-medium rounded-md transition-colors">
                                    {{ __('الحفظ') }}
                                </button>
                                <button @click="fillTarget = 'review'" :class="fillTarget === 'review' ? 'bg-white dark:bg-zinc-700 shadow-sm text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400'" class="px-3 flex-1  py-2 text-lg font-medium rounded-md transition-colors">
                                    {{ __('المراجعة') }}
                                </button>
                            </div>
                        </div>

                        {{-- Fill buttons --}}
                        <div class="flex flex-wrap gap-1 items-center bg-white dark:bg-zinc-900 px-2 py-1.5 rounded border border-zinc-200 dark:border-zinc-700">
                            <div x-show="planType === 'review' || fillTarget === 'review'" class="flex flex-wrap gap-1">
                                <template x-if="planType === 'hifz_review'">
                                    <flux:button size="xs" class="bg-indigo-600 text-white hover:bg-indigo-700" @click="doFill('all_previous')">{{ __('جميع ما سبق') }}</flux:button>
                                </template>
                                <flux:button size="xs" class="bg-indigo-600 text-white hover:bg-indigo-700" @click="doFill('juz')">{{ __('جزء') }}</flux:button>
                                <flux:button size="xs" class="bg-indigo-600 text-white hover:bg-indigo-700" @click="doFill('half_juz')">{{ __('نصف جزء') }}</flux:button>
                                <flux:button size="xs" class="bg-indigo-600 text-white hover:bg-indigo-700" @click="doFill('5_pages')">{{ __('5 صفحات') }}</flux:button>
                                <flux:button size="xs" class="bg-indigo-600 text-white hover:bg-indigo-700" @click="doFill('3_surahs')">{{ __('3 سور') }}</flux:button>
                            </div>
                            <div x-show="planType === 'hifz' || (planType === 'hifz_review' && fillTarget !== 'review')" class="flex flex-wrap gap-1">
                                <flux:button size="xs" class="bg-indigo-600 text-white hover:bg-indigo-700" @click="doFill('surah')">{{ __('سورة') }}</flux:button>
                                <flux:button size="xs" variant="ghost" @click="doFill('page')">{{ __('صفحة') }}</flux:button>
                                <flux:button size="xs" variant="ghost" @click="doFill('half')">{{ __('1/2 صفحة') }}</flux:button>
                                <flux:button size="xs" variant="ghost" @click="doFill('third')">{{ __('1/3 صفحة') }}</flux:button>
                            </div>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div x-show="filling" x-cloak class="relative h-1 bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                        <div class="absolute inset-y-0 w-1/3 bg-gradient-to-r from-transparent via-indigo-500 to-transparent" style="animation: shimmer 1.2s ease-in-out infinite;"></div>
                    </div>

                    <div class="overflow-auto flex-1 h-full min-h-[300px]">
                        <table class="w-full text-sm text-right align-middle whitespace-nowrap relative">
                            <thead class="sticky top-0 z-10 bg-zinc-100 dark:bg-zinc-800 shadow-sm border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="p-4 w-32 font-bold text-zinc-700 dark:text-zinc-300 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition" @click="toggleAll()">
                                        <div class="flex items-center gap-2">
                                            <flux:icon icon="check-circle" class="size-4 opacity-50" />
                                            <span>{{ __('التاريخ') }}</span>
                                        </div>
                                    </th>

                                    <th x-show="planType === 'hifz' || (planType === 'hifz_review' && fillTarget === 'hifz')" class="p-3 min-w-[300px] border-r border-zinc-200 dark:border-zinc-700">
                                        <span class="text-indigo-600 dark:text-indigo-400 font-bold ml-2">{{ __('الحفظ') }}</span>
                                        <div class="grid grid-cols-2 text-xs text-zinc-500 mt-1">
                                            <span>من</span><span>إلى</span>
                                        </div>
                                    </th>

                                    <th x-show="planType === 'review' || (planType === 'hifz_review' && fillTarget === 'review')" class="p-3 min-w-[300px] border-r border-zinc-200 dark:border-zinc-700">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold ml-2">{{ __('المراجعة') }}</span>
                                        <div class="grid grid-cols-2 text-xs text-zinc-500 mt-1">
                                            <span>من</span><span>إلى</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($planDays as $index => $day)
                                    <tr wire:key="row-{{ $index }}">
                                        <td class="p-3 cursor-pointer transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-900/40"
                                            :class="{
                                                'bg-indigo-100 dark:bg-indigo-900/60': selected[{{ $index }}],
                                                'ring-2 ring-inset ring-indigo-500': selectionStart === {{ $index }}
                                            }" 
                                            @click="toggleDay({{ $index }})">
                                            <div class="flex flex-col">
                                                <span class="font-bold whitespace-normal leading-tight" :class="selected[{{ $index }}] ? 'text-indigo-700 dark:text-indigo-300' : ''">
                                                    {{ $day['day_name_ar'] }}
                                                </span>
                                                <span class="text-[11px] mt-0.5 whitespace-normal" :class="selected[{{ $index }}] ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-500 dark:text-zinc-400'">
                                                    {{ $day['hijri'] }}
                                                </span>
                                            </div>
                                        </td>

                                        <td x-show="planType === 'hifz' || (planType === 'hifz_review' && fillTarget === 'hifz')" class="h-16 border-r border-zinc-200 dark:border-zinc-700 p-2">
                                            <div class="h-full grid grid-cols-2 gap-2">
                                                <div class="h-full flex flex-col md:flex-row md:items-center gap-1 bg-white dark:bg-zinc-900 p-1.5 rounded border border-zinc-100 dark:border-zinc-800">
                                                    <select wire:model.live="planDays.{{ $index }}.from_surah_id" class="w-full text-xs p-1 border-none bg-transparent focus:ring-0">
                                                        @foreach($allSurahs as $surah)
                                                            <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select wire:model="planDays.{{ $index }}.from_verse" class="w-full md:w-20 text-xs p-1 border-none bg-zinc-50 dark:bg-zinc-800 rounded font-mono text-center">
                                                        @php
                                                            $fSurah = $allSurahs->find($day['from_surah_id']);
                                                            $fCount = $fSurah?->verses_count ?? 1;
                                                        @endphp
                                                        @for($i = 1; $i <= $fCount; $i++)
                                                            <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div class="h-full flex flex-col md:flex-row md:items-center gap-1 bg-white dark:bg-zinc-900 p-1.5 rounded border border-zinc-100 dark:border-zinc-800">
                                                    <select wire:model.live="planDays.{{ $index }}.to_surah_id" class="w-full text-xs p-1 border-none bg-transparent focus:ring-0">
                                                        @foreach($allSurahs as $surah)
                                                            <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select wire:model="planDays.{{ $index }}.to_verse" class="w-full md:w-20 text-xs p-1 border-none bg-zinc-50 dark:bg-zinc-800 rounded font-mono text-center">
                                                        @php
                                                            $tSurah = $allSurahs->find($day['to_surah_id']);
                                                            $tCount = $tSurah?->verses_count ?? 1;
                                                        @endphp
                                                        @for($i = 1; $i <= $tCount; $i++)
                                                            <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>
                                        </td>

                                        <td x-show="planType === 'review' || (planType === 'hifz_review' && fillTarget === 'review')" class="h-16 border-r border-zinc-200 dark:border-zinc-700 p-2">
                                            <div class="h-full grid grid-cols-2 gap-2">
                                                <div class="h-full flex flex-col md:flex-row md:items-center gap-1 bg-white dark:bg-zinc-900 p-1.5 rounded border border-zinc-100 dark:border-zinc-800">
                                                    <select wire:model.live="planDays.{{ $index }}.review_from_surah_id" class="w-full text-xs p-1 border-none bg-transparent focus:ring-0">
                                                        @foreach($allSurahs as $surah)
                                                            <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select wire:model="planDays.{{ $index }}.review_from_verse" class="w-full md:w-20 text-xs p-1 border-none bg-zinc-50 dark:bg-zinc-800 rounded font-mono text-center">
                                                        @php
                                                            $rfSurah = $allSurahs->find($day['review_from_surah_id']);
                                                            $rfCount = $rfSurah?->verses_count ?? 1;
                                                        @endphp
                                                        @for($i = 1; $i <= $rfCount; $i++)
                                                            <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div class="h-full flex flex-col md:flex-row md:items-center gap-1 bg-white dark:bg-zinc-900 p-1.5 rounded border border-zinc-100 dark:border-zinc-800">
                                                    <select wire:model.live="planDays.{{ $index }}.review_to_surah_id" class="w-full text-xs p-1 border-none bg-transparent focus:ring-0">
                                                        @foreach($allSurahs as $surah)
                                                            <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select wire:model="planDays.{{ $index }}.review_to_verse" class="w-full md:w-20 text-xs p-1 border-none bg-zinc-50 dark:bg-zinc-800 rounded font-mono text-center">
                                                        @php
                                                            $rtSurah = $allSurahs->find($day['review_to_surah_id']);
                                                            $rtCount = $rtSurah?->verses_count ?? 1;
                                                        @endphp
                                                        @for($i = 1; $i <= $rtCount; $i++)
                                                            <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-zinc-100 dark:border-zinc-800 flex justify-between bg-zinc-50 dark:bg-zinc-800/90 z-20">
                        <div class="text-sm text-zinc-500 pt-2">{{ __('تأكد من مراجعة النطاقات التلقائية أو تعديلها قبل الحفظ النهائي.') }}</div>
                        <flux:button variant="primary" wire:click="save" icon="check" class="bg-emerald-600 hover:bg-emerald-700 text-white min-w-[200px] border-none">{{ __('اعتماد الخطة وإرسالها') }}</flux:button>
                    </div>
                </flux:card>
            @endif
        </div>
    @endif
</div>
