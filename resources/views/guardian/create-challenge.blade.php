<x-layouts.role-shell role="guardian">
    <x-slot:title>
        {{ __('إنشاء تحدي جديد') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('guardian.sidebar-nav')
    </x-slot:sidebar>
    <livewire:guardian.create-challenge :student-id="$studentId" />
</x-layouts.role-shell>