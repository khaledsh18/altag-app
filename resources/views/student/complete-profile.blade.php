<x-layouts.role-shell>
    <x-slot:title>
        {{ __('استكمال البيانات الجانبية') }}
    </x-slot:title>

    <div class="flex items-center justify-center min-h-[80vh]">
        <div class="w-full max-w-md">
            <livewire:student.complete-profile />
        </div>
    </div>
</x-layouts.role-shell>
