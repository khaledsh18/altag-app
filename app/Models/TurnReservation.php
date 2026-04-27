<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnReservation extends Model
{
    //
    protected $fillable = [
        'turn_reservation_session_id',
        'student_id',
        'date',
        'turn_number',
    ];

    public function session()
    {
        return $this->belongsTo(TurnReservationSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
