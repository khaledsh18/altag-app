<x-layouts.role-shell>
    <x-slot:title>
        {{ __('إعداد خطتي القرآنية') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('student.sidebar-nav')
    </x-slot:sidebar>

    <div class="p-6 md:p-8">
        <livewire:shared.plan-creator :edit="request('edit')" />
    </div>
</x-layouts.role-shell>
