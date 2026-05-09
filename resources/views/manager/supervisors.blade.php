<x-layouts.role-shell>

    <x-slot:title>
        {{ __('إدارة المشرفين') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('manager.sidebar-nav')
    </x-slot:sidebar>

    <livewire:manager.supervisors />
</x-layouts.role-shell>