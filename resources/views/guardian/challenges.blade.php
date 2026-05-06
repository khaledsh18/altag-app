<x-layouts.role-shell role="guardian">
    <x-slot:title>
        {{ __('التحديات') }}
    </x-slot:title>

    <x-slot:sidebar>
        @include('guardian.sidebar-nav')
    </x-slot:sidebar>
    <livewire:guardian.challenges-manager />
</x-layouts.role-shell>