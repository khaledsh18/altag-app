<?php

use Livewire\Component;
use App\Models\Student;
use App\Models\Surah;
use App\Models\Ayah;
use App\Models\StudentPlan;
use App\Services\QuranPlanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

new class extends Component {
    #[Url]
    public $edit = null;

    public $studentId;
    public $startDate;
    public $daysCount = 30;
    public $activeDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday'];
    public $description;
    public $planType = 'hifz_review';

    public $planDays = [];
    public $allSurahs = [];
    public $bulkStartSurah;
    public $bulkStartVerse;

    public function mount()
    {
        $this->allSurahs = Surah::orderBy('id')->get();
        $this->bulkStartSurah = 114;
        $this->bulkStartVerse = 1;

        if ($this->edit) {
            $plan = StudentPlan::with('days.fromAyah', 'days.toAyah', 'days.reviewFromAyah', 'days.reviewToAyah')->findOrFail($this->edit);
            $this->studentId = $plan->student_id;
            $this->startDate = $plan->start_date->format('Y-m-d');
            $this->daysCount = $plan->days_count;
            $this->activeDays = $plan->active_days ?? [];
            $this->description = $plan->description;
            $this->planType = $plan->plan_type;

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
            $this->studentId = Student::where('circle_id', Auth::guard('teacher')->user()->circles()->first()?->id)->first()->id ?? null;
        }
    }

    public function with()
    {
        $teacher = Auth::guard('teacher')->user();
        $circleIds = $teacher->circles->pluck('id');
        $students = Student::whereIn('circle_id', $circleIds)->orderBy('name')->get();

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

        // Notify Alpine to reset selection state
        $this->dispatch('daysGenerated');
    }

    /**
     * $target and $direction are now passed from Alpine to avoid round-trips.
     */
    public function fillSelected($type, $target = 'hifz', $direction = 'reverse')
    {
        $service = app(QuranPlanService::class);
        $lastDayStart = null;
        $lastDayEnd = null;
        $fixedReviewStart = null;

        $fromSurahKey = $target === 'review' ? 'review_from_surah_id' : 'from_surah_id';
        $fromVerseKey = $target === 'review' ? 'review_from_verse' : 'from_verse';
        $toSurahKey = $target === 'review' ? 'review_to_surah_id' : 'to_surah_id';
        $toVerseKey = $target === 'review' ? 'review_to_verse' : 'to_verse';

        if ($target === 'review' && $this->planType === 'hifz_review') {
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
            if (! $day['selected']) {
                $lastDayStart = Ayah::where('surah_id', $day[$fromSurahKey])
                    ->where('verse_number', $day[$fromVerseKey])
                    ->first();
                $lastDayEnd = Ayah::where('surah_id', $day[$toSurahKey])
                    ->where('verse_number', $day[$toVerseKey])
                    ->first();
                continue;
            }

            if ($target === 'review' && $this->planType === 'hifz_review') {
                $hifzStartAyah = Ayah::where('surah_id', $day['from_surah_id'])
                    ->where('verse_number', $day['from_verse'])
                    ->first();

                if (! $hifzStartAyah || ! $fixedReviewStart) {
                    continue;
                }

                $maxPossibleEnd = $service->getAyahBefore($hifzStartAyah, $direction);

                if ($type === 'all_previous') {
                    $actualStart = $fixedReviewStart;
                    $targetReviewEnd = $maxPossibleEnd;
                } else {
                    if ($resetNextReview) {
                        $actualStart = $fixedReviewStart;
                        $resetNextReview = false;
                    } elseif ($lastDayEnd) {
                        $actualStart = $service->getNextStartAyah($lastDayStart, $lastDayEnd, $type, $direction);
                    } else {
                        $actualStart = $fixedReviewStart;
                    }

                    if (! $actualStart) {
                        $actualStart = $fixedReviewStart;
                    }

                    if ($service->isExceeding($actualStart, $maxPossibleEnd, $direction)) {
                        $actualStart = $maxPossibleEnd;
                        $resetNextReview = true;
                    }

                    $targetReviewEnd = $service->getEndAyah($actualStart, $type, $direction);

                    if ($service->isExceeding($targetReviewEnd, $maxPossibleEnd, $direction)) {
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
                $start = $service->getNextStartAyah($lastDayStart, $lastDayEnd, $type, $direction);
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
                if ($target === 'review') {
                    $hifzStartAyah = Ayah::where('surah_id', $day['from_surah_id'])
                        ->where('verse_number', $day['from_verse'])
                        ->first();
                }

                $end = $service->getEndAyah($currentStart, $type, $direction, $hifzStartAyah);

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
                'student_id' => $this->studentId,
                'start_date' => $this->startDate,
                'days_count' => $this->daysCount,
                'active_days' => $this->activeDays,
                'description' => $this->description,
                'plan_type' => $this->planType,
            ]);

            $existingIds = collect($this->planDays)->pluck('id')->filter()->toArray();
            $plan->days()->whereNotIn('id', $existingIds)->delete();
        } else {
            $plan = StudentPlan::create([
                'student_id' => $this->studentId,
                'teacher_id' => Auth::guard('teacher')->id(),
                'start_date' => $this->startDate,
                'days_count' => $this->daysCount,
                'active_days' => $this->activeDays,
                'description' => $this->description,
                'plan_type' => $this->planType,
                'status' => 'active',
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

{{--
    Alpine.js manages ALL pure-UI state:
      • planType   — switches tabs (sets $wire.planType before next request)
      • fillTarget — hifz vs review (passed as param to fillSelected)
      • fillDirection — forward vs reverse (passed as param)
      • selectAll / selectionStart — row selection
      • days — @entangle('planDays') so changes sync to Livewire deferred
      • surahs JSON — enables dynamic verse-count ranges without server round-trips

    Livewire only fires on:  generateDays | fillSelected | save
--}}
<div class="space-y-6"
     x-data="{
         planType:       @js($planType),
         days:           @entangle('planDays'),
         fillTarget:     'hifz',
         fillDirection:  'reverse',
         selectAll:      false,
         selectionStart: null,
         surahs: @js($allSurahs->map(fn($s) => ['id' => $s->id, 'name' => $s->name_arabic, 'verses_count' => $s->verses_count])->values()),

         init() {
             $wire.on('daysGenerated', () => {
                 this.selectAll      = false;
                 this.selectionStart = null;
             });
         },

         /* Returns [1, 2, ... n] for a given surah id — no server round-trip */
         getVerseRange(surahId) {
             const surah = this.surahs.find(s => s.id == surahId);
             const count = surah ? surah.verses_count : 1;
             return Array.from({ length: count }, (_, i) => i + 1);
         },

         /* Sets Alpine local `planType` AND queues $wire.planType for next request */
         setPlanType(type) {
             this.planType   = type;
             $wire.planType  = type;
             /* Reset fillTarget to a valid value when switching modes */
             this.fillTarget = 'hifz';
         },

         /* Toggle a single day or a range — purely client-side */
         toggleDay(index) {
             if (this.selectionStart === null) {
                 this.selectionStart              = index;
                 this.days[index].selected        = ! this.days[index].selected;
             } else {
                 const start       = Math.min(this.selectionStart, index);
                 const end         = Math.max(this.selectionStart, index);
                 const targetValue = this.days[this.selectionStart].selected;
                 for (let i = start; i <= end; i++) {
                     this.days[i].selected = targetValue;
                 }
                 this.selectionStart = null;
             }
         },

         /* Select / deselect all rows — purely client-side */
         doSelectAll() {
             const newVal   = ! this.selectAll;
             this.selectAll = newVal;
             for (let i = 0; i < this.days.length; i++) {
                 this.days[i].selected = newVal;
             }
         },

         /* Trigger fillSelected — passes Alpine state as params so no extra round-trips */
         doFill(type) {
             this.filling = true;
             $wire.fillSelected(type, this.fillTarget, this.fillDirection)
                 .then(() => { this.filling = false; });
         },

         filling: false,        /* true while fillSelected is in-flight */
     }">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('إعداد الخطة الدراسية') }}</flux:heading>
            <flux:subheading>{{ __('قم بتخصيص المهام اليومية للطالب') }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- ━━━━━━━━━━━━━━━━━━━━━━━━ SIDEBAR ━━━━━━━━━━━━━━━━━━━━━━━━ --}}
        <flux:card class="lg:col-span-1 space-y-4">

            {{-- Plan-type tabs — Alpine only, zero round-trips --}}
            <div class="p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg flex flex-col gap-1">
                @foreach(['hifz' => 'حفظ فقط', 'hifz_review' => 'حفظ ومراجعة', 'review' => 'مراجعة فقط'] as $type => $label)
                    <button @click="setPlanType('{{ $type }}')"
                            :class="planType === '{{ $type }}'
                                ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-indigo-400'
                                : 'text-zinc-500'"
                            class="w-full py-1.5 text-xs font-medium rounded-md transition-colors">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <flux:select label="{{ __('الطالب') }}" wire:model="studentId">
                @foreach($students as $student)
                    <flux:select.option value="{{ $student->id }}">{{ $student->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="space-y-1">
                <flux:label>{{ __('البداية') }}</flux:label>
                <livewire:teacher.hijri-datepicker wire:model="startDate" />
            </div>

            <flux:input type="number" label="{{ __('عدد الأيام') }}" wire:model="daysCount" />

            <div class="space-y-2">
                <flux:label>{{ __('الأيام النشطة') }}</flux:label>
                <div class="grid grid-cols-2 gap-x-2 gap-y-1">
                    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $d)
                        <div class="flex items-center gap-2">
                            <flux:checkbox wire:model="activeDays" value="{{ $d }}" id="day-{{ $d }}" />
                            <flux:label for="day-{{ $d }}" class="text-xs">{{ $this->translateDay($d) }}</flux:label>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Default start point — bulkStartVerse is Alpine x-for; no wire:model.live --}}
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg space-y-3">
                <flux:heading size="sm">{{ __('نقطة البداية الافتراضية') }}</flux:heading>

                {{-- Surah select: x-model sets $wire.bulkStartSurah deferred --}}
                <select x-model="$wire.bulkStartSurah"
                        @change="$wire.bulkStartVerse = 1"
                        class="w-full text-xs p-1 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700">
                    @foreach($allSurahs as $surah)
                        <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                    @endforeach
                </select>

                {{-- Verse select: Alpine x-for reacts to $wire.bulkStartSurah change instantly --}}
                <select x-model="$wire.bulkStartVerse"
                        class="w-full text-xs p-1 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700">
                    <template x-for="v in getVerseRange($wire.bulkStartSurah)" :key="v">
                        <option :value="v" x-text="'{{ __('آية') }} ' + v"></option>
                    </template>
                </select>
            </div>

            <flux:button variant="primary" class="w-full" wire:click="generateDays">
                {{ __('توليد الجدول') }}
            </flux:button>
        </flux:card>

        {{-- ━━━━━━━━━━━━━━━━━━━━━━━━ TABLE AREA ━━━━━━━━━━━━━━━━━━━━━━ --}}
        <div class="lg:col-span-3 space-y-4 h-vh">

            {{-- Empty state — Alpine x-show instead of @if --}}
            <div x-show="days.length === 0"
                 class="h-64 flex flex-col items-center justify-center border-2 border-dashed border-zinc-100 dark:border-zinc-800 rounded-2xl text-zinc-400">
                <flux:icon icon="pencil-square" size="xl" class="mb-2 opacity-50 text-indigo-500" />
                <p class="text-sm">{{ __('اضغط توليد الجدول للبدء') }}</p>
            </div>

            <flux:card x-show="days.length > 0" class="p-0 overflow-hidden flex flex-col h-[calc(100vh)]">

                {{-- Toolbar --}}
                <div class="p-4 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/90 shrink-0 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <flux:heading size="sm">{{ __('التحديد التلقائي') }}</flux:heading>

                        {{-- Direction toggle — Alpine only --}}
                        <div class="inline-flex items-center gap-1 p-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg text-xs font-medium">
                            <button @click="fillDirection = 'forward'"
                                    :class="fillDirection === 'forward' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-white' : 'text-zinc-500'"
                                    class="px-2.5 py-1 rounded-md transition-colors">
                                {{ __('تصاعدي') }}
                            </button>
                            <button @click="fillDirection = 'reverse'"
                                    :class="fillDirection === 'reverse' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-white' : 'text-zinc-500'"
                                    class="px-2.5 py-1 rounded-md transition-colors">
                                {{ __('تنازلي') }}
                            </button>
                        </div>

                        {{-- Target toggle (hifz_review only) — Alpine only --}}
                        <div x-show="planType === 'hifz_review'"
                             class="inline-flex items-center gap-1 p-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg text-xs font-medium">
                            <button @click="fillTarget = 'hifz'"
                                    :class="fillTarget === 'hifz' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-white' : 'text-zinc-500'"
                                    class="px-2.5 py-1 rounded-md transition-colors">
                                {{ __('تحديد للحفظ') }}
                            </button>
                            <button @click="fillTarget = 'review'"
                                    :class="fillTarget === 'review' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-white' : 'text-zinc-500'"
                                    class="px-2.5 py-1 rounded-md transition-colors">
                                {{ __('تحديد للمراجعة') }}
                            </button>
                        </div>
                    </div>

                    {{-- Fill buttons — call doFill() which passes Alpine state to Livewire --}}
                    <div class="flex flex-wrap gap-1 items-center bg-white dark:bg-zinc-900 px-2 py-1.5 rounded border border-zinc-200 dark:border-zinc-700">

                        {{-- Review buttons --}}
                        <template x-if="fillTarget === 'review'">
                            <div class="flex flex-wrap gap-1">
                                <flux:button size="xs" class="bg-indigo-600" x-show="planType === 'hifz_review'" @click="doFill('all_previous')">
                                    {{ __('جميع ما سبق') }}
                                </flux:button>
                                <flux:button size="xs" class="bg-indigo-600" @click="doFill('juz')">{{ __('جزء') }}</flux:button>
                                <flux:button size="xs" class="bg-indigo-600" @click="doFill('half_juz')">{{ __('نصف جزء') }}</flux:button>
                                <flux:button size="xs" class="bg-indigo-600" @click="doFill('5_pages')">{{ __('5 صفحات') }}</flux:button>
                                <flux:button size="xs" class="bg-indigo-600" @click="doFill('3_surahs')">{{ __('3 سور') }}</flux:button>
                            </div>
                        </template>

                        {{-- Hifz buttons --}}
                        <template x-if="fillTarget !== 'review'">
                            <div class="flex flex-wrap gap-1">
                                <flux:button size="xs" class="bg-indigo-600" @click="doFill('surah')">{{ __('سورة') }}</flux:button>
                                <flux:button size="xs" variant="ghost" @click="doFill('page')">{{ __('صفحات') }}</flux:button>
                                <flux:button size="xs" variant="ghost" @click="doFill('half')">{{ __('1/2 صفحة') }}</flux:button>
                                <flux:button size="xs" variant="ghost" @click="doFill('third')">{{ __('1/3 صفحة') }}</flux:button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ── Progress bar ── visible while fillSelected is running ── --}}
                <div x-show="filling" x-cloak class="relative h-1 bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                    <div class="absolute inset-y-0 w-1/3 bg-gradient-to-r from-transparent via-indigo-500 to-transparent"
                         style="animation: shimmer 1.2s ease-in-out infinite;"
                         x-ref="shimmerBar"></div>
                </div>

                {{-- Table --}}
                <div class="overflow-auto flex-1">
                    <table class="w-full text-sm text-right align-middle whitespace-nowrap relative">
                        <thead class="sticky top-0 z-10 bg-zinc-100 dark:bg-zinc-800 shadow-sm border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                {{-- Date header doubles as select-all — Alpine only --}}
                                <th class="p-3 w-32 font-medium text-zinc-500 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition"
                                    @click="doSelectAll()">
                                    <div class="flex items-center gap-2">
                                        <span>{{ __('التاريخ') }}</span>
                                    </div>
                                </th>

                                {{-- Hifz column header — x-show instead of @if --}}
                                <th x-show="planType === 'hifz' || (planType === 'hifz_review' && fillTarget === 'hifz')"
                                    class="p-3 min-w-[300px] border-r border-zinc-200 dark:border-zinc-700">
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold ml-2">{{ __('الحفظ') }}</span>
                                    <div class="grid grid-cols-2 text-xs text-zinc-500 mt-1">
                                        <span>من</span><span>إلى</span>
                                    </div>
                                </th>

                                {{-- Review column header — x-show instead of @if --}}
                                <th x-show="planType === 'review' || (planType === 'hifz_review' && fillTarget === 'review')"
                                    class="p-3 min-w-[300px] border-r border-zinc-200 dark:border-zinc-700">
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

                                    {{-- Date / selection cell — Alpine only --}}
                                    <td :class="{
                                            'bg-indigo-100 dark:bg-indigo-900/60': days[{{ $index }}]?.selected,
                                            'ring-2 ring-inset ring-indigo-500': selectionStart === {{ $index }}
                                        }"
                                        class="p-3 cursor-pointer transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-900/40"
                                        @click="toggleDay({{ $index }})">
                                        <div class="flex flex-col">
                                            <span :class="days[{{ $index }}]?.selected ? 'text-indigo-700 dark:text-indigo-300' : ''"
                                                  class="text-xs font-bold">{{ $day['day_name_ar'] }}</span>
                                            <span :class="days[{{ $index }}]?.selected ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400'"
                                                  class="text-[10px]">{{ $day['hijri'] }}</span>
                                        </div>
                                    </td>

                                    {{-- Hifz selects — x-model on Alpine days; x-for for verse ranges --}}
                                    <td x-show="planType === 'hifz' || (planType === 'hifz_review' && fillTarget === 'hifz')"
                                        class="h-15 border-r border-zinc-200 dark:border-zinc-700">
                                        <div class="h-full grid grid-cols-2 gap-2">
                                            {{-- From --}}
                                            <div class="h-full flex items-center gap-1">
                                                <select x-model="days[{{ $index }}].from_surah_id"
                                                        @change="days[{{ $index }}].from_verse = 1"
                                                        class="h-full flex-3 text-[11px] p-1.5 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700">
                                                    @foreach($allSurahs as $surah)
                                                        <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                                                    @endforeach
                                                </select>
                                                <select x-model="days[{{ $index }}].from_verse"
                                                        class="h-full flex-1 text-[11px] p-1.5 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700">
                                                    <template x-for="v in getVerseRange(days[{{ $index }}].from_surah_id)" :key="v">
                                                        <option :value="v" x-text="v"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            {{-- To --}}
                                            <div class="flex items-center gap-1">
                                                <select x-model="days[{{ $index }}].to_surah_id"
                                                        @change="days[{{ $index }}].to_verse = 1"
                                                        class="h-full flex-3 text-[11px] p-1.5 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700">
                                                    @foreach($allSurahs as $surah)
                                                        <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                                                    @endforeach
                                                </select>
                                                <select x-model="days[{{ $index }}].to_verse"
                                                        class="h-full flex-1 text-[11px] p-1.5 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700 w-16">
                                                    <template x-for="v in getVerseRange(days[{{ $index }}].to_surah_id)" :key="v">
                                                        <option :value="v" x-text="v"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Review selects — same pattern --}}
                                    <td x-show="planType === 'review' || (planType === 'hifz_review' && fillTarget === 'review')"
                                        class="h-15 border-r border-zinc-200 dark:border-zinc-700">
                                        <div class="h-full grid grid-cols-2 gap-2">
                                            {{-- From --}}
                                            <div class="h-full flex items-center gap-1">
                                                <select x-model="days[{{ $index }}].review_from_surah_id"
                                                        @change="days[{{ $index }}].review_from_verse = 1"
                                                        class="h-full flex-3 text-[11px] p-1.5 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700 w-full max-w-[100px]">
                                                    @foreach($allSurahs as $surah)
                                                        <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                                                    @endforeach
                                                </select>
                                                <select x-model="days[{{ $index }}].review_from_verse"
                                                        class="h-full flex-1 text-[11px] p-1.5 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700 w-16">
                                                    <template x-for="v in getVerseRange(days[{{ $index }}].review_from_surah_id)" :key="v">
                                                        <option :value="v" x-text="v"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            {{-- To --}}
                                            <div class="h-full flex items-center gap-1">
                                                <select x-model="days[{{ $index }}].review_to_surah_id"
                                                        @change="days[{{ $index }}].review_to_verse = 1"
                                                        class="h-full flex-3 text-[11px] p-1.5 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700 w-full max-w-[100px]">
                                                    @foreach($allSurahs as $surah)
                                                        <option value="{{ $surah->id }}">{{ $surah->name_arabic }}</option>
                                                    @endforeach
                                                </select>
                                                <select x-model="days[{{ $index }}].review_to_verse"
                                                        class="h-full flex-1 text-[11px] p-1.5 border border-zinc-200 rounded dark:bg-zinc-800 dark:border-zinc-700 w-16">
                                                    <template x-for="v in getVerseRange(days[{{ $index }}].review_to_surah_id)" :key="v">
                                                        <option :value="v" x-text="v"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 flex justify-end">
                    <flux:button variant="primary" wire:click="save">{{ __('اعتماد الخطة') }}</flux:button>
                </div>
            </flux:card>
        </div>
    </div>
</div>