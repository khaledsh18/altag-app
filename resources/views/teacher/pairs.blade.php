<x-layouts.role-shell>
    <x-slot:title>
        {{ __('الثنائيات (التسميع المتبادل)') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('teacher.sidebar-nav')
    </x-slot:sidebar>

    <div class="space-y-6">
        <livewire:teacher.pairs-manager />
    </div>
</x-layouts.role-shell>