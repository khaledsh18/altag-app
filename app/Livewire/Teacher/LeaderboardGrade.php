<?php

namespace App\Livewire\Teacher;

use App\Models\Leaderboard;
use App\Models\LeaderboardScore;
use App\Models\Student;
use App\Services\LeaderboardService;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LeaderboardGrade extends Component
{
    public $leaderboardId;

    public $date;

    // Modal state kept for backward-compat but not used in inline flow
    public $showExtraPointsModal = false;

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

    public function saveExtraPoints(int $studentId, int|float $amount, string $notes): void
    {
        $this->validate([
            'date' => 'required|date',
        ]);

        if (!$amount || !$notes) {
            return;
        }

        DB::table('leaderboard_extra_points')->insert([
            'leaderboard_id' => $this->leaderboardId,
            'student_id' => $studentId,
            'date' => $this->date,
            'points' => $amount,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Flux::toast('تم حفظ النقاط الإضافية بنجاح', variant: 'success');
    }

    public function deleteExtraPoints($id)
    {
        DB::table('leaderboard_extra_points')->where('id', $id)->delete();
    }

    public function render()
    {
        $leaderboard = Leaderboard::with('criteria', 'circles')->findOrFail($this->leaderboardId);

        // For supervisor competitions (multi-circle), load all participating circles' students
        if ($leaderboard->isSupervisorCompetition() && $leaderboard->circles->isNotEmpty()) {
            $circleIds = $leaderboard->circles->pluck('id')->toArray();
            $students = Student::whereIn('circle_id', $circleIds)
                ->where('status', 'active')
                ->orderBy('circle_id')
                ->orderBy('name')
                ->with('circle')
                ->get();
        } else {
            $students = Student::where('circle_id', $leaderboard->circle_id)->orderBy('name')->get();
        }

        $scores = LeaderboardScore::where('leaderboard_id', $this->leaderboardId)
            ->whereDate('date', \Carbon\Carbon::parse($this->date))
            ->get()
            ->groupBy('student_id')
            ->map(function ($studentScores) {
                return $studentScores->pluck('leaderboard_criterion_id')->toArray();
            });

        $extraPointsMap = DB::table('leaderboard_extra_points')
            ->where('leaderboard_id', $this->leaderboardId)
            ->whereDate('date', \Carbon\Carbon::parse($this->date))
            ->get()
            ->groupBy('student_id');

        $service = new LeaderboardService;
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
