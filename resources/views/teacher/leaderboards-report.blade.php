<x-layouts.role-shell>
    <x-slot:title>
        {{ __('التقرير الشامل للمسابقة') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('teacher.sidebar-nav')
    </x-slot:sidebar>

    <div class="p-6 md:p-8">
        <livewire:teacher.leaderboard-report :leaderboardId="$leaderboardId" />
    </div>
</x-layouts.role-shell>
