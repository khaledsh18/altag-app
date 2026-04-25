<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\Student;
use App\Models\StudentPlanDay;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public function getStandings(Leaderboard $leaderboard)
    {
        $students = Student::where('circle_id', $leaderboard->circle_id)->get();
        if ($students->isEmpty()) {
            return collect([]);
        }

        $startDate = $leaderboard->start_date->format('Y-m-d');
        $endDate = $leaderboard->end_date ? $leaderboard->end_date->format('Y-m-d') : now()->format('Y-m-d');
        $settings = $leaderboard->settings ?? [];

        $standings = [];

        foreach ($students as $student) {
            $score = 0;

            // 1. Manual Criteria Scores
            $manualPoints = DB::table('leaderboard_scores')
                ->join('leaderboard_criteria', 'leaderboard_scores.leaderboard_criterion_id', '=', 'leaderboard_criteria.id')
                ->where('leaderboard_scores.leaderboard_id', $leaderboard->id)
                ->where('leaderboard_scores.student_id', $student->id)
                ->sum('leaderboard_criteria.points');
            $score += $manualPoints;

            // 2. Attendance Points
            if ($settings['attendance_enabled'] ?? false) {
                $attendances = Attendance::where('student_id', $student->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get();
                
                $score += $attendances->where('status', 'present')->count() * ($settings['attendance_present'] ?? 4);
                $score += $attendances->where('status', 'late')->count() * ($settings['attendance_late'] ?? 2);
            }

            // 3. Hifz & Review Points
            if (($settings['hifz_enabled'] ?? false) || ($settings['review_enabled'] ?? false)) {
                $days = StudentPlanDay::whereHas('plan', function($q) use ($student) {
                        $q->where('student_id', $student->id);
                    })
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get();

                if ($settings['hifz_enabled'] ?? false) {
                    foreach ($days as $day) {
                        $hifz = (int) $day->hifz_achievement;
                        if ($hifz === 3) $score += ($settings['hifz_excellent'] ?? 10);
                        elseif ($hifz === 2) $score += ($settings['hifz_good'] ?? 7);
                        elseif ($hifz === 1) $score += ($settings['hifz_acceptable'] ?? 4);
                    }
                }

                if ($settings['review_enabled'] ?? false) {
                    foreach ($days as $day) {
                        $review = (int) $day->review_achievement;
                        if ($review === 3) $score += ($settings['review_excellent'] ?? 5);
                        elseif ($review === 2 || $review === 1) $score += ($settings['review_good'] ?? 3); // Good/Acceptable gets good points
                    }
                }
            }

            $standings[] = [
                'student' => $student,
                'score' => $score,
            ];
        }

        // Sort descending by score
        return collect($standings)->sortByDesc('score')->values();
    }
}
