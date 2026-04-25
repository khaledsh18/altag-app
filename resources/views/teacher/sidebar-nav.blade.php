<flux:sidebar.group heading="التعليم" class="grid">
    <flux:sidebar.item icon="home" :href="route('teacher.dashboard')" :current="request()->routeIs('teacher.dashboard')"
        wire:navigate>
        {{ __('الرئيسية') }}
    </flux:sidebar.item>
    <flux:sidebar.group heading="{{ __('الخطط القرآنية') }}" class="mt-4">
        <flux:sidebar.item wire:navigate href="{{ route('teacher.students') }}" icon="users">
            {{ __('إدارة الطلاب') }}</flux:sidebar.item>
        <flux:sidebar.item wire:navigate href="{{ route('teacher.plan-creator') }}" icon="pencil-square">
            {{ __('إنشاء خطة طالب') }}</flux:sidebar.item>
        <flux:sidebar.item wire:navigate href="{{ route('teacher.student-plans') }}" icon="clipboard-document-list">
            {{ __('عرض الخطط المنشأة') }}</flux:sidebar.item>
        <flux:sidebar.item wire:navigate href="{{ route('teacher.tasmeeh') }}" icon="book-open">
            {{ __('التسميع والمتابعة') }}</flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group heading="{{ __('التحفيز والمنافسة') }}" class="mt-4">
        <flux:sidebar.item wire:navigate href="{{ route('teacher.leaderboards') }}" icon="trophy" :current="request()->routeIs('teacher.leaderboards*')">
            {{ __('مسابقات الحلقة') }}</flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group heading="{{ __('التحضير') }}" class="mt-4">
        <flux:sidebar.item icon="calendar" :href="route('teacher.attendance')"
            :current="request()->routeIs('teacher.attendance')" wire:navigate>
            سجل الحضور
        </flux:sidebar.item>
        <flux:sidebar.item icon="chart-bar" :href="route('teacher.discipline')"
            :current="request()->routeIs('teacher.discipline')" wire:navigate>
            الانضباط الحضوري
        </flux:sidebar.item>
        <flux:sidebar.item icon="chart-pie" :href="route('teacher.quranic-discipline')"
            :current="request()->routeIs('teacher.quranic-discipline')" wire:navigate>
            الانضباط القرآني
        </flux:sidebar.item>
        <flux:sidebar.item icon="exclamation-triangle" :href="route('teacher.exceeded-limits')"
            :current="request()->routeIs('teacher.exceeded-limits')" wire:navigate>
            لائحة التجاوزات
        </flux:sidebar.item>
    </flux:sidebar.group>
</flux:sidebar.group>