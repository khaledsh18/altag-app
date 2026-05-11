<?php

namespace App\Livewire\Supervisor;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class WhatsappSettings extends Component
{
    public $status = 'loading';

    public $message = 'جاري التحقق من حالة الواتساب...';

    public $qrCode = null;

    public string $clientId = '';

    public function mount(): void
    {
        $user = auth()->guard('supervisor')->user();
        $this->clientId = 'supervisor_'.$user->id;
        $this->checkStatus();
    }

    public function checkStatus(): void
    {
        try {
            $response = Http::timeout(5)->get("http://localhost:3000/status/{$this->clientId}");
            if ($response->successful()) {
                $data = $response->json();
                $this->status = $data['status'] ?? 'unknown';
                $this->message = $data['message'] ?? '';
                $this->qrCode = $data['qr_image'] ?? null;
            } else {
                $this->status = 'error';
                $this->message = 'لا يمكن الاتصال بخدمة الواتساب.';
            }
        } catch (\Exception $e) {
            $this->status = 'error';
            $this->message = 'تأكد من تشغيل خادم Node.js الخاص بالواتساب.';
        }
    }

    public function disconnect(): void
    {
        try {
            Http::timeout(5)->post("http://localhost:3000/disconnect/{$this->clientId}");
            $this->status = 'starting';
            $this->message = 'جاري إعادة التهيئة...';
            $this->qrCode = null;
        } catch (\Exception $e) {
            // ignore
        }
    }

    public function render()
    {
        return view('livewire.supervisor.whatsapp-settings');
    }
}
