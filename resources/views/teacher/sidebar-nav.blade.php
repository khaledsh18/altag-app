<flux:sidebar.group heading="التعليم" class="grid">
    <flux:sidebar.item icon="home" 
        x-on:click.prevent="$dispatch('switch-tab', { tab: 'dashboard', url: '{{ route('teacher.dashboard') }}' })"
        x-bind:data-current="'{{ $initialTab ?? '' }}' === 'dashboard' ? 'true' : null"
        x-on:switch-tab.window="if($event.detail.tab === 'dashboard') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
        href="{{ route('teacher.dashboard') }}">
        {{ __('الرئيسية') }}
    </flux:sidebar.item>
    <flux:sidebar.group heading="{{ __('الخطط القرآنية') }}" class="mt-4">
        <flux:sidebar.item icon="users"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'students', url: '{{ route('teacher.students') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'students' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'students') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.students') }}">
            {{ __('إدارة الطلاب') }}</flux:sidebar.item>
        <flux:sidebar.item icon="pencil-square"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'plan-creator', url: '{{ route('teacher.plan-creator') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'plan-creator' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'plan-creator') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.plan-creator') }}">
            {{ __('إنشاء خطة طالب') }}</flux:sidebar.item>
        <flux:sidebar.item icon="clipboard-document-list"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'student-plans', url: '{{ route('teacher.student-plans') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'student-plans' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'student-plans') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.student-plans') }}">
            {{ __('عرض الخطط المنشأة') }}</flux:sidebar.item>
        <flux:sidebar.item icon="book-open"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'tasmeeh', url: '{{ route('teacher.tasmeeh') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'tasmeeh' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'tasmeeh') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.tasmeeh') }}">
            {{ __('التسميع والمتابعة') }}</flux:sidebar.item>
        <flux:sidebar.item icon="users"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'pairs', url: '{{ route('teacher.pairs') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'pairs' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'pairs') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.pairs') }}">
            {{ __('التسميع المتبادل') }}</flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group heading="{{ __('التحفيز والمنافسة') }}" class="mt-4">
        <flux:sidebar.item icon="trophy"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'leaderboards', url: '{{ route('teacher.leaderboards') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'leaderboards' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'leaderboards') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.leaderboards') }}">
            {{ __('مسابقات الحلقة') }}</flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group heading="{{ __('الاختبارات') }}" class="mt-4">
        <flux:sidebar.item icon="academic-cap"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'student-exams', url: '{{ route('teacher.student-exams') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'student-exams' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'student-exams') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.student-exams') }}">
            {{ __('اختبارات الطلاب') }}</flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group heading="{{ __('التحضير') }}" class="mt-4">
        <flux:sidebar.item icon="calendar"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'attendance', url: '{{ route('teacher.attendance') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'attendance' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'attendance') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.attendance') }}">
            سجل الحضور
        </flux:sidebar.item>
        <flux:sidebar.item icon="chart-bar"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'discipline', url: '{{ route('teacher.discipline') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'discipline' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'discipline') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.discipline') }}">
            الانضباط الحضوري
        </flux:sidebar.item>
        <flux:sidebar.item icon="chart-pie"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'quranic-discipline', url: '{{ route('teacher.quranic-discipline') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'quranic-discipline' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'quranic-discipline') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.quranic-discipline') }}">
            الانضباط القرآني
        </flux:sidebar.item>
        <flux:sidebar.item icon="exclamation-triangle"
            x-on:click.prevent="$dispatch('switch-tab', { tab: 'exceeded-limits', url: '{{ route('teacher.exceeded-limits') }}' })"
            x-bind:data-current="'{{ $initialTab ?? '' }}' === 'exceeded-limits' ? 'true' : null"
            x-on:switch-tab.window="if($event.detail.tab === 'exceeded-limits') $el.setAttribute('data-current', 'true'); else $el.removeAttribute('data-current');"
            href="{{ route('teacher.exceeded-limits') }}">
            لائحة التجاوزات
        </flux:sidebar.item>
    </flux:sidebar.group>
</flux:sidebar.group>