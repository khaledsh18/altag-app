<flux:sidebar.group :heading="__('Platform')" class="grid">
    <flux:sidebar.item icon="home" :href="route('supervisor.dashboard')" :current="request()->routeIs('supervisor.dashboard')" wire:navigate>
        {{ __('Dashboard') }}
    </flux:sidebar.item>
</flux:sidebar.group>

<flux:sidebar.group heading="الإشراف" class="grid">
    <flux:sidebar.item icon="circle-stack" href="#" wire:navigate>
        الحلقات المسؤولة
    </flux:sidebar.item>
    <flux:sidebar.item icon="users" href="#" wire:navigate>
        المعلمون
    </flux:sidebar.item>
    <flux:sidebar.item icon="calendar" :href="route('supervisor.academic-calendar')"
        :current="request()->routeIs('supervisor.academic-calendar')" wire:navigate>
        التقويم الأكاديمي
    </flux:sidebar.item>
    <flux:sidebar.item icon="clipboard-document-list" :href="route('supervisor.tasks')"
        :current="request()->routeIs('supervisor.tasks')" wire:navigate>
        المهام
    </flux:sidebar.item>
    <flux:sidebar.item icon="exclamation-triangle" :href="route('supervisor.exceeded-limits')"
        :current="request()->routeIs('supervisor.exceeded-limits')" wire:navigate>
        لائحة التجاوزات
    </flux:sidebar.item>
    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('supervisor.whatsapp-settings')"
        :current="request()->routeIs('supervisor.whatsapp-settings')" wire:navigate>
        إعدادات الواتساب
    </flux:sidebar.item>
</flux:sidebar.group>
