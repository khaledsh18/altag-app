<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardScore extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function leaderboard()
    {
        return $this->belongsTo(Leaderboard::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function criterion()
    {
        return $this->belongsTo(LeaderboardCriterion::class, 'leaderboard_criterion_id');
    }
}
