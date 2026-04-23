<x-layouts.role-shell>
    <x-slot:title>
        {{ __('تفاصيل الخطة') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('student.sidebar-nav')
    </x-slot:sidebar>

    <div class="p-6 md:p-8">
        <livewire:student.show-plan :planId="request()->route('id')" />
    </div>
</x-layouts.role-shell>
