<?php

namespace App\Livewire\Teacher;

use App\Models\Leaderboard;
use App\Models\LeaderboardScore;
use App\Models\Student;
use Livewire\Component;
use Illuminate\Support\Carbon;

class LeaderboardGrade extends Component
{
    public $leaderboardId;
    public $date;

    public function mount($leaderboardId)
    {
        $this->leaderboardId = $leaderboardId;
        $this->date = now()->format('Y-m-d');
    }

    public function toggleScore($studentId, $criterionId, $points)
    {
        $score = LeaderboardScore::where('leaderboard_id', $this->leaderboardId)
            ->where('student_id', $studentId)
            ->where('leaderboard_criterion_id', $criterionId)
            ->whereDate('date', Carbon::parse($this->date))
            ->first();

        if ($score) {
            $score->delete(); // Untoggle
        } else {
            LeaderboardScore::create([
                'leaderboard_id' => $this->leaderboardId,
                'student_id' => $studentId,
                'leaderboard_criterion_id' => $criterionId,
                'date' => $this->date,
            ]);
        }
    }

    public function render()
    {
        $leaderboard = Leaderboard::with('criteria')->findOrFail($this->leaderboardId);
        $students = Student::where('circle_id', $leaderboard->circle_id)->orderBy('name')->get();
        
        $scores = LeaderboardScore::where('leaderboard_id', $this->leaderboardId)
            ->whereDate('date', Carbon::parse($this->date))
            ->get()
            ->groupBy('student_id')
            ->map(function ($studentScores) {
                return $studentScores->pluck('leaderboard_criterion_id')->toArray();
            });

        return view('livewire.teacher.leaderboard-grade', [
            'leaderboard' => $leaderboard,
            'students' => $students,
            'scoresMap' => $scores,
        ]);
    }
}
