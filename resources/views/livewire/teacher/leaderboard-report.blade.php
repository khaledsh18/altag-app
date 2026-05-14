<div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <flux:button href="{{ route('teacher.leaderboards') }}" wire:navigate variant="ghost" size="sm" icon="arrow-right" class="rtl:rotate-180" />
                <flux:heading size="xl">{{ __('التقرير الشامل للمنافسة: ') }} <span class="text-indigo-600 dark:text-indigo-400">{{ $leaderboard->title }}</span></flux:heading>
            </div>
            <flux:subheading class="ms-10">{{ __('يُظهر هذا التقرير الترتيب العام وإجمالي النقاط التلقائية واليدوية منذ انطلاق المسابقة وحتى اليوم.') }}</flux:subheading>
        </div>
        <div class="bg-white dark:bg-zinc-800 px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 text-sm font-semibold flex items-center gap-2">
            <flux:icon icon="calendar" class="size-4 text-zinc-500" />
            <span>{{ $leaderboard->start_date->format('Y-m-d') }}</span>
            <span class="text-zinc-400">-</span>
            <span>{{ $leaderboard->end_date ? $leaderboard->end_date->format('Y-m-d') : __('الآن') }}</span>
        </div>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800/80 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="p-4 font-semibold text-zinc-800 dark:text-zinc-200">{{ __('الترتيب والطالب') }}</th>
                        <th class="p-4 font-semibold text-zinc-800 dark:text-zinc-200 text-center">{{ __('الإجمالي') }}</th>
                        
                        <!-- Automated columns -->
                        <th class="p-4 font-semibold text-indigo-700 dark:text-indigo-400 text-center bg-indigo-50/50 dark:bg-indigo-900/10 border-r border-l border-zinc-100 dark:border-zinc-700">{{ __('تلقائي (حفظ)') }}</th>
                        <th class="p-4 font-semibold text-indigo-700 dark:text-indigo-400 text-center bg-indigo-50/50 dark:bg-indigo-900/10 border-r border-l border-zinc-100 dark:border-zinc-700">{{ __('تلقائي (مراجعة)') }}</th>
                        <th class="p-4 font-semibold text-indigo-700 dark:text-indigo-400 text-center bg-indigo-50/50 dark:bg-indigo-900/10 border-r border-l border-zinc-100 dark:border-zinc-700">{{ __('تلقائي (حضور)') }}</th>

                        <!-- Manual criteria columns -->
                        @foreach($leaderboard->criteria as $criterion)
                            <th class="p-4 font-semibold text-emerald-700 dark:text-emerald-400 text-center bg-emerald-50/50 dark:bg-emerald-900/10 border-l border-zinc-100 dark:border-zinc-700">
                                <div class="flex flex-col items-center">
                                    <span>{{ $criterion->name }}</span>
                                    <span class="text-[10px] font-normal opacity-70 mt-0.5">{{ $criterion->points }} {{ __('نقاط') }}</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($standings as $index => $standing)
                        @php $rank = $index + 1; @endphp
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30   s">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm {{ $rank === 1 ? 'bg-amber-100 text-amber-600 border border-amber-300' : ($rank === 2 ? 'bg-slate-100 text-slate-600 border border-slate-300' : ($rank === 3 ? 'bg-orange-100 text-orange-600 border border-orange-300' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 border border-zinc-200 dark:border-zinc-700')) }}">
                                        {{ $rank }}
                                    </div>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $standing['student']->name }}</span>
                                </div>
                            </td>
                            <td class="p-4 text-center font-bold text-lg text-emerald-600 dark:text-emerald-500">
                                {{ $standing['score'] }}
                            </td>
                            
                            <td class="p-4 text-center text-sm font-medium text-zinc-600 dark:text-zinc-400 bg-indigo-50/20 dark:bg-indigo-900/5 border-r border-l border-zinc-100 dark:border-zinc-800/50">
                                {{ $standing['details']['hifz'] }}
                            </td>
                            <td class="p-4 text-center text-sm font-medium text-zinc-600 dark:text-zinc-400 bg-indigo-50/20 dark:bg-indigo-900/5 border-r border-l border-zinc-100 dark:border-zinc-800/50">
                                {{ $standing['details']['review'] }}
                            </td>
                            <td class="p-4 text-center text-sm font-medium text-zinc-600 dark:text-zinc-400 bg-indigo-50/20 dark:bg-indigo-900/5 border-r border-l border-zinc-100 dark:border-zinc-800/50">
                                {{ $standing['details']['attendance'] }}
                            </td>

                            @foreach($leaderboard->criteria as $criterion)
                                @php
                                    $timesEarned = $standing['details']['criteria_counts'][$criterion->id] ?? 0;
                                    $pointsEarned = $timesEarned * $criterion->points;
                                @endphp
                                <td class="p-4 text-center bg-emerald-50/20 dark:bg-emerald-900 border-l border-zinc-100 dark:border-zinc-800/50">
                                    @if($timesEarned > 0)
                                        <div class="flex flex-col items-center">
                                            <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ $pointsEarned }}</span>
                                            <span class="text-[10px] text-zinc-400 bg-white dark:bg-zinc-800 px-1.5 py-0.5 rounded shadow-sm">{{ $timesEarned }} {{ __('مرات') }}</span>
                                        </div>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    
                    @if($standings->isEmpty())
                        <tr>
                            <td colspan="100%" class="p-8 text-center text-zinc-500">
                                {{ __('لا يوجد طلاب في هذه الحلقة بعد.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
