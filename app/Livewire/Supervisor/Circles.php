<?php

namespace App\Livewire\Supervisor;

use App\Models\Circle;
use App\Models\Teacher;
use Flux\Flux;
use Livewire\Component;

class Circles extends Component
{
    public $circles;

    public string $name = '';

    public string $description = '';

    public $editingCircleId = null;

    public string $search = '';

    public string $teacherFilter = 'all';

    public array $selectedTeachers = [];

    public $teachersList = [];

    public function mount(): void
    {
        $this->loadData();
    }

    private function getSupervisorCircleIds(): array
    {
        $supervisor = auth()->guard('supervisor')->user();

        return Circle::whereIn('stage_id', $supervisor->stages()->pluck('stages.id'))->pluck('id')->toArray();
    }

    private function getSupervisorStages()
    {
        return auth()->guard('supervisor')->user()->stages()->get();
    }

    public function loadData(): void
    {
        $circleIds = $this->getSupervisorCircleIds();

        $query = Circle::with(['stage', 'teachers'])
            ->withCount('students')
            ->whereIn('id', $circleIds);

        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        if ($this->teacherFilter !== 'all') {
            $query->whereHas('teachers', function ($q) {
                $q->where('teachers.id', $this->teacherFilter);
            });
        }

        $this->circles = $query->latest()->get();
        $this->teachersList = Teacher::where('is_approved', true)
            ->whereHas('circles', function ($q) use ($circleIds) {
                $q->whereIn('circles.id', $circleIds);
            })
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->loadData();
    }

    public function updatedTeacherFilter(): void
    {
        $this->loadData();
    }

    public function edit($id): void
    {
        $circleIds = $this->getSupervisorCircleIds();
        $circle = Circle::whereIn('id', $circleIds)->findOrFail($id);

        $this->editingCircleId = $circle->id;
        $this->name = $circle->name;
        $this->description = $circle->description ?? '';
        $this->selectedTeachers = $circle->teachers->pluck('id')->toArray();

        Flux::modal('circle-modal')->show();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $circleIds = $this->getSupervisorCircleIds();
        $circle = Circle::whereIn('id', $circleIds)->findOrFail($this->editingCircleId);

        $circle->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        // Only assign teachers from this supervisor's scope
        $validTeachers = Teacher::whereHas('circles', function ($q) use ($circleIds) {
            $q->whereIn('circles.id', $circleIds);
        })->whereIn('id', $this->selectedTeachers)->pluck('id')->toArray();

        $circle->teachers()->sync($validTeachers);

        Flux::toast(__('تم تحديث الحلقة بنجاح'), variant: 'success');
        $this->reset(['name', 'description', 'editingCircleId', 'selectedTeachers']);
        $this->loadData();
        Flux::modal('circle-modal')->close();
    }

    public function cancel(): void
    {
        $this->reset(['name', 'description', 'editingCircleId', 'selectedTeachers']);
    }

    public function render()
    {
        return view('livewire.supervisor.circles', [
            'stages' => $this->getSupervisorStages(),
        ])->layout('layouts.role-shell');
    }
}
