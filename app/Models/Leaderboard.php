<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'settings' => 'json',
    ];

    public function circle()
    {
        return $this->belongsTo(Circle::class);
    }

    public function criteria()
    {
        return $this->hasMany(LeaderboardCriterion::class);
    }

    public function scores()
    {
        return $this->hasMany(LeaderboardScore::class);
    }
}
