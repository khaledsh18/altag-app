<x-layouts.role-shell>
    <x-slot:title>
        {{ __('تقرير الإنجاز القرآني') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('manager.sidebar-nav')
    </x-slot:sidebar>

    <livewire:manager.quranic-achievement-report />
</x-layouts.role-shell>