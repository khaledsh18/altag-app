<?php

namespace App\Observers;

use App\Models\StudentPlanDay;

class StudentPlanDayObserver
{
    /**
     * Handle the StudentPlanDay "created" event.
     */
    public function created(StudentPlanDay $studentPlanDay): void
    {
        //
    }

    /**
     * Handle the StudentPlanDay "updated" event.
     */
    public function updated(StudentPlanDay $studentPlanDay): void
    {
        //
    }

    /**
     * Handle the StudentPlanDay "deleted" event.
     */
    public function deleted(StudentPlanDay $studentPlanDay): void
    {
        //
    }

    /**
     * Handle the StudentPlanDay "restored" event.
     */
    public function restored(StudentPlanDay $studentPlanDay): void
    {
        //
    }

    /**
     * Handle the StudentPlanDay "force deleted" event.
     */
    public function forceDeleted(StudentPlanDay $studentPlanDay): void
    {
        //
    }
}
