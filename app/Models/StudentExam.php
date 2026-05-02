<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_level_id',
        'status',
        'date_time',
        'location',
        'notes',
        'score_percentage',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'score_percentage' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function examLevel(): BelongsTo
    {
        return $this->belongsTo(ExamLevel::class);
    }
}
