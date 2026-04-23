<flux:sidebar.group :heading="__('المنصة')" class="grid">
    <flux:sidebar.item icon="home" :href="route('student.dashboard')" :current="request()->routeIs('student.dashboard')" wire:navigate>
        {{ __('الرئيسية') }}
    </flux:sidebar.item>
</flux:sidebar.group>

<flux:sidebar.group heading="التعلم والمتابعة" class="grid">
    <flux:sidebar.item icon="book-open" :href="route('student.plan')" :current="request()->routeIs('student.plan')" wire:navigate>
        مساري القرآني
    </flux:sidebar.item>
    <flux:sidebar.item icon="clipboard-document-check" :href="route('student.attendance')" :current="request()->routeIs('student.attendance')" wire:navigate>
        سجل الانضباط
    </flux:sidebar.item>
</flux:sidebar.group>
