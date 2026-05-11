<x-layouts.role-shell>
<x-slot:title>
        {{ __('الاختبارات القرآنية') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('teacher.sidebar-nav')
    </x-slot:sidebar>

    <livewire:teacher.student-exams />
</x-layouts.role-shell>
