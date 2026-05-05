<x-layouts.role-shell>
    <x-slot:sidebar>
        @include('guardian.sidebar-nav')
    </x-slot:sidebar>

    @php
        $guardian = auth()->guard('guardian')->user();
        $students = $guardian->students()->with('circle')->get();
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">لوحة تحكم ولي الأمر</h1>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900/30 dark:text-blue-400">
                        <flux:icon icon="users" class="size-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">عدد الأبناء</p>
                        <h3 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $students->count() }}</h3>
                    </div>
                </div>
            </div>

            <div
                class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 text-green-600 rounded-lg dark:bg-green-900/30 dark:text-green-400">
                        <flux:icon icon="check-circle" class="size-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">حالة الاعتماد</p>
                        <h3 class="text-xl font-bold text-neutral-900 dark:text-white">
                            {{ $guardian->is_approved ? 'معتمد' : 'قيد الانتظار' }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="relative h-full flex-1 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <h2 class="text-lg font-bold mb-4">بيانات الأبناء</h2>

            <div class="space-y-4">
                @forelse($students as $student)
                    @php
                        $memorizedPages = $student->memorizedPagesCount();
                        $percentage = $student->memorizationPercentage();

                        // Today's plan day
                        $todayPlanDay = \App\Models\StudentPlanDay::whereHas(
                            'plan',
                            fn($q) =>
                            $q->where('student_id', $student->id)
                                ->whereIn('plan_type', ['hifz', 'hifz_review'])
                        )
                            ->whereDate('date', today())
                            ->with(['fromAyah.surah', 'toAyah.surah', 'reviewFromAyah.surah', 'reviewToAyah.surah'])
                            ->first();

                        // Last scored day
                        $lastScored = \App\Models\StudentPlanDay::whereHas(
                            'plan',
                            fn($q) =>
                            $q->where('student_id', $student->id)
                        )
                            ->whereNotNull('hifz_achievement')
                            ->orderByDesc('date')
                            ->first();

                        // This week attendance
                        $weekStart = now()->startOfWeek(\Carbon\Carbon::SATURDAY);
                        $weekAttend = $student->attendances()
                            ->whereBetween('date', [$weekStart, now()])
                            ->get();
                        $presentCount = $weekAttend->whereIn('status', ['present', 'late'])->count();
                        $totalCount = $weekAttend->count();
                    @endphp

                    <div
                        class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-900 border border-neutral-100 dark:border-neutral-800">

                        {{-- Header row --}}
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-neutral-200 dark:bg-neutral-700 rounded-lg">
                                    <flux:icon icon="academic-cap" class="size-5 text-neutral-600 dark:text-neutral-300" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $student->name }}
                                    </h4>
                                    <p class="text-xs text-neutral-500">
                                        {{ $student->circle?->name ?? 'لم تُحدَّد حلقة بعد' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('guardian.student.challenge.create', $student->id) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition-colors">
                                    <flux:icon icon="trophy" class="size-3.5" />
                                    مكافأة جديدة
                                </a>
                                <a href="{{ route('guardian.student', $student->id) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-500/20 transition-colors">
                                    التفاصيل
                                    <flux:icon icon="arrow-left" class="size-3.5" />
                                </a>
                            </div>
                        </div>

                        {{-- Stats row --}}
                        <div class="grid grid-cols-3 gap-3 mb-4">

                            {{-- Today's task --}}
                            <div
                                class="rounded-lg bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-3">
                                <p class="text-xs text-neutral-500 mb-1 flex items-center gap-1">
                                    <flux:icon icon="calendar-days" class="size-3.5" />
                                    مهمة اليوم
                                </p>
                                @if($todayPlanDay && $todayPlanDay->fromAyah)
                                    <p class="text-xs font-medium text-neutral-800 dark:text-neutral-200 leading-relaxed">
                                        {{ $todayPlanDay->fromAyah->surah->name_arabic }}
                                        {{ $todayPlanDay->fromAyah->verse_number }}-{{ $todayPlanDay->toAyah->verse_number }}
                                    </p>
                                @else
                                    <p class="text-xs text-neutral-400">لا توجد مهمة</p>
                                @endif
                            </div>

                            {{-- Last score --}}
                            <div
                                class="rounded-lg bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-3">
                                <p class="text-xs text-neutral-500 mb-1 flex items-center gap-1">
                                    <flux:icon icon="star" class="size-3.5" />
                                    آخر تقييم
                                </p>
                                @if($lastScored)
                                    <div class="flex items-center gap-1.5">
                                        @php
                                            $scoreColor = match ($lastScored->hifz_achievement) {
                                                3 => 'text-emerald-600 dark:text-emerald-400',
                                                2 => 'text-amber-600 dark:text-amber-400',
                                                default => 'text-red-600 dark:text-red-400',
                                            };
                                            $scoreLabel = match ($lastScored->hifz_achievement) {
                                                3 => 'ممتاز',
                                                2 => 'جيد',
                                                default => 'ضعيف',
                                            };
                                        @endphp
                                        <span class="text-xs font-bold {{ $scoreColor }}">{{ $scoreLabel }}</span>
                                        <span class="text-xs text-neutral-400">({{ $lastScored->date->diffForHumans() }})</span>
                                    </div>
                                @else
                                    <p class="text-xs text-neutral-400">لا يوجد بعد</p>
                                @endif
                            </div>

                            {{-- Weekly attendance --}}
                            <div
                                class="rounded-lg bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-3">
                                <p class="text-xs text-neutral-500 mb-1 flex items-center gap-1">
                                    <flux:icon icon="clock" class="size-3.5" />
                                    هذا الأسبوع
                                </p>
                                @if($totalCount > 0)
                                    <p class="text-xs font-medium text-neutral-800 dark:text-neutral-200">
                                        {{ $presentCount }}/{{ $totalCount }} أيام
                                    </p>
                                @else
                                    <p class="text-xs text-neutral-400">لا توجد بيانات</p>
                                @endif
                            </div>
                        </div>

                        {{-- Memorization progress --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs text-neutral-500 flex items-center gap-1">
                                    <flux:icon icon="book-open" class="size-3.5" />
                                    نسبة المحفوظ من القرآن الكريم
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-neutral-500">{{ number_format($memorizedPages) }} صفحة</span>
                                    <span
                                        class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $percentage }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-500"
                                    style="width: {{ min($percentage, 100) }}%"></div>
                            </div>
                            @if($memorizedPages > 0)
                                <p class="text-xs text-neutral-400 mt-1">
                                    ≈ {{ floor($memorizedPages / 20) }} جزء من 30
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-neutral-500">
                        لا يوجد أبناء مسجلين حالياً
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.role-shell>