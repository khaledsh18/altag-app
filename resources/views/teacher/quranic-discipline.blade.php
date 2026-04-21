<x-layouts.role-shell>
    <x-slot:title>
        {{ __('الانضباط القرآني') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('teacher.sidebar-nav')
    </x-slot:sidebar>

    <div class="p-1 md:p-8">
        <livewire:teacher.quranic-discipline />
    </div>
</x-layouts.role-shell>
