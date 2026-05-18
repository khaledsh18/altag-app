<x-layouts.role-shell>
    <x-slot:title>{{ __('لوحة تحكم المدير') }}</x-slot:title>

    <x-slot:sidebar>
        @include('manager.sidebar-nav')
    </x-slot:sidebar>

    <livewire:manager.dashboard />
</x-layouts.role-shell>
