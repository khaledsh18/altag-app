<x-layouts.role-shell>
    <x-slot:title>
        {{ __('لوحة تحكم المعلم') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('teacher.sidebar-nav')
    </x-slot:sidebar>

    <div id="teacher-app-shell" x-data="{
        activeTab: '{{ $initialTab ?? 'dashboard' }}',
        showStaleWarning: false,
        
        init() {
            // Listen for popstate (browser back/forward) to update tab if needed
            window.addEventListener('popstate', (e) => {
                const path = window.location.pathname;
                const match = path.match(/\/teacher\/([a-zA-Z0-9\-]+)/);
                if (match && match[1]) {
                    this.activeTab = match[1];
                } else if (path === '/teacher/dashboard') {
                    this.activeTab = 'dashboard';
                }
            });
            
            // Stale Data Timer (20 minutes)
            setTimeout(() => {
                this.showStaleWarning = true;
            }, 20 * 60 * 1000);
        }
    }" 
    x-on:switch-tab.window="
        activeTab = $event.detail.tab;
        if ($event.detail.url && window.location.pathname !== $event.detail.url) {
            history.pushState(null, '', $event.detail.url);
        }
    "
    class="relative min-h-[80vh]">

        {{-- Stale Data Warning --}}
        <div x-cloak x-show="showStaleWarning" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-amber-100 dark:bg-amber-900/90 border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 px-4 py-2 rounded-full shadow-lg flex items-center gap-3">
             <flux:icon icon="clock" class="size-5" />
             <span class="text-sm font-medium">{{ __('مر وقت على تحديث البيانات') }}</span>
             <button onclick="window.location.reload()" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-full text-xs font-bold transition-colors">
                 {{ __('تحديث الآن') }} 🔄
             </button>
        </div>

        <div x-show="activeTab === 'dashboard'" x-cloak class="p-6 md:p-8 space-y-8" dir="rtl">
            <livewire:teacher.dashboard />
            <div>
                <flux:heading size="lg" class="mb-4">{{ __('لائحة التجاوزات والانذارات') }}</flux:heading>
                <livewire:shared.exceeded-limits />
            </div>
        </div>

        <div x-show="activeTab === 'students'" x-cloak>
            <livewire:teacher.student-manager />
        </div>

        <div x-show="activeTab === 'plan-creator'" x-cloak>
            <livewire:shared.plan-creator />
        </div>

        <div x-show="activeTab === 'student-plans'" x-cloak>
            <livewire:teacher.student-plans-list />
        </div>

        <div x-show="activeTab === 'tasmeeh'" x-cloak>
            <livewire:teacher.tasmeeh-manager />
        </div>

        <div x-show="activeTab === 'pairs'" x-cloak>
            <livewire:teacher.pairs-manager />
        </div>

        <div x-show="activeTab === 'leaderboards'" x-cloak>
            <livewire:teacher.leaderboards />
        </div>

        <div x-show="activeTab === 'student-exams'" x-cloak>
            <livewire:teacher.student-exams />
        </div>

        <div x-show="activeTab === 'attendance'" x-cloak class="p-1 md:p-8">
            <livewire:teacher.attendance />
        </div>

        <div x-show="activeTab === 'discipline'" x-cloak class="p-1 md:p-8">
            <livewire:teacher.attendance-discipline />
        </div>

        <div x-show="activeTab === 'quranic-discipline'" x-cloak class="p-1 md:p-8">
            <livewire:teacher.quranic-discipline />
        </div>

        <div x-show="activeTab === 'exceeded-limits'" x-cloak>
            <livewire:shared.exceeded-limits />
        </div>
    </div>
</x-layouts.role-shell>
