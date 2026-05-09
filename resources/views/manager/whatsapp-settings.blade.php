<x-layouts.role-shell>
    <x-slot:title>
        إعدادات الواتساب
    </x-slot:title>

    <x-slot:sidebar>
        @include('manager.sidebar-nav')
    </x-slot:sidebar>

    <livewire:manager.whatsapp-settings />
</x-layouts.role-shell>