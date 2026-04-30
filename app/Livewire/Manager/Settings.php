<?php

namespace App\Livewire\Manager;

use App\Models\Setting;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public $absenceLimit;
    public $latenessLimit;
    public $calculationPeriodDays;
    public $uploadedBackup;

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

    public function downloadBackup()
    {
        $dbPath = config('database.connections.sqlite.database');
        if (!file_exists($dbPath)) {
            Flux::toast('ملف قاعدة البيانات غير موجود.', variant: 'danger');
            return;
        }

        $filename = 'manual_' . now()->format('Y-m-d_H-i-s') . '.sqlite';
        return response()->download($dbPath, $filename);
    }

    public function saveBackupToServer()
    {
        $dbPath = config('database.connections.sqlite.database');
        if (!file_exists($dbPath)) {
            Flux::toast('ملف قاعدة البيانات غير موجود.', variant: 'danger');
            return;
        }

        $filename = 'manual_' . now()->format('Y-m-d_H-i-s') . '.sqlite';
        $backupDir = storage_path('app/backups');

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        copy($dbPath, $backupDir . '/' . $filename);
        Flux::toast('تم حفظ النسخة الاحتياطية على الخادم بنجاح.', variant: 'success');
    }

    public function uploadBackup()
    {
        if (!$this->uploadedBackup) {
            Flux::toast('لم يتم استلام أي ملف بعد. يرجى الانتظار قليلاً بعد اختيار الملف.', variant: 'danger');
            return;
        }
        $this->validate([
            'uploadedBackup' => 'required|file',
        ]);

        $filename = $this->uploadedBackup->getClientOriginalName();
        if (!str_ends_with($filename, '.sqlite') && !str_ends_with($filename, '.db')) {

            Flux::toast('يجب أن يكون الملف بصيغة sqlite أو db.', variant: 'danger');
            return;
        }

        $newFilename = 'uploaded_' . now()->format('Y-m-d_H-i-s') . '.sqlite';
        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir) && !str_ends_with($backupDir, '/')) {
            $backupDir .= '/';
        }
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        copy($this->uploadedBackup->getRealPath(), $backupDir . '/' . $newFilename);

        $this->uploadedBackup = null;
        Flux::toast('تم رفع النسخة الاحتياطية بنجاح.', variant: 'success');
    }

    public function downloadSpecificBackup($filename)
    {
        $path = storage_path('app/backups/' . $filename);
        if (file_exists($path)) {
            return response()->download($path);
        }
        Flux::toast('الملف غير موجود.', variant: 'danger');
    }

    public function deleteBackup($filename)
    {
        $path = storage_path('app/backups/' . $filename);
        if (file_exists($path)) {
            unlink($path);
            Flux::toast('تم حذف النسخة الاحتياطية.', variant: 'success');
        } else {
            Flux::toast('الملف غير موجود.', variant: 'danger');
        }
    }

    public function render()
    {
        $scheduledBackups = [];
        $manualBackups = [];
        $uploadedBackups = [];

        $backupDir = storage_path('app/backups');
        if (file_exists($backupDir)) {
            $files = \Illuminate\Support\Facades\File::files($backupDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'sqlite') {
                    $item = [
                        'name' => $file->getFilename(),
                        'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                        'time' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];

                    if (str_starts_with($item['name'], 'scheduled_')) {
                        $scheduledBackups[] = $item;
                    } elseif (str_starts_with($item['name'], 'uploaded_')) {
                        $uploadedBackups[] = $item;
                    } else {
                        $manualBackups[] = $item;
                    }
                }
            }
        }

        $sortFn = function ($a, $b) {
            return $b['time'] <=> $a['time'];
        };

        usort($scheduledBackups, $sortFn);
        usort($manualBackups, $sortFn);
        usort($uploadedBackups, $sortFn);

        return view('livewire.manager.settings', [
            'scheduledBackups' => $scheduledBackups,
            'manualBackups' => $manualBackups,
            'uploadedBackups' => $uploadedBackups,
        ]);
    }
}
