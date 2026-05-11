<x-layouts.role-shell>
    <x-slot:title>
        إعدادات الواتساب
    </x-slot:title>

    <x-slot:sidebar>
        @include('supervisor.sidebar-nav')
    </x-slot:sidebar>

    <livewire:supervisor.whatsapp-settings />
</x-layouts.role-shell>
