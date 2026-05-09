<?php

namespace App\Livewire\Manager;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class WhatsappSettings extends Component
{
    public $status = 'loading';
    public $message = 'جاري التحقق من حالة الواتساب...';
    public $qrCode = null;

    public function checkStatus()
    {
        try {
            $response = Http::timeout(3)->get('http://localhost:3000/status');
            if ($response->successful()) {
                $data = $response->json();
                $this->status = $data['status'] ?? 'unknown';
                $this->message = $data['message'] ?? '';
                if (isset($data['qr_image'])) {
                    $this->qrCode = $data['qr_image'];
                } else {
                    $this->qrCode = null;
                }
            } else {
                $this->status = 'error';
                $this->message = 'لا يمكن الاتصال بخدمة الواتساب.';
            }
        } catch (\Exception $e) {
            $this->status = 'error';
            $this->message = 'تأكد من تشغيل خادم Node.js الخاص بالواتساب.';
        }
    }

    public function mount()
    {
        $this->checkStatus();
    }

    public function render()
    {
        return view('livewire.manager.whatsapp-settings');
    }
}
