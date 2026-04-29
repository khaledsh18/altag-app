<?php

namespace App\Models;

use App\Models\Concerns\HasProfile;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

class Student extends Authenticatable
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, HasProfile, Notifiable, TwoFactorAuthenticatable;

    /** @return BelongsTo<Circle, $this> */
    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    /** @return BelongsTo<Guardian, $this> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return HasMany<StudentPlan, $this> */
    public function plans(): HasMany
    {
        return $this->hasMany(StudentPlan::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_approved',
        'approved_by',
        'circle_id',
        'guardian_id',
        'access_token',
        'is_data_completed',
        'birth_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    public function getAbsencesInPeriodCount($date = null): int
    {
        $date = $date ? Carbon::parse($date) : now();
        $days = (int) Setting::getVal('calculation_period_days', 30);

        return $this->attendances()
            ->where('status', 'absent')
            ->whereBetween('date', [$date->copy()->subDays($days), $date])
            ->count();
    }

    public function getLatenessInPeriodCount($date = null): int
    {
        $date = $date ? Carbon::parse($date) : now();
        $days = (int) Setting::getVal('calculation_period_days', 30);

        return $this->attendances()
            ->where('status', 'late')
            ->whereBetween('date', [$date->copy()->subDays($days), $date])
            ->count();
    }

    public function hasExceededAbsenceLimit($date = null): bool
    {
        $limit = (int) Setting::getVal('absence_limit', 3);

        return $this->getAbsencesInPeriodCount($date) >= $limit;
    }

    public function hasExceededLatenessLimit($date = null): bool
    {
        $limit = (int) Setting::getVal('lateness_limit', 5);

        return $this->getLatenessInPeriodCount($date) >= $limit;
    }

    public function getAbsencesInLast30DaysCount($date = null): int
    {
        return $this->getAbsencesInPeriodCount($date);
    }

    public function getGuardianPhoneAttribute()
    {
        return $this->guardian?->phone;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
        ];
    }
}
