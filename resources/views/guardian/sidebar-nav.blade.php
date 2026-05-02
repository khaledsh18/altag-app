<flux:sidebar.group :heading="__('Platform')" class="grid">
    <flux:sidebar.item icon="home" :href="route('guardian.dashboard')" :current="request()->routeIs('guardian.dashboard')" wire:navigate>
        {{ __('الرئيسية') }}
    </flux:sidebar.item>
    <flux:sidebar.item icon="trophy" :href="route('guardian.challenges')" :current="request()->routeIs('guardian.challenges')" wire:navigate>
        {{ __('المكافآت التحفيزية') }}
    </flux:sidebar.item>
</flux:sidebar.group>

<flux:sidebar.group heading="متابعة الأبناء" class="grid">
    @foreach(auth()->guard('guardian')->user()->students()->with('circle')->get() as $sidebarStudent)
        <flux:sidebar.item
            icon="academic-cap"
            :href="route('guardian.student', $sidebarStudent->id)"
            :current="request()->routeIs('guardian.student*') && request()->route('id') == $sidebarStudent->id"
            wire:navigate>
            {{ $sidebarStudent->name }}
        </flux:sidebar.item>
    @endforeach
</flux:sidebar.group>
