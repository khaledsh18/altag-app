<div class="fixed bottom-0 left-0 rounded-full right-0 z-[100] lg:hidden bg-maroon dark:bg-accent-dark border-t border-white/10 shadow-none"
    style="padding-bottom: env(safe-area-inset-bottom, 12px);">
    <div class="flex items-center justify-around px-2 min-h-18 max-w-lg mx-auto ">

        @php
            $navItems = [
                [
                    'name' => 'التسميع',
                    'route' => 'teacher.tasmeeh',
                    'icon' => 'book-open'
                ],
                [
                    'name' => 'البنود',
                    'route' => 'teacher.leaderboards',
                    'icon' => 'star'
                ],
                [
                    'name' => 'التحضير',
                    'route' => 'teacher.attendance',
                    'icon' => 'calendar'
                ],
                [
                    'name' => 'الخطط',
                    'route' => 'teacher.plan-creator',
                    'icon' => 'pencil-square'
                ],
                [
                    'name' => 'الطلاب',
                    'route' => 'teacher.students',
                    'icon' => 'users'
                ],
            ];
        @endphp

        @foreach($navItems as $item)
            @php
                $isActive = request()->routeIs($item['route'] . '*');
            @endphp
            <a href="{{ route($item['route']) }}" wire:navigate
                class="relative  flex flex-col items-center justify-center transition-all duration-300 ease-out h-full {{ $isActive ? 'text-white' : 'text-white/60 hover:text-white' }} flex-1">

                <div
                    class="relative flex items-center justify-center min-h-15 rounded-full transition-all duration-300 {{ $isActive ? 'bg-white/15 px-6 py-2' : 'p-2' }}">
                    <flux:icon icon="{{ $item['icon'] }}" class="size-7 shrink-0"
                        variant="{{ $isActive ? 'solid' : 'outline' }}" />
                    @if($isActive)
                        <span class="ms-2  font-bold text-sm truncate block">{{ $item['name'] }}</span>
                    @endif
                </div>

            </a>
        @endforeach
    </div>
</div>