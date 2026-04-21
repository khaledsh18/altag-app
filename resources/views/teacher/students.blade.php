<x-layouts.role-shell>
    <x-slot:title>
        {{ __('إدارة الطلاب') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('teacher.sidebar-nav')
    </x-slot:sidebar>

    <livewire:teacher.student-manager />
</x-layouts.role-shell>
