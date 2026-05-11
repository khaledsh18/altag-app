<x-layouts.role-shell>

    <x-slot:title>
        {{ __('التقويم الأكاديمي') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('manager.sidebar-nav')
    </x-slot:sidebar>

    <livewire:manager.academic-calendar />
</x-layouts.role-shell>