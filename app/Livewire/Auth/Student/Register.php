<?php

namespace App\Livewire\Auth\Student;

use App\Models\Student;
use App\Rules\SaudiPhone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:students'],
            'phone' => ['required', new SaudiPhone, 'unique:students,phone'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = Student::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => SaudiPhone::format($this->phone),
            'password' => Hash::make($this->password),
            'is_approved' => false,
        ]);

        event(new Registered($user));

        Auth::guard('student')->login($user);

        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.auth.student.register')
            ->layout('layouts.auth', ['title' => 'إنشاء حساب - طالب']);
    }
}
