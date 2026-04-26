<div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <flux:button href="{{ route('teacher.leaderboards') }}" wire:navigate variant="ghost" size="sm" icon="arrow-right" class="rtl:rotate-180" />
                <flux:heading size="xl">{{ __('الرصد اليدوي: ') }} <span class="text-indigo-600 dark:text-indigo-400">{{ $leaderboard->title }}</span></flux:heading>
            </div>
            <flux:subheading class="ms-10">{{ __('قم بمنح النقاط للطلاب في البنود المخصصة بشكل يومي.') }}</flux:subheading>
        </div>

        <div class="w-full md:w-64 bg-white dark:bg-zinc-900 p-2 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
            <livewire:teacher.hijri-datepicker wire:model.live="date" />
        </div>
    </div>

    @if($leaderboard->criteria->isEmpty())
        <div class="text-center py-12 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
            <flux:icon icon="clipboard-document" class="size-10 mx-auto text-zinc-400 mb-3" />
            <flux:heading size="md" class="mb-2">{{ __('لا توجد بنود مخصصة') }}</flux:heading>
            <p class="text-zinc-500">{{ __('لم تقم بإضافة بنود التقييم لهذه المسابقة (مثل: الزي، الهدوء). يمكنك إضافتها من إعدادات اللوحة.') }}</p>
        </div>
    @else
        <flux:card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="p-4 font-semibold text-zinc-800 dark:text-zinc-200 w-1/4">{{ __('اسم الطالب') }}</th>
                            
                            <!-- Manual Criteria Headers -->
                            @foreach($leaderboard->criteria as $criterion)
                                <th class="p-4 font-semibold text-zinc-800 dark:text-zinc-200 text-center">
                                    <div class="flex flex-col items-center">
                                        <span>{{ $criterion->name }}</span>
                                        <flux:badge color="emerald" size="sm" class="mt-1">{{ $criterion->points }} {{ __('نقاط') }}</flux:badge>
                                    </div>
                                </th>
                            @endforeach

                            <!-- Automated Points Headers -->
                            <th class="p-2 font-semibold text-zinc-500 dark:text-zinc-400 text-center bg-zinc-100/50 dark:bg-zinc-800/30 border-r border-zinc-200 dark:border-zinc-700 w-16">
                                <div class="flex flex-col items-center gap-1">
                                    <flux:icon icon="book-open" variant="micro" class="size-4" />
                                    <span class="text-[10px]">{{ __('حفظ') }}</span>
                                </div>
                            </th>
                            <th class="p-2 font-semibold text-zinc-500 dark:text-zinc-400 text-center bg-zinc-100/50 dark:bg-zinc-800/30 w-16">
                                <div class="flex flex-col items-center gap-1">
                                    <flux:icon icon="arrow-path" variant="micro" class="size-4" />
                                    <span class="text-[10px]">{{ __('مراجعة') }}</span>
                                </div>
                            </th>
                            <th class="p-2 font-semibold text-zinc-500 dark:text-zinc-400 text-center bg-zinc-100/50 dark:bg-zinc-800/30 w-16">
                                <div class="flex flex-col items-center gap-1">
                                    <flux:icon icon="clock" variant="micro" class="size-4" />
                                    <span class="text-[10px]">{{ __('حضور') }}</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($students as $student)
                            @php
                                $studentScoreIds = $scoresMap->get($student->id, []);
                                $dailyTotal = $dailyScores[$student->id]['total'] ?? 0;
                                $dailyAutomated = $dailyScores[$student->id]['automated'] ?? 0;
                                $dailyManual = $dailyScores[$student->id]['manual'] ?? 0;
                            @endphp
                            <tr class="even:bg-zinc-50/50 odd:bg-white dark:even:bg-zinc-800/20 dark:odd:bg-zinc-900/10 hover:!bg-zinc-100/80 dark:hover:!bg-zinc-800/50 transition-colors">
                                <td class="p-4 font-medium text-zinc-900 dark:text-zinc-100 w-1/4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs">
                                                {{ mb_substr($student->name, 0, 1) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span>{{ $student->name }}</span>
                                                @if($dailyAutomated > 0)
                                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-normal">
                                                        <flux:icon icon="cpu-chip" variant="micro" class="size-3 inline" /> {{ __('تلقائي اليوم:') }} {{ $dailyAutomated }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 text-xs px-2 py-1 rounded-md font-bold flex items-center gap-1" title="{{ __('مجموع نقاط اليوم (التلقائية + اليدوية)') }}">
                                            {{ $dailyTotal }} <flux:icon icon="star" variant="solid" class="size-3" />
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Manual Criteria Cells -->
                                @foreach($leaderboard->criteria as $criterion)
                                    @php
                                        $hasScore = in_array($criterion->id, $studentScoreIds);
                                    @endphp
                                    <td class="p-4 text-center border-l border-zinc-50 dark:border-zinc-800/50">
                                        <button 
                                            wire:click="toggleScore({{ $student->id }}, {{ $criterion->id }}, {{ $criterion->points }})"
                                            class="inline-flex w-10 h-10 items-center justify-center rounded-xl border-2 transition-all duration-200 {{ $hasScore ? 'bg-emerald-500 border-emerald-500 text-white shadow-md shadow-emerald-500/20 scale-110' : 'bg-white border-zinc-200 text-zinc-300 hover:border-emerald-200 hover:text-emerald-300 dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-700 dark:hover:border-emerald-800 dark:hover:text-emerald-700' }}"
                                            title="{{ $hasScore ? __('إزالة النقطة') : __('منح النقطة') }}"
                                        >
                                            <flux:icon icon="check" variant="micro" class="size-6" />
                                        </button>
                                    </td>
                                @endforeach

                                <!-- Automated Points Cells -->
                                <td class="p-2 text-center bg-zinc-50/50 dark:bg-zinc-800/30 border-r border-zinc-200/50 dark:border-zinc-700/50">
                                    @if(($dailyScores[$student->id]['hifz'] ?? 0) > 0)
                                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 font-bold text-xs">
                                            +{{ $dailyScores[$student->id]['hifz'] }}
                                        </span>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-600 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="p-2 text-center bg-zinc-50/50 dark:bg-zinc-800/30">
                                    @if(($dailyScores[$student->id]['review'] ?? 0) > 0)
                                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 font-bold text-xs">
                                            +{{ $dailyScores[$student->id]['review'] }}
                                        </span>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-600 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="p-2 text-center bg-zinc-50/50 dark:bg-zinc-800/30">
                                    @if(($dailyScores[$student->id]['attendance'] ?? 0) > 0)
                                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 font-bold text-xs">
                                            +{{ $dailyScores[$student->id]['attendance'] }}
                                        </span>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-600 text-sm">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif
</div>
