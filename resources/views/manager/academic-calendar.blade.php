<x-layouts.role-shell>

    <x-slot:title>
        {{ __('التحليل الذكي') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('manager.sidebar-nav')
    </x-slot:sidebar>

    <livewire:manager.academic-calendar />
</x-layouts.role-shell>