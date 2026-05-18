<?php

namespace App\Livewire\Supervisor;

use App\Models\Circle;
use App\Models\Leaderboard;
use App\Models\LeaderboardCriterion;
use Flux\Flux;
use Livewire\Component;

class Competitions extends Component
{
    public $competitions;

    // Form
    public bool $showModal = false;

    public bool $isEditing = false;

    public $editingId = null;

    public string $title = '';

    public string $start_date = '';

    public string $end_date = '';

    public bool $is_active = true;

    public array $selectedCircles = [];

    // Settings (mirrors teacher leaderboard settings)
    public bool $hifz_enabled = true;

    public int $hifz_excellent = 10;

    public int $hifz_good = 7;

    public int $hifz_acceptable = 4;

    public bool $review_enabled = true;

    public int $review_excellent = 5;

    public int $review_good = 3;

    public bool $attendance_enabled = true;

    public int $attendance_present = 4;

    public int $attendance_late = 2;

    public bool $extra_points_enabled = true;

    public array $criteria = [];

    public $circlesList = [];

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'selectedCircles' => 'required|array|min:1',
            'selectedCircles.*' => 'exists:circles,id',
            'criteria.*.name' => 'required|string|max:255',
            'criteria.*.points' => 'required|numeric|min:0',
        ];
    }

    protected $messages = [
        'selectedCircles.required' => 'يجب اختيار حلقة واحدة على الأقل.',
        'selectedCircles.min' => 'يجب اختيار حلقة واحدة على الأقل.',
    ];

    public function mount(): void
    {
        $this->loadData();
    }

    private function getSupervisorCircleIds(): array
    {
        $supervisor = auth()->guard('supervisor')->user();

        return Circle::whereIn('stage_id', $supervisor->stages()->pluck('stages.id'))->pluck('id')->toArray();
    }

    public function loadData(): void
    {
        $circleIds = $this->getSupervisorCircleIds();
        $supervisorId = auth()->guard('supervisor')->id();

        $this->circlesList = Circle::with('stage')->whereIn('id', $circleIds)->get();

        $this->competitions = Leaderboard::with(['circles.stage'])
            ->withCount('criteria')
            ->where('supervisor_id', $supervisorId)
            ->latest()
            ->get();
    }

    public function create(): void
    {
        $this->reset('title', 'end_date', 'editingId', 'selectedCircles', 'criteria');
        $this->start_date = now()->format('Y-m-d');
        $this->is_active = true;
        $this->hifz_enabled = true;
        $this->hifz_excellent = 10;
        $this->hifz_good = 7;
        $this->hifz_acceptable = 4;
        $this->review_enabled = true;
        $this->review_excellent = 5;
        $this->review_good = 3;
        $this->attendance_enabled = true;
        $this->attendance_present = 4;
        $this->attendance_late = 2;
        $this->extra_points_enabled = true;
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id): void
    {
        $supervisorId = auth()->guard('supervisor')->id();
        $competition = Leaderboard::with('criteria', 'circles')
            ->where('supervisor_id', $supervisorId)
            ->findOrFail($id);

        $this->editingId = $competition->id;
        $this->title = $competition->title;
        $this->start_date = $competition->start_date->format('Y-m-d');
        $this->end_date = $competition->end_date ? $competition->end_date->format('Y-m-d') : '';
        $this->is_active = $competition->is_active;
        $this->selectedCircles = $competition->circles->pluck('id')->toArray();

        $settings = $competition->settings ?? [];
        $this->hifz_enabled = $settings['hifz_enabled'] ?? true;
        $this->hifz_excellent = $settings['hifz_excellent'] ?? 10;
        $this->hifz_good = $settings['hifz_good'] ?? 7;
        $this->hifz_acceptable = $settings['hifz_acceptable'] ?? 4;
        $this->review_enabled = $settings['review_enabled'] ?? true;
        $this->review_excellent = $settings['review_excellent'] ?? 5;
        $this->review_good = $settings['review_good'] ?? 3;
        $this->attendance_enabled = $settings['attendance_enabled'] ?? true;
        $this->attendance_present = $settings['attendance_present'] ?? 4;
        $this->attendance_late = $settings['attendance_late'] ?? 2;
        $this->extra_points_enabled = $settings['extra_points_enabled'] ?? true;

        $this->criteria = $competition->criteria->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'points' => $c->points,
        ])->toArray();

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function addCriterion(): void
    {
        $this->criteria[] = ['name' => '', 'points' => 5];
    }

    public function removeCriterion($index): void
    {
        unset($this->criteria[$index]);
        $this->criteria = array_values($this->criteria);
    }

    public function toggleActive($id): void
    {
        $supervisorId = auth()->guard('supervisor')->id();
        $competition = Leaderboard::where('supervisor_id', $supervisorId)->findOrFail($id);
        $competition->is_active = ! $competition->is_active;
        $competition->save();
        $this->loadData();
        Flux::toast($competition->is_active ? 'تم تنشيط المسابقة' : 'تم إيقاف المسابقة', variant: 'success');
    }

    public function save(): void
    {
        $this->validate();

        $supervisorId = auth()->guard('supervisor')->id();
        $allowedCircleIds = $this->getSupervisorCircleIds();

        // Ensure selected circles are within scope
        $validCircles = array_intersect($this->selectedCircles, $allowedCircleIds);

        $settings = [
            'hifz_enabled' => $this->hifz_enabled,
            'hifz_excellent' => (int) $this->hifz_excellent,
            'hifz_good' => (int) $this->hifz_good,
            'hifz_acceptable' => (int) $this->hifz_acceptable,
            'review_enabled' => $this->review_enabled,
            'review_excellent' => (int) $this->review_excellent,
            'review_good' => (int) $this->review_good,
            'attendance_enabled' => $this->attendance_enabled,
            'attendance_present' => (int) $this->attendance_present,
            'attendance_late' => (int) $this->attendance_late,
            'extra_points_enabled' => $this->extra_points_enabled,
        ];

        $competition = Leaderboard::updateOrCreate(
            ['id' => $this->editingId, 'supervisor_id' => $supervisorId],
            [
                'supervisor_id' => $supervisorId,
                'circle_id' => $validCircles[0] ?? null, // primary circle (backward-compat)
                'title' => $this->title,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date ?: null,
                'is_active' => $this->is_active,
                'settings' => $settings,
            ]
        );

        // Sync participating circles
        $competition->circles()->sync($validCircles);

        // Sync custom criteria
        $existingIds = collect($this->criteria)->pluck('id')->filter()->toArray();
        LeaderboardCriterion::where('leaderboard_id', $competition->id)
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach ($this->criteria as $c) {
            LeaderboardCriterion::updateOrCreate(
                ['id' => $c['id'] ?? null, 'leaderboard_id' => $competition->id],
                ['name' => $c['name'], 'points' => $c['points']]
            );
        }

        Flux::toast($this->isEditing ? 'تم تحديث المسابقة بنجاح' : 'تم إنشاء المسابقة بنجاح', variant: 'success');
        $this->showModal = false;
        $this->loadData();
    }

    public function delete($id): void
    {
        $supervisorId = auth()->guard('supervisor')->id();
        Leaderboard::where('supervisor_id', $supervisorId)->findOrFail($id)->delete();
        $this->loadData();
        Flux::toast('تم حذف المسابقة', variant: 'success');
    }

    public function render()
    {
        return view('livewire.supervisor.competitions')
            ->layout('layouts.role-shell');
    }
}
