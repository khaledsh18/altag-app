<?php

namespace App\Livewire\Manager;

use App\Models\Setting;
use Flux\Flux;
use Livewire\Component;

class Settings extends Component
{
    public $absenceLimit;

    public $latenessLimit;

    public $calculationPeriodDays;

    public function mount()
    {
        $this->absenceLimit = Setting::getVal('absence_limit', 3);
        $this->latenessLimit = Setting::getVal('lateness_limit', 5);
        $this->calculationPeriodDays = Setting::getVal('calculation_period_days', 30);
    }

    public function save()
    {
        $this->validate([
            'absenceLimit' => 'required|integer|min:1',
            'latenessLimit' => 'required|integer|min:1',
            'calculationPeriodDays' => 'required|integer|min:1',
        ]);

        Setting::setVal('absence_limit', $this->absenceLimit);
        Setting::setVal('lateness_limit', $this->latenessLimit);
        Setting::setVal('calculation_period_days', $this->calculationPeriodDays);

        Flux::toast('تم حفظ الإعدادات بنجاح', variant: 'success');
    }

    public function render()
    {
        return view('livewire.manager.settings');
    }
}
