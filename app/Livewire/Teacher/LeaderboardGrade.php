<?php

namespace App\Livewire\Teacher;

use App\Models\Leaderboard;
use App\Models\LeaderboardScore;
use App\Models\Student;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Flux\Flux;


class LeaderboardGrade extends Component
{
    public $leaderboardId;
    public $date;

    public $showExtraPointsModal = false;
    public $extraPointsStudentId = null;
    public $extraPointsAmount = 1;
    public $extraPointsNotes = '';

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

    public function openExtraPointsModal($studentId)
    {
        $this->extraPointsStudentId = $studentId;
        $this->extraPointsAmount = 1;
        $this->extraPointsNotes = '';
        $this->showExtraPointsModal = true;
    }

    public function saveExtraPoints()
    {
        $this->validate([
            'extraPointsAmount' => 'required|numeric|not_in:0',
            'extraPointsNotes' => 'required|string|max:255',
        ]);

        \Illuminate\Support\Facades\DB::table('leaderboard_extra_points')->insert([
            'leaderboard_id' => $this->leaderboardId,
            'student_id' => $this->extraPointsStudentId,
            'date' => $this->date,
            'points' => $this->extraPointsAmount,
            'notes' => $this->extraPointsNotes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->showExtraPointsModal = false;
        Flux::toast('تم حفظ النقاط الإضافية بنجاح', variant: 'success');
    }

    public function deleteExtraPoints($id)
    {
        \Illuminate\Support\Facades\DB::table('leaderboard_extra_points')->where('id', $id)->delete();
    }

    public function render()
    {
        $leaderboard = Leaderboard::with('criteria')->findOrFail($this->leaderboardId);
        $students = Student::where('circle_id', $leaderboard->circle_id)->orderBy('name')->get();

        $scores = LeaderboardScore::where('leaderboard_id', $this->leaderboardId)
            ->whereDate('date', \Carbon\Carbon::parse($this->date))
            ->get()
            ->groupBy('student_id')
            ->map(function ($studentScores) {
                return $studentScores->pluck('leaderboard_criterion_id')->toArray();
            });

        $extraPointsMap = \Illuminate\Support\Facades\DB::table('leaderboard_extra_points')
            ->where('leaderboard_id', $this->leaderboardId)
            ->whereDate('date', \Carbon\Carbon::parse($this->date))
            ->get()
            ->groupBy('student_id');

        $service = new \App\Services\LeaderboardService();
        $dailyScores = $service->getDailyScores($leaderboard, \Carbon\Carbon::parse($this->date)->format('Y-m-d'));

        return view('livewire.teacher.leaderboard-grade', [
            'leaderboard' => $leaderboard,
            'students' => $students,
            'scoresMap' => $scores,
            'extraPointsMap' => $extraPointsMap,
            'dailyScores' => $dailyScores,
        ]);
    }
}
