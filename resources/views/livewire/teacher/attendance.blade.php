{{--
Alpine owns all pure-UI state:
mode — wizard | list (no round-trip to switch)
currentIndex — wizard navigation (no round-trip)
isComplete — computed from records keys
markedCount — computed from records values
records — @entangle so Alpine visual = Livewire DB state
studentOrder — @entangle ordered IDs for navigation

Livewire fires only on: markStatus | updateStatus | markAllPresent | loadStudents
--}}
<div class="space-y-6" dir="rtl" x-data="{
         mode: 'list',
         currentIndex: 0,
         records: @entangle('records'),
         studentOrder: @entangle('studentOrder'),
         syncing: [],

         init() {
             /* After loadStudents() — reset navigation */
             $wire.on('studentsLoaded', () => {
                 this.currentIndex = 0;
                 this.mode         = 'list';
             });
         },

         /* ── Computed ──────────────────────────────────────────── */
         get markedCount() {
             return Object.values(this.records).filter(v => v && v !== '').length;
         },

         get isComplete() {
             if (!this.studentOrder.length) return false;
             return this.studentOrder.every(id => this.records[id] && this.records[id] !== '');
         },

         getStatus(studentId) {
             return this.records[studentId] || '';
         },

         /* ── Wizard ─────────────────────────────────────────────── */
         /**
          * Immediately update Alpine state + navigate,
          * then fire the Livewire save in the background (no await = non-blocking).
          */
         async markAndAdvance(studentId, status) {
             this.records[studentId] = status;   /* instant visual feedback */
             this.syncing.push(studentId);       /* purple mode */
             this.moveToNextUnmarked();
             
             await $wire.markStatus(studentId, status); /* async DB save */
             
             this.syncing = this.syncing.filter(id => id !== studentId);
         },
         
         async updateRecord(studentId, status) {
             this.records[studentId] = status;
             this.syncing.push(studentId);
             
             await $wire.updateStatus(studentId, status);
             
             this.syncing = this.syncing.filter(id => id !== studentId);
         },

         moveToNextUnmarked() {
             const total = this.studentOrder.length;
             for (let i = this.currentIndex + 1; i < total; i++) {
                 if (!this.records[this.studentOrder[i]]) { this.currentIndex = i; return; }
             }
             /* wrap around */
             for (let i = 0; i <= this.currentIndex; i++) {
                 if (!this.records[this.studentOrder[i]]) { this.currentIndex = i; return; }
             }
             /* all marked — isComplete will be true via computed */
         },

         goToPrevious() { if (this.currentIndex > 0) this.currentIndex--; },
         goToNext() {
             if (this.currentIndex < this.studentOrder.length - 1) this.currentIndex++;
         },
     }">

    {{-- ══════════════════ HEADER ══════════════════ --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-zinc-50 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                <flux:icon icon="clipboard-document-check" />
            </div>
            <div>
                <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-white">سجل الحضور والغياب
                </flux:heading>
                <flux:subheading>تسجيل حضور الطلاب اليومي في حلقاتك</flux:subheading>
            </div>
        </div>
    </div>

    {{-- ══════════════════ CONTROLS ══════════════════ --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs p-5">
        <div class="flex flex-col md:flex-row gap-4 items-end max-md:items-center">

            {{-- Circle picker (server-side, only changes on DB load) --}}
            @if ($circles->count() > 1)
                <div class="w-full md:w-64">
                    <flux:select wire:model.live="selectedCircle" label="اختر الحلقة">
                        <flux:select.option value="">-- اختر حلقة --</flux:select.option>
                        @foreach ($circles as $circle)
                            <flux:select.option :value="$circle->id">{{ $circle->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @elseif ($circles->count() === 1)
                <div class="flex items-center gap-2 px-3 py-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:icon icon="users" class="size-4 text-zinc-500" />
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $circles->first()->name }}</span>
                </div>
            @endif

            {{-- Date picker --}}
            <div class="w-full md:w-48 mt-auto relative z-50">
                @if($selectedCircle)
                    <livewire:teacher.hijri-datepicker wire:model.live="date" :circle-id="$selectedCircle"
                        wire:key="datepicker-{{ $selectedCircle }}" />
                @else
                    <flux:input type="date" wire:model.live="date" label="التاريخ" disabled />
                @endif
            </div>

            {{-- Mode toggle — Alpine only, zero round-trips --}}
            <div class="flex items-center gap-1 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg h-fit">
                <button @click="mode = 'wizard'" :class="mode === 'wizard'
                            ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm'
                            : 'text-zinc-500 dark:text-zinc-400'" class="px-3 py-1.5 text-sm font-medium rounded-md ">
                    <span class="flex items-center gap-1.5">
                        <flux:icon icon="play" class="size-4" />
                        تحضير تفاعلي
                    </span>
                </button>
                <button @click="mode = 'list'" :class="mode === 'list'
                            ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm'
                            : 'text-zinc-500 dark:text-zinc-400'" class="px-3 py-1.5 text-sm font-medium rounded-md ">
                    <span class="flex items-center gap-1.5">
                        <flux:icon icon="list-bullet" class="size-4" />
                        قائمة يدوية
                    </span>
                </button>
            </div>

            {{-- Mark all — Alpine hides/shows, Livewire executes --}}
            <flux:button x-show="studentOrder.length > 0 && !isComplete" wire:click="markAllPresent" size="sm">
                <span class="flex items-center gap-1">
                    <flux:icon icon="check-circle" class="size-4" />
                    تحضير الكل
                </span>
            </flux:button>
        </div>

        {{-- Progress bar — Alpine computed, no round-trip --}}
        <div x-show="studentOrder.length > 0" class="mt-4">
            <div class="flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400 mb-1.5">
                <span>التقدم:
                    <span x-text="markedCount"></span> /
                    <span x-text="studentOrder.length"></span>
                </span>
                <span x-show="isComplete"
                    class="text-green-600 dark:text-green-400 font-medium flex items-center gap-1">
                    <flux:icon icon="check-circle" class="size-4" />
                    مكتمل
                </span>
            </div>
            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                <div class="h-2 rounded-full  duration-500 ease-out"
                    :class="isComplete ? 'bg-green-500' : 'bg-blue-500'"
                    :style="{ width: (studentOrder.length > 0 ? (markedCount / studentOrder.length) * 100 : 0) + '%' }">
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ MAIN CONTENT ══════════════════ --}}

    @if (!$selectedCircle)
        {{-- No circle selected --}}
        <div
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs p-16 text-center">
            <flux:icon icon="cursor-arrow-ripple" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="text-zinc-500 dark:text-zinc-400">اختر حلقة للبدء</flux:heading>
            <flux:subheading class="text-zinc-400 dark:text-zinc-500">حدد الحلقة التي تريد تسجيل حضور طلابها
            </flux:subheading>
        </div>

    @elseif($students->count() === 0)
        {{-- No students --}}
        <div
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs p-16 text-center">
            <flux:icon icon="users" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="text-zinc-500 dark:text-zinc-400">لا يوجد طلاب</flux:heading>
            <flux:subheading class="text-zinc-400 dark:text-zinc-500">لا يوجد طلاب معتمدون في هذه الحلقة</flux:subheading>
        </div>

    @else
        {{-- ────────── WIZARD MODE ────────── --}}
        <div x-show="mode === 'wizard'"
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs overflow-hidden">

            {{-- Completion screen --}}
            <div x-show="isComplete" class="p-16 text-center space-y-4">
                <div class="inline-flex p-4 rounded-full bg-green-50 dark:bg-green-900/20 mb-2">
                    <flux:icon icon="check-circle" class="size-16 text-green-500" />
                </div>
                <flux:heading size="xl" class="text-green-600 dark:text-green-400">تم التحضير بنجاح! 🎉</flux:heading>
                <flux:subheading class="text-zinc-500 dark:text-zinc-400">تم تسجيل حضور جميع الطلاب لهذا اليوم
                </flux:subheading>
                <div class="pt-4">
                    <flux:button @click="mode = 'list'" variant="primary"
                        class="bg-zinc-800 hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-800 dark:hover:bg-zinc-300">
                        عرض القائمة للمراجعة
                    </flux:button>
                </div>
            </div>

            {{-- Student cards — one per student, Alpine shows the current --}}
            @foreach($students as $index => $student)
                <div x-show="!isComplete && currentIndex === {{ $index }}" wire:key="wizard-{{ $student->id }}"
                    class="p-8 md:p-12 text-center space-y-8">

                    {{-- Counter + name --}}
                    <div class="space-y-2">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-full text-sm text-zinc-500 dark:text-zinc-400">
                            <span>{{ $index + 1 }} من {{ count($students) }}</span>
                        </div>
                        <div class="pt-4">
                            <div class="inline-flex p-5 rounded-full bg-zinc-50 dark:bg-zinc-800 mb-4">
                                <flux:icon icon="user" class="size-12 text-zinc-400 dark:text-zinc-500" />
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white">{{ $student->name }}</h2>
                            @if ($student->circle)
                                <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">{{ $student->circle->name }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Status buttons — Alpine visual, Livewire saves in background --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 max-w-2xl mx-auto">
                        <button @click="markAndAdvance({{ $student->id }}, 'present')"
                            :disabled="syncing.includes({{ $student->id }})"
                            :class="syncing.includes({{ $student->id }}) && getStatus({{ $student->id }}) === 'present'
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400'
                                : (getStatus({{ $student->id }}) === 'present'
                                    ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-gray-800 dark:text-white'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-green-400 hover:bg-green-50 dark:hover:border-green-600 dark:hover:bg-green-900/10 text-gray-800 dark:text-white')"
                            class="group flex flex-col items-center justify-center gap-3 p-6 rounded-2xl border-2 duration-200">
                            <span class="font-semibold">حاضر</span>
                        </button>
                        <button @click="markAndAdvance({{ $student->id }}, 'absent')"
                            :disabled="syncing.includes({{ $student->id }})"
                            :class="syncing.includes({{ $student->id }}) && getStatus({{ $student->id }}) === 'absent'
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400'
                                : (getStatus({{ $student->id }}) === 'absent'
                                    ? 'border-red-500 bg-red-50 dark:bg-red-900/20 text-gray-800 dark:text-white'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-red-400 hover:bg-red-50 dark:hover:border-red-600 dark:hover:bg-red-900/10 text-gray-800 dark:text-white')"
                            class="group flex flex-col items-center justify-center gap-3 p-6 rounded-2xl border-2 duration-200">
                            <span class="font-semibold">غائب</span>
                        </button>
                        <button @click="markAndAdvance({{ $student->id }}, 'late')"
                            :disabled="syncing.includes({{ $student->id }})"
                            :class="syncing.includes({{ $student->id }}) && getStatus({{ $student->id }}) === 'late'
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400'
                                : (getStatus({{ $student->id }}) === 'late'
                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 text-gray-800 dark:text-white'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-amber-400 hover:bg-amber-50 dark:hover:border-amber-600 dark:hover:bg-amber-900/10 text-gray-800 dark:text-white')"
                            class="group flex flex-col items-center justify-center gap-3 p-6 rounded-2xl border-2 duration-200">
                            <span class="font-semibold">متأخر</span>
                        </button>
                        <button @click="markAndAdvance({{ $student->id }}, 'excused')"
                            :disabled="syncing.includes({{ $student->id }})"
                            :class="syncing.includes({{ $student->id }}) && getStatus({{ $student->id }}) === 'excused'
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400'
                                : (getStatus({{ $student->id }}) === 'excused'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-gray-800 dark:text-white'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-blue-400 hover:bg-blue-50 dark:hover:border-blue-600 dark:hover:bg-blue-900/10 text-gray-800 dark:text-white')"
                            class="group flex flex-col items-center justify-center gap-3 p-6 rounded-2xl border-2 duration-200">
                            <span class="font-semibold">مستأذن</span>
                        </button>
                    </div>

                    {{-- Navigation — Alpine only, zero round-trips --}}
                    <div class="flex items-center justify-between mt-4">
                        <button @click="goToPrevious()" :disabled="currentIndex === 0"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 disabled:opacity-40 disabled:cursor-not-allowed ">
                            <flux:icon icon="chevron-right" class="size-4" />
                            السابق
                        </button>

                        {{-- Dot indicators --}}
                        <div class="flex items-center gap-1 max-w-xs overflow-hidden">
                            @foreach ($students as $idx => $s)
                                <div :class="{
                                                                                                    'bg-blue-500 scale-125': currentIndex === {{ $idx }},
                                                                                                    'bg-green-400': currentIndex !== {{ $idx }} && records[{{ $s->id }}],
                                                                                                    'bg-zinc-300 dark:bg-zinc-600': currentIndex !== {{ $idx }} && !records[{{ $s->id }}]
                                                                                                }" class="size-2 rounded-full ">
                                </div>
                            @endforeach
                        </div>

                        <button @click="goToNext()" :disabled="currentIndex >= studentOrder.length - 1"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 disabled:opacity-40 disabled:cursor-not-allowed ">
                            التالي
                            <flux:icon icon="chevron-left" class="size-4" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ────────── LIST MODE ────────── --}}
        <div x-show="mode === 'list'"
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs overflow-hidden">
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($students as $index => $student)
                    <div wire:key="student-{{ $student->id }}"
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono text-zinc-400 w-6 text-center">{{ $index + 1 }}</span>
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon icon="user" class="size-4 text-zinc-400" />
                                </div>
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $student->name }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mr-9 sm:mr-0 min-w-[32px] justify-center">
                            {{-- Loading spinner while syncing --}}
                            <div x-cloak x-show="syncing.includes({{ $student->id }})" class="flex items-center justify-center">
                                <flux:icon icon="arrow-path" class="size-5 text-purple-500 animate-spin" />
                            </div>

                            {{-- Actual buttons hide while syncing --}}
                            <div x-show="!syncing.includes({{ $student->id }})" class="flex gap-2">
                                {{-- WhatsApp link — server-rendered on updateStatus re-render --}}
                                @if (in_array($records[$student->id] ?? '', ['absent', 'late']))
                                    @php $msg = $this->getWhatsAppMessage($student, $records[$student->id]); @endphp
                                    @if ($student->guardian_phone)
                                        <a class="whatsapp-link"
                                            href="https://wa.me/{{ $student->guardian_phone }}/?text={{ urlencode($msg) }}" target="_blank"
                                            title="تواصل عبر واتساب">
                                            <flux:icon icon="chat-bubble-left-right" class="size-5 text-green-500 hover:text-green-600" />
                                        </a>
                                    @else
                                        <button x-data="{ copied: false }" data-msg="{{ $msg }}"
                                            x-on:click="navigator.clipboard.writeText($el.dataset.msg).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                            title="لا يوجد رقم - نسخ الرسالة"
                                            class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 flex items-center justify-center">
                                            <flux:icon x-show="!copied" icon="clipboard-document" class="size-5" />
                                            <flux:icon x-cloak x-show="copied" icon="check" class="size-5 text-green-500" />
                                        </button>
                                    @endif
                                @endif
                            </div>

                            {{-- Status buttons — Alpine instant color, Livewire re-renders for WhatsApp --}}
                            <button
                                @click="updateRecord({{ $student->id }}, 'present')"
                                :disabled="syncing.includes({{ $student->id }})"
                                :class="syncing.includes({{ $student->id }}) && getStatus({{ $student->id }}) === 'present'
                                    ? 'bg-purple-100 text-purple-700 border border-purple-300 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-700'
                                    : (getStatus({{ $student->id }}) === 'present'
                                        ? 'bg-green-100 text-green-700 border border-green-300 dark:bg-green-900/30 dark:text-green-400 dark:border-green-700'
                                        : 'bg-zinc-100 text-zinc-500 border border-zinc-200 hover:bg-green-50 hover:text-green-700 hover:border-green-300 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700 dark:hover:bg-green-900/20 dark:hover:text-green-400')"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg">حاضر</button>
                            <button
                                @click="updateRecord({{ $student->id }}, 'absent')"
                                :disabled="syncing.includes({{ $student->id }})"
                                :class="syncing.includes({{ $student->id }}) && getStatus({{ $student->id }}) === 'absent'
                                    ? 'bg-purple-100 text-purple-700 border border-purple-300 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-700'
                                    : (getStatus({{ $student->id }}) === 'absent'
                                        ? 'bg-red-100 text-red-700 border border-red-300 dark:bg-red-900/30 dark:text-red-400 dark:border-red-700'
                                        : 'bg-zinc-100 text-zinc-500 border border-zinc-200 hover:bg-red-50 hover:text-red-700 hover:border-red-300 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700 dark:hover:bg-red-900/20 dark:hover:text-red-400')"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg">غائب</button>
                            <button
                                @click="updateRecord({{ $student->id }}, 'late')"
                                :disabled="syncing.includes({{ $student->id }})"
                                :class="syncing.includes({{ $student->id }}) && getStatus({{ $student->id }}) === 'late'
                                    ? 'bg-purple-100 text-purple-700 border border-purple-300 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-700'
                                    : (getStatus({{ $student->id }}) === 'late'
                                        ? 'bg-amber-100 text-amber-700 border border-amber-300 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-700'
                                        : 'bg-zinc-100 text-zinc-500 border border-zinc-200 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700 dark:hover:bg-amber-900/20 dark:hover:text-amber-400')"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg">متأخر</button>
                            <button
                                @click="updateRecord({{ $student->id }}, 'excused')"
                                :disabled="syncing.includes({{ $student->id }})"
                                :class="syncing.includes({{ $student->id }}) && getStatus({{ $student->id }}) === 'excused'
                                    ? 'bg-purple-100 text-purple-700 border border-purple-300 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-700'
                                    : (getStatus({{ $student->id }}) === 'excused'
                                        ? 'bg-blue-100 text-blue-700 border border-blue-300 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-700'
                                        : 'bg-zinc-100 text-zinc-500 border border-zinc-200 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700 dark:hover:bg-blue-900/20 dark:hover:text-blue-400')"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg">مستأذن</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════════════ STATS (Alpine computed) ══════════════════ --}}
    <div x-show="isComplete && studentOrder.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div
            class="bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-200 dark:border-green-800 p-4 text-center">
            <div x-text="Object.values(records).filter(v => v === 'present').length"
                class="text-2xl font-bold text-green-700 dark:text-green-400"></div>
            <div class="text-sm text-green-600 dark:text-green-500">حاضر</div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-4 text-center">
            <div x-text="Object.values(records).filter(v => v === 'absent').length"
                class="text-2xl font-bold text-red-700 dark:text-red-400"></div>
            <div class="text-sm text-red-600 dark:text-red-500">غائب</div>
        </div>
        <div
            class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200 dark:border-amber-800 p-4 text-center">
            <div x-text="Object.values(records).filter(v => v === 'late').length"
                class="text-2xl font-bold text-amber-700 dark:text-amber-400"></div>
            <div class="text-sm text-amber-600 dark:text-amber-500">متأخر</div>
        </div>
        <div
            class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-4 text-center">
            <div x-text="Object.values(records).filter(v => v === 'excused').length"
                class="text-2xl font-bold text-blue-700 dark:text-blue-400"></div>
            <div class="text-sm text-blue-600 dark:text-blue-500">مستأذن</div>
        </div>
    </div>
</div>