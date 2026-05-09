@props(['options', 'label', 'placeholder' => 'اختر...'])

<div x-data="{
        isOpen: false,
        search: '',
        options: {{ json_encode($options) }},
        selected: @entangle($attributes->wire('model')),
        get filteredOptions() {
            if (this.search === '') return this.options;
            return this.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()));
        },
        toggle(value) {
            if (this.selected.includes(value)) {
                this.selected = this.selected.filter(v => v !== value);
            } else {
                this.selected.push(value);
            }
        },
        remove(value) {
            this.selected = this.selected.filter(v => v !== value);
        },
        get selectedLabels() {
            return this.selected.map(val => {
                const opt = this.options.find(o => o.value == val);
                return opt ? opt.label : val;
            });
        }
    }" 
    class="relative w-full"
    @click.away="isOpen = false"
>
    @if($label)
        <flux:label class="mb-2">{{ $label }}</flux:label>
    @endif

    <div 
        @click="isOpen = !isOpen" 
        class="min-h-[40px] w-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg p-2 flex flex-wrap gap-1 items-center cursor-pointer shadow-sm hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors"
    >
        <template x-if="selected.length === 0">
            <span class="text-zinc-400 text-sm px-1" x-text="'{{ $placeholder }}'"></span>
        </template>
        
        <template x-for="val in selected" :key="val">
            <span class="flex items-center gap-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-xs px-2 py-1 rounded-md font-medium border border-indigo-100 dark:border-indigo-800/50">
                <span x-text="options.find(o => o.value == val)?.label || val"></span>
                <button type="button" @click.stop="remove(val)" class="hover:text-indigo-900 dark:hover:text-indigo-200 transition-colors">
                    <flux:icon icon="x-mark" class="size-3" />
                </button>
            </span>
        </template>
    </div>

    <div 
        x-show="isOpen" 
        x-transition.opacity.duration.200ms
        class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg overflow-hidden flex flex-col"
        style="display: none;"
    >
        <div class="p-2 border-b border-zinc-100 dark:border-zinc-800">
            <input 
                type="text" 
                x-model="search" 
                placeholder="ابحث..." 
                class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-md text-sm p-2 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:text-zinc-100 placeholder-zinc-400"
            >
        </div>
        <ul class="max-h-60 overflow-y-auto p-1 text-sm">
            <template x-for="option in filteredOptions" :key="option.value">
                <li 
                    @click="toggle(option.value)"
                    class="px-3 py-2 flex items-center gap-2 cursor-pointer rounded-md transition-colors"
                    :class="selected.includes(option.value) ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                >
                    <div class="size-4 border rounded shrink-0 flex items-center justify-center transition-colors"
                         :class="selected.includes(option.value) ? 'bg-indigo-600 border-indigo-600' : 'border-zinc-300 dark:border-zinc-600'">
                        <flux:icon icon="check" class="size-3 text-white" x-show="selected.includes(option.value)" />
                    </div>
                    <span x-text="option.label"></span>
                </li>
            </template>
            <template x-if="filteredOptions.length === 0">
                <li class="px-3 py-4 text-center text-zinc-500">لا توجد نتائج مطابقة</li>
            </template>
        </ul>
    </div>
</div>
