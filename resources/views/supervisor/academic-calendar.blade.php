<x-layouts.role-shell>

    <x-slot:title>
        {{ __('التقويم الأكاديمي') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('supervisor.sidebar-nav')
    </x-slot:sidebar>

    <livewire:supervisor.academic-calendar />
</x-layouts.role-shell>
