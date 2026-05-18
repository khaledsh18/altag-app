<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-amber-50 text-amber-500 dark:bg-amber-900/30 dark:text-amber-400">
                <flux:icon icon="trophy" class="size-6" />
            </div>
            <div>
                <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-white">مسابقات المرحلة</flux:heading>
                <flux:subheading>أنشئ مسابقات تشمل حلقات متعددة وتحفّز الطلاب على التنافس</flux:subheading>
            </div>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus"
            class="bg-amber-500 hover:bg-amber-600 border-none text-amber-950 shadow-md shadow-amber-500/20">
            إنشاء مسابقة جديدة
        </flux:button>
    </div>

    {{-- Grid --}}
    @if (count($competitions) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($competitions as $competition)
                <flux:card
                    class="relative overflow-hidden group hover:shadow-lg transition-shadow duration-300 border border-zinc-200 dark:border-zinc-700/50">

                    {{-- Supervisor Badge --}}
                    <div class="absolute top-3 left-3">
                        <flux:badge size="sm" color="indigo" icon="shield-check">مسابقة المشرف</flux:badge>
                    </div>

                    {{-- Actions Menu --}}
                    <div class="absolute top-2 right-2">
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200" />
                            <flux:menu>
                                <flux:menu.item wire:click="edit({{ $competition->id }})" icon="pencil-square">
                                    تعديل
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item wire:click="delete({{ $competition->id }})"
                                    wire:confirm="هل أنت متأكد من حذف هذه المسابقة؟" icon="trash"
                                    class="text-rose-500 hover:text-rose-600">
                                    حذف
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    <div class="mt-6 mb-4">
                        <flux:badge color="{{ $competition->is_active ? 'amber' : 'zinc' }}" size="sm" class="mb-2">
                            {{ $competition->is_active ? 'نشطة' : 'مغلقة' }}
                        </flux:badge>
                        <flux:heading size="lg" class="mb-1 text-zinc-800 dark:text-zinc-100 leading-snug">
                            {{ $competition->title }}
                        </flux:heading>
                        <div class="text-sm text-zinc-500 flex items-center gap-2">
                            <flux:icon icon="calendar" class="size-4" />
                            <span>{{ $competition->start_date->format('Y-m-d') }}</span>
                            @if($competition->end_date)
                                <span>-</span>
                                <span>{{ $competition->end_date->format('Y-m-d') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Circles --}}
                    <div class="mb-4">
                        <div class="text-xs text-zinc-500 mb-1.5 font-medium">الحلقات المشاركة</div>
                        <div class="flex flex-wrap gap-1">
                            @forelse($competition->circles as $circle)
                                <flux:badge size="sm" variant="neutral">{{ $circle->name }}</flux:badge>
                            @empty
                                <span class="text-xs text-zinc-400">لم تُحدَّد حلقات</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3 text-sm mb-4">
                        <div class="flex justify-between items-center text-zinc-600 dark:text-zinc-400">
                            <span class="flex items-center gap-2">
                                <flux:icon icon="star" class="size-4 text-emerald-500" />
                                بنود التقييم المخصصة
                            </span>
                            <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $competition->criteria_count }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="grid grid-cols-2 gap-2 mt-4">
                        <flux:button wire:click="toggleActive({{ $competition->id }})" variant="ghost" size="sm"
                            class="w-full border border-zinc-200 dark:border-zinc-700">
                            @if ($competition->is_active)
                                <flux:icon icon="pause-circle" class="size-4 ml-1" /> إيقاف
                            @else
                                <flux:icon icon="play-circle" class="size-4 ml-1 text-emerald-500" /> تنشيط
                            @endif
                        </flux:button>
                        
                        <flux:button wire:click="toggleActiveForGrading({{ $competition->id }})" variant="ghost" size="sm"
                            class="w-full border {{ $competition->is_active_for_grading ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 border-amber-300 dark:border-amber-700' : 'border-zinc-200 dark:border-zinc-700' }}">
                            @if ($competition->is_active_for_grading)
                                <flux:icon icon="star" variant="solid" class="size-4 ml-1 text-amber-500" /> أساسية للتسجيل
                            @else
                                <flux:icon icon="star" variant="outline" class="size-4 ml-1 text-zinc-400" /> تعيين للتسجيل
                            @endif
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @else
        <div
            class="text-center py-20 bg-zinc-50 dark:bg-zinc-800/20 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
            <div
                class="bg-amber-100 dark:bg-amber-900/30 text-amber-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <flux:icon icon="trophy" class="size-8" />
            </div>
            <flux:heading size="lg" class="mb-2">لا توجد مسابقات حالية</flux:heading>
            <p class="text-zinc-500 mb-6 max-w-md mx-auto">أنشئ مسابقة تشمل حلقات متعددة لتحفيز الطلاب على التنافس عبر
                المرحلة.</p>
            <flux:button wire:click="create" variant="primary" icon="plus"
                class="bg-amber-500 hover:bg-amber-600 border-none text-white shadow-md shadow-amber-500/20">
                إنشاء أول مسابقة
            </flux:button>
        </div>
    @endif

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="md:w-[800px] w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditing ? 'تعديل المسابقة' : 'إنشاء مسابقة جديدة' }}</flux:heading>
                <flux:subheading>حدد اسم المسابقة والحلقات المشاركة وإعدادات النقاط.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Basic Info --}}
                <div class="space-y-4">
                    <flux:input wire:model="title" label="اسم المسابقة" placeholder="مثال: نجوم التحفيظ لشهر شوال"
                        required />

                    <div class="grid grid-cols-2 gap-4">
                        <livewire:shared.hijri-datepicker wire:model="start_date" label="تاريخ البداية" />
                        <livewire:shared.hijri-datepicker wire:model="end_date" label="تاريخ النهاية" />
                    </div>

                    <flux:switch wire:model="is_active" label="المسابقة نشطة"
                        description="المسابقات النشطة تظهر بأولوية في صفحة الطلاب" />

                    {{-- Circles selection --}}
                    <div>
                        <flux:heading size="sm" class="mb-2">الحلقات المشاركة</flux:heading>
                        @error('selectedCircles')
                            <div class="text-sm text-red-500 mb-2">{{ $message }}</div>
                        @enderror
                        <div
                            class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            @forelse($circlesList as $circle)
                                <label
                                    class="flex items-start gap-2 cursor-pointer p-1.5 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                    <flux:checkbox wire:model="selectedCircles" :value="$circle->id"
                                        :id="'circle-'.$circle->id" />
                                    <div>
                                        <div class="text-sm font-medium">{{ $circle->name }}</div>
                                        <div class="text-xs text-zinc-400">{{ $circle->stage->name }}</div>
                                    </div>
                                </label>
                            @empty
                                <span class="text-xs text-zinc-400 col-span-2 text-center py-2">لا توجد حلقات متاحة</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Custom Criteria --}}
                <div
                    class="space-y-4 p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-100 dark:border-zinc-700/50 h-[300px] overflow-y-auto">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="sm">بنود التقييم اليدوية</flux:heading>
                        <flux:button wire:click="addCriterion" size="xs" variant="ghost" icon="plus"
                            class="text-emerald-600 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/30">
                            إضافة بند
                        </flux:button>
                    </div>

                    @foreach ($criteria as $index => $criterion)
                        <div class="flex items-center gap-2" wire:key="criterion-{{ $index }}">
                            <flux:input wire:model="criteria.{{ $index }}.name" class="flex-2"
                                placeholder="اسم البند مثال: الهدوء" />
                            <flux:input type="number" wire:model="criteria.{{ $index }}.points" class="flex-1"
                                placeholder="نقاط" />
                            <flux:button wire:click="removeCriterion({{ $index }})" variant="ghost" icon="trash"
                                class="text-rose-500 shrink-0" />
                        </div>
                    @endforeach

                    @if (count($criteria) === 0)
                        <div class="text-center py-6 text-sm text-zinc-400">لم تقم بإضافة بنود يدوية بعد.</div>
                    @endif
                </div>
            </div>

            <flux:separator />

            {{-- Automated Settings --}}
            <div>
                <flux:heading size="md" class="mb-4 flex items-center gap-2">
                    <flux:icon icon="cog-6-tooth" class="size-5 text-indigo-500" />
                    إعدادات النقاط التلقائية
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <flux:switch wire:model="hifz_enabled" label="نقاط الحفظ" />
                        <div x-show="$wire.hifz_enabled"
                            class="space-y-2 mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:input type="number" size="sm" wire:model="hifz_excellent" label="تقييم ممتاز" />
                            <flux:input type="number" size="sm" wire:model="hifz_good" label="تقييم جيد" />
                            <flux:input type="number" size="sm" wire:model="hifz_acceptable" label="تقييم مقبول" />
                        </div>
                    </div>
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <flux:switch wire:model="review_enabled" label="نقاط المراجعة" />
                        <div x-show="$wire.review_enabled"
                            class="space-y-2 mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:input type="number" size="sm" wire:model="review_excellent" label="تقييم ممتاز" />
                            <flux:input type="number" size="sm" wire:model="review_good" label="تقييم جيد وجيد جدا" />
                        </div>
                    </div>
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <flux:switch wire:model="attendance_enabled" label="نقاط الحضور" />
                        <div x-show="$wire.attendance_enabled"
                            class="space-y-2 mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:input type="number" size="sm" wire:model="attendance_present" label="حاضر بوقت" />
                            <flux:input type="number" size="sm" wire:model="attendance_late" label="حاضر متأخر" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">إلغاء</flux:button>
                <flux:button wire:click="save" variant="primary"
                    class="bg-amber-500 hover:bg-amber-600 border-none text-white">
                    {{ $isEditing ? 'حفظ التعديلات' : 'إنشاء المسابقة' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>