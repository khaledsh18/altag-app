<x-layouts.role-shell>
    <x-slot:sidebar>
        @include("manager.sidebar-nav")
    </x-slot:sidebar>
    <livewire:manager.backup-browser :filename="request()->route('filename')" />
</x-layouts.role-shell>
