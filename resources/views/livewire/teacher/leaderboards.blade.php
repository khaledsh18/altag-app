<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('لوحات المنافسة والتحفيز') }}</flux:heading>
            <flux:subheading>{{ __('أدر بطولات الحلقة المعرفية، خصص مفاتيح كسب النقاط، وتوج الفرسان!') }}
            </flux:subheading>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus"
            class="bg-amber-500 hover:bg-amber-600 border-none text-amber-950 shadow-md shadow-amber-500/20">
            {{ __('إنشاء مسابقة جديدة') }}
        </flux:button>
    </div>

    <!-- Leaderboards Grid -->
    @if (count($leaderboards) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($leaderboards as $board)
                <flux:card
                    class="relative overflow-hidden group hover:shadow-lg transition-[shadow,transform] duration-300 border border-zinc-200 dark:border-zinc-700/50">
                    <div class="absolute top-0 right-0 p-4">
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200" />
                            <flux:menu>
                                <flux:menu.item wire:click="edit({{ $board->id }})" icon="pencil-square">
                                    {{ __('تعديل') }}
                                </flux:menu.item>
                                <flux:menu.item href="{{ route('teacher.leaderboards.grade', $board->id) }}"
                                    icon="clipboard-document-check" target="_blank">{{ __('رصد النقاط اليدوية') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item wire:click="deleteLeaderboard({{ $board->id }})" icon="trash"
                                    class="text-rose-500 hover:text-rose-600">{{ __('حذف') }}</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    <div class="mb-4">
                        <flux:badge color="{{ $board->is_active ? 'amber' : 'zinc' }}" size="sm" class="mb-2">
                            {{ $board->is_active ? __('نشطة') : __('مغلقة') }}
                        </flux:badge>
                        <flux:heading size="lg" class="mb-1 text-zinc-800 dark:text-zinc-100">{{ $board->title }}
                        </flux:heading>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-2">
                            <flux:icon icon="calendar" class="size-4" />
                            <span>{{ $board->start_date->format('Y-m-d') }}</span>
                            @if ($board->end_date)
                                <span>-</span>
                                <span>{{ $board->end_date->format('Y-m-d') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3 mt-4">
                        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3 text-sm">
                            <div class="flex justify-between items-center text-zinc-600 dark:text-zinc-400">
                                <span class="flex items-center gap-2">
                                    <flux:icon icon="star" class="size-4 text-emerald-500" />
                                    {{ __('بنود التقييم المخصصة') }}
                                </span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $board->criteria_count }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <flux:button wire:click="toggleActive({{ $board->id }})"
                                    variant="{{ $board->is_active ? 'ghost' : 'ghost' }}"
                                    class=" border border-zinc-200 dark:border-zinc-700">
                                    @if ($board->is_active)
                                        {{ __('إيقاف') }}
                                        <flux:icon icon="pause-circle" class="size-4 ml-1" />
                                    @else
                                        {{ __('تنشيط') }}
                                        <flux:icon icon="play-circle" class="size-4 ml-1 text-emerald-500" />
                                    @endif
                                </flux:button>
                                <flux:button href="{{ route('teacher.leaderboards.report', $board->id) }}" variant="ghost"
                                    class="border flex-1 border-emerald-200 text-emerald-600 hover:bg-emerald-50 dark:border-emerald-900/50 dark:text-emerald-400 dark:hover:bg-emerald-900/20"
                                    title="{{ __('التقرير الشامل') }}">
                                    <span class="text-muted dark:text-white">{{ __('التقرير الشامل') }}</span>
                                    <flux:icon icon="chart-bar" class="size-4" />
                                </flux:button>
                            </div>
                            <div class="flex gap-3">
                                <flux:button href="{{ route('teacher.leaderboards.grade', $board->id) }}" variant="primary"
                                    class="flex-1 bg-amber-500 hover:bg-amber-600 border-none text-amber-950 shadow-md shadow-amber-500/20"
                                    title="{{ __('رصد النقاط اليدوية') }}">
                                    <span>{{ __('رصد النقاط اليدوية') }}</span>
                                    <flux:icon icon="clipboard-document-check" class="size-4" />
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @else
        <div
            class="text-center py-16 bg-zinc-50 dark:bg-zinc-800/20 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
            <div
                class="bg-amber-100 dark:bg-amber-900/30 text-amber-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <flux:icon icon="trophy" class="size-8" />
            </div>
            <flux:heading size="lg" class="mb-2">{{ __('لا توجد مسابقات حالية') }}</flux:heading>
            <p class="text-zinc-500 mb-6 max-w-md mx-auto">
                {{ __('ابدأ بصناعة جو تنافسي ممتع لطلابك! قم بإنشاء مسابقة وتخصيص نقاط للحفظ السليم والحضور المنضبط.') }}
            </p>
            <flux:button wire:click="create" variant="primary" icon="plus"
                class="bg-amber-500 hover:bg-amber-600 border-none text-white shadow-md shadow-amber-500/20">
                {{ __('إنشاء أول مسابقة') }}
            </flux:button>
        </div>
    @endif

    <!-- Leaderboard Modal Form -->
    <flux:modal wire:model="showModal" class="md:w-[800px] w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditing ? __('تعديل مسابقة') : __('إنشاء مسابقة جديدة') }}
                </flux:heading>
                <flux:subheading>{{ __('قم بتحديد اسم المنافسة وتواريخها واضبط وزن النقاط بدقة.') }}</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="space-y-4">
                    <flux:input wire:model="title" label="{{ __('اسم المسابقة') }}"
                        placeholder="{{ __('مثال: نجوم التحفيظ لشهر شوال') }}" />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <livewire:shared.hijri-datepicker wire:model="start_date" label="{{ __('تاريخ البداية') }}" />
                        <livewire:shared.hijri-datepicker wire:model="end_date" label="{{ __('تاريخ النهاية') }}" />
                    </div>

                    <flux:switch wire:model="is_active" label="{{ __('حالة اللوحة') }}"
                        description="{{ __('عند تفعيلها ستصبح متاحة في متصدرين الطلاب') }}" />
                </div>

                <!-- Custom Criteria List -->
                <div
                    class="space-y-4 p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-100 dark:border-zinc-700/50 h-[300px] overflow-y-auto">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="sm">{{ __('بنود التقييم اليدوية') }}</flux:heading>
                        <flux:button wire:click="addCriterion" size="xs" variant="ghost" icon="plus"
                            class="text-emerald-600 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50">
                            {{ __('إضافة بند') }}
                        </flux:button>
                    </div>

                    @foreach ($criteria as $index => $criterion)
                        <div class="flex items-center gap-2" wire:key="criterion-{{ $index }}">
                            <div class="flex-1">
                                <flux:input wire:model="criteria.{{ $index }}.name"
                                    placeholder="{{ __('اسم البند مثال: الهدوء') }}" />
                            </div>
                            <div class="w-24">
                                <flux:input type="number" wire:model="criteria.{{ $index }}.points"
                                    placeholder="{{ __('النقاط') }}" />
                            </div>
                            <flux:button wire:click="removeCriterion({{ $index }})" variant="ghost" icon="trash"
                                class="text-rose-500 shrink-0 mt-8" />
                        </div>
                    @endforeach

                    @if (count($criteria) === 0)
                        <div class="text-center py-6 text-sm text-zinc-400">
                            {{ __('لم تقم بإضافة بنود يدوية بعد.') }}
                        </div>
                    @endif
                </div>
            </div>

            <flux:separator />

            <!-- Automated Settings -->
            <div>
                <flux:heading size="md" class="mb-4 flex items-center gap-2">
                    <flux:icon icon="cog-6-tooth" class="size-5 text-indigo-500" />
                    {{ __('إعدادات النقاط التلقائية') }}
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Hifz -->
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <flux:switch wire:model="hifz_enabled" label="{{ __('نقاط الحفظ') }}" />
                        <div x-show="$wire.hifz_enabled"
                            class="space-y-2 mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:input type="number" size="sm" wire:model="hifz_excellent"
                                label="{{ __('تقييم ممتاز') }}" />
                            <flux:input type="number" size="sm" wire:model="hifz_good" label="{{ __('تقييم جيد') }}" />
                            <flux:input type="number" size="sm" wire:model="hifz_acceptable"
                                label="{{ __('تقييم مقبول') }}" />
                        </div>
                    </div>

                    <!-- Review -->
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <flux:switch wire:model="review_enabled" label="{{ __('نقاط المراجعة') }}" />
                        <div x-show="$wire.review_enabled"
                            class="space-y-2 mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:input type="number" size="sm" wire:model="review_excellent"
                                label="{{ __('تقييم ممتاز') }}" />
                            <flux:input type="number" size="sm" wire:model="review_good"
                                label="{{ __('تقييم جيد وجيد جدا') }}" />
                        </div>
                    </div>

                    <!-- Attendance -->
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <flux:switch wire:model="attendance_enabled" label="{{ __('نقاط الحضور') }}" />
                        <div x-show="$wire.attendance_enabled"
                            class="space-y-2 mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:input type="number" size="sm" wire:model="attendance_present"
                                label="{{ __('حاضر بوقت') }}" />
                            <flux:input type="number" size="sm" wire:model="attendance_late"
                                label="{{ __('حاضر متأخر') }}" />
                        </div>
                    </div>
                    
                    <!-- Extra Points -->
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <flux:switch wire:model="extra_points_enabled" label="{{ __('السماح بالنقاط الإضافية (يدوياً)') }}" description="{{ __('تمكين المعلم من منح نقاط إضافية للطلاب مع كتابة ملاحظة.') }}" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">{{ __('إلغاء') }}</flux:button>
                <flux:button wire:click="save" variant="primary"
                    class="bg-amber-500 hover:bg-amber-600 border-none text-white">{{ __('حفظ التغييرات') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>