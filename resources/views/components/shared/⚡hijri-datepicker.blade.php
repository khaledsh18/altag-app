<?php

use Livewire\Component;
use Livewire\Attributes\Modelable;

new class extends Component {
    #[Modelable]
    public $date;

    public $label;
    public $currentViewTimestamp;
    public $open = false;

    public function mount($label = 'التاريخ (هجري)')
    {
        $this->label = $label;
        $this->date = $this->date ?: now()->format('Y-m-d');

        $cal = \IntlCalendar::createInstance('Asia/Riyadh', 'ar_SA@calendar=islamic-umalqura');
        $cal->setTime(strtotime($this->date) * 1000);
        $cal->set(\IntlCalendar::FIELD_DAY_OF_MONTH, 1);
        $this->currentViewTimestamp = $cal->getTime() / 1000;
    }

    public function getHijriFormattedDateProperty()
    {
        if (! $this->date) return '';
        $formatter = new \IntlDateFormatter(
            'ar_SA@calendar=islamic-umalqura',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            'Asia/Riyadh',
            \IntlDateFormatter::TRADITIONAL,
            'd MMMM yyyy'
        );
        return $formatter->format(strtotime($this->date));
    }

    public function previousMonth()
    {
        $cal = \IntlCalendar::createInstance('Asia/Riyadh', 'ar_SA@calendar=islamic-umalqura');
        $cal->setTime($this->currentViewTimestamp * 1000);
        $cal->add(\IntlCalendar::FIELD_MONTH, -1);
        $this->currentViewTimestamp = $cal->getTime() / 1000;
    }

    public function nextMonth()
    {
        $cal = \IntlCalendar::createInstance('Asia/Riyadh', 'ar_SA@calendar=islamic-umalqura');
        $cal->setTime($this->currentViewTimestamp * 1000);
        $cal->add(\IntlCalendar::FIELD_MONTH, 1);
        $this->currentViewTimestamp = $cal->getTime() / 1000;
    }

    public function selectDate($gregorianDate)
    {
        $this->date = $gregorianDate;
        $this->open = false;
        
        $cal = \IntlCalendar::createInstance('Asia/Riyadh', 'ar_SA@calendar=islamic-umalqura');
        $cal->setTime(strtotime($this->date) * 1000);
        $cal->set(\IntlCalendar::FIELD_DAY_OF_MONTH, 1);
        $this->currentViewTimestamp = $cal->getTime() / 1000;
    }
};
?>

<div class="relative w-full" x-data="{ open: @entangle('open') }" @click.away="open = false">
    <button @click="open = !open" type="button" class="w-full flex items-center justify-between text-right bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 shadow-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 transition-colors">
        <div class="flex flex-col text-right">
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
            <span class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $this->hijriFormattedDate ?: 'حدد التاريخ' }}
            </span>
        </div>
        <flux:icon icon="calendar" class="size-5 text-zinc-400" />
    </button>

    <div x-show="open" x-transition style="display: none;" 
         class="absolute z-[100] top-full mt-2 w-72 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden" dir="rtl">
        
        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
            <button wire:click="previousMonth" type="button" class="p-1.5 rounded-md hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-500">
                <flux:icon icon="chevron-right" class="size-5" />
            </button>
            <div class="font-bold text-zinc-800 dark:text-zinc-100 text-sm">
                {{ (new \IntlDateFormatter('ar_SA@calendar=islamic-umalqura', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, 'Asia/Riyadh', \IntlDateFormatter::TRADITIONAL, 'MMMM yyyy'))->format($currentViewTimestamp) }}
            </div>
            <button wire:click="nextMonth" type="button" class="p-1.5 rounded-md hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-500">
                <flux:icon icon="chevron-left" class="size-5" />
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 px-2 pt-3 pb-1 text-center">
            @foreach(['أحد', 'إثن', 'ثلا', 'أرب', 'خمي', 'جمعة', 'سبت'] as $day)
                <div class="text-[0.6rem] font-bold text-zinc-400 dark:text-zinc-500">{{ $day }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-1 px-2 pb-2">
            @php
                $cal = \IntlCalendar::createInstance('Asia/Riyadh', 'ar_SA@calendar=islamic-umalqura');
                $cal->setTime($currentViewTimestamp * 1000);
                $monthLength = $cal->getActualMaximum(\IntlCalendar::FIELD_DAY_OF_MONTH);
                $startDayOfWeek = $cal->get(\IntlCalendar::FIELD_DAY_OF_WEEK);
                $emptySlots = $startDayOfWeek - 1;
            @endphp

            @for ($i = 0; $i < $emptySlots; $i++)
                <div></div>
            @endfor

            @for ($i = 1; $i <= $monthLength; $i++)
                @php
                    $cal->set(\IntlCalendar::FIELD_DAY_OF_MONTH, $i);
                    $gregDate = date('Y-m-d', $cal->getTime() / 1000);
                    $isSelected = $gregDate === $date;
                    $isToday = $gregDate === date('Y-m-d');
                @endphp
                <button wire:click="selectDate('{{ $gregDate }}')" type="button"
                        class="h-8 w-8 flex items-center justify-center rounded-lg text-xs transition-colors
                        {{ $isSelected ? 'bg-indigo-500 text-white font-bold' : ($isToday ? 'border border-indigo-300 text-indigo-600' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300') }}">
                    {{ $i }}
                </button>
            @endfor
        </div>
    </div>
</div>
