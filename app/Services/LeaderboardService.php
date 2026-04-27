<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\Student;
use App\Models\StudentPlanDay;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public function getDetailedStandings(Leaderboard $leaderboard)
    {
        $students = Student::where('circle_id', $leaderboard->circle_id)->get();
        if ($students->isEmpty()) {
            return collect([]);
        }

        $startDate = $leaderboard->start_date->startOfDay();
        $endDate = $leaderboard->end_date ? $leaderboard->end_date->endOfDay() : now()->endOfDay();
        $settings = $leaderboard->settings ?? [];

        $standings = [];

        foreach ($students as $student) {
            $totalScore = 0;
            $manualScore = 0;
            $attendanceScore = 0;
            $hifzScore = 0;
            $reviewScore = 0;

            // 1. Manual Criteria Scores
            $manualScoresList = DB::table('leaderboard_scores')
                ->join('leaderboard_criteria', 'leaderboard_scores.leaderboard_criterion_id', '=', 'leaderboard_criteria.id')
                ->where('leaderboard_scores.leaderboard_id', $leaderboard->id)
                ->where('leaderboard_scores.student_id', $student->id)
                ->select('leaderboard_criteria.id as criterion_id', 'leaderboard_criteria.name', 'leaderboard_criteria.points')
                ->get();
            
            $manualScore = $manualScoresList->sum('points');
            
            // 1.5 Extra Points
            $extraPointsList = DB::table('leaderboard_extra_points')
                ->where('leaderboard_id', $leaderboard->id)
                ->where('student_id', $student->id)
                ->get();
            $extraPointsScore = $extraPointsList->sum('points');
            
            $totalScore += $manualScore + $extraPointsScore;
            
            // Count occurrences per criterion
            $criteriaCounts = [];
            foreach ($manualScoresList as $ms) {
                $criteriaCounts[$ms->criterion_id] = ($criteriaCounts[$ms->criterion_id] ?? 0) + 1;
            }

            // 2. Attendance Points
            if ($settings['attendance_enabled'] ?? false) {
                $attendances = Attendance::where('student_id', $student->id)
                    ->where('date', '>=', $startDate)
                    ->where('date', '<=', $endDate)
                    ->get();
                
                $attendanceScore += $attendances->where('status', 'present')->count() * ($settings['attendance_present'] ?? 4);
                $attendanceScore += $attendances->where('status', 'late')->count() * ($settings['attendance_late'] ?? 2);
                $totalScore += $attendanceScore;
            }

            // 3. Hifz & Review Points
            if (($settings['hifz_enabled'] ?? false) || ($settings['review_enabled'] ?? false)) {
                $days = StudentPlanDay::whereHas('plan', function($q) use ($student) {
                        $q->where('student_id', $student->id)
                          ->where('is_approved', 1);
                    })
                    ->where('date', '>=', $startDate)
                    ->where('date', '<=', $endDate)
                    ->get();

                if ($settings['hifz_enabled'] ?? false) {
                    foreach ($days as $day) {
                        $hifz = (int) $day->hifz_achievement;
                        if ($hifz === 3) $hifzScore += ($settings['hifz_excellent'] ?? 10);
                        elseif ($hifz === 2) $hifzScore += ($settings['hifz_good'] ?? 7);
                        elseif ($hifz === 1) $hifzScore += ($settings['hifz_acceptable'] ?? 4);
                    }
                }

                if ($settings['review_enabled'] ?? false) {
                    foreach ($days as $day) {
                        $review = (int) $day->review_achievement;
                        if ($review === 3) $reviewScore += ($settings['review_excellent'] ?? 5);
                        elseif ($review === 2 || $review === 1) $reviewScore += ($settings['review_good'] ?? 3);
                    }
                }
                $totalScore += $hifzScore + $reviewScore;
            }

            $standings[] = [
                'student' => $student,
                'score' => $totalScore,
                'details' => [
                    'manual' => $manualScore,
                    'extra_points_score' => $extraPointsScore,
                    'extra_points_list' => $extraPointsList,
                    'attendance' => $attendanceScore,
                    'hifz' => $hifzScore,
                    'review' => $reviewScore,
                    'criteria_counts' => $criteriaCounts,
                ]
            ];
        }

        return collect($standings)->sortByDesc('score')->values();
    }

    // Proxy for original getStandings to not break student dashboard
    public function getStandings(Leaderboard $leaderboard)
    {
        return $this->getDetailedStandings($leaderboard);
    }

    public function getDailyScores(Leaderboard $leaderboard, $date)
    {
        $students = Student::where('circle_id', $leaderboard->circle_id)->get();
        $settings = $leaderboard->settings ?? [];
        
        $dailyScores = [];

        foreach ($students as $student) {
            $totalScore = 0;
            $manualScore = 0;
            $automatedScore = 0;
            $hifzScoreDaily = 0;
            $reviewScoreDaily = 0;
            $attendanceScoreDaily = 0;

            // 1. Manual Criteria Scores for TODAY
            $manualScore = DB::table('leaderboard_scores')
                ->join('leaderboard_criteria', 'leaderboard_scores.leaderboard_criterion_id', '=', 'leaderboard_criteria.id')
                ->where('leaderboard_scores.leaderboard_id', $leaderboard->id)
                ->where('leaderboard_scores.student_id', $student->id)
                ->whereDate('leaderboard_scores.date', $date)
                ->sum('leaderboard_criteria.points');
            
            $totalScore += $manualScore;

            // 2. Attendance Points for TODAY
            if ($settings['attendance_enabled'] ?? false) {
                $attendance = Attendance::where('student_id', $student->id)
                    ->whereDate('date', $date)
                    ->first();
                
                if ($attendance) {
                    if ($attendance->status === 'present') {
                        $attendanceScoreDaily = ($settings['attendance_present'] ?? 4);
                    } elseif ($attendance->status === 'late') {
                        $attendanceScoreDaily = ($settings['attendance_late'] ?? 2);
                    }
                    $automatedScore += $attendanceScoreDaily;
                }
            }

            // 3. Hifz & Review Points for TODAY
            if (($settings['hifz_enabled'] ?? false) || ($settings['review_enabled'] ?? false)) {
                $days = StudentPlanDay::whereHas('plan', function($q) use ($student) {
                        $q->where('student_id', $student->id)
                          ->where('is_approved', 1);
                    })
                    ->whereDate('date', $date)
                    ->get();

                foreach ($days as $day) {
                    if ($settings['hifz_enabled'] ?? false) {
                        $hifz = (int) $day->hifz_achievement;
                        if ($hifz === 3) $hifzScoreDaily += ($settings['hifz_excellent'] ?? 10);
                        elseif ($hifz === 2) $hifzScoreDaily += ($settings['hifz_good'] ?? 7);
                        elseif ($hifz === 1) $hifzScoreDaily += ($settings['hifz_acceptable'] ?? 4);
                    }

                    if ($settings['review_enabled'] ?? false) {
                        $review = (int) $day->review_achievement;
                        if ($review === 3) $reviewScoreDaily += ($settings['review_excellent'] ?? 5);
                        elseif ($review === 2 || $review === 1) $reviewScoreDaily += ($settings['review_good'] ?? 3);
                    }
                }
                $automatedScore += $hifzScoreDaily + $reviewScoreDaily;
            }
            
            $totalScore += $automatedScore;

            $dailyScores[$student->id] = [
                'total' => $totalScore,
                'manual' => $manualScore,
                'automated' => $automatedScore,
                'hifz' => $hifzScoreDaily,
                'review' => $reviewScoreDaily,
                'attendance' => $attendanceScoreDaily,
            ];
        }

        return $dailyScores;
    }
}
