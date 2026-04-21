<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Student;
use App\Models\Circle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public $name = '';
    public $phone = '';
    public $search = '';

    // Modal state
    public $viewingStudent = null;
    public $editName = '';
    public $editPhone = '';
    public $editCircleId = null;
    public $stats = [];

    public function createStudent()
    {
        $this->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $teacher = Auth::guard('teacher')->user();
        $circle = $teacher->circles()->first();

        if (!$circle) {
            Flux::toast('لا توجد حلقة مرتبطة بك لإضافة الطلاب فيها.', variant: 'danger');
            return;
        }

        Student::create([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => 'student_' . Str::random(10) . '@uncompleted.altag.app',
            'password' => Hash::make(Str::random(10)),
            'circle_id' => $circle->id,
            'is_approved' => true,
            'access_token' => Str::random(32),
            'is_data_completed' => false,
        ]);

        $this->reset(['name', 'phone']);
        $this->resetPage();

        Flux::toast('تم إنشاء حساب الطالب بنجاح', variant: 'success');
    }

    public function viewStudent($studentId)
    {
        $teacher = Auth::guard('teacher')->user();
        $circleIds = $teacher->circles()->pluck('id');

        $this->viewingStudent = Student::with(['circle', 'guardian', 'plans' => function($q) { $q->latest(); }, 'attendances'])
            ->whereIn('circle_id', $circleIds)
            ->findOrFail($studentId);

        $this->editName = $this->viewingStudent->name;
        $this->editPhone = $this->viewingStudent->phone;
        $this->editCircleId = $this->viewingStudent->circle_id;

        $this->stats = [
            'present' => $this->viewingStudent->attendances->where('status', 'present')->count(),
            'absent' => $this->viewingStudent->attendances->where('status', 'absent')->count(),
            'late' => $this->viewingStudent->attendances->where('status', 'late')->count(),
        ];

        Flux::modal('student-details')->show();
    }

    public function saveStudentInfo()
    {
        $this->validate([
            'editName' => 'required|string|min:2|max:255',
            'editPhone' => 'nullable|string|max:20',
            'editCircleId' => 'required',
        ]);

        $teacher = Auth::guard('teacher')->user();
        $teacherCircles = $teacher->circles()->pluck('id')->toArray();
        if (!in_array($this->editCircleId, $teacherCircles)) {
           abort(403);
        }

        $this->viewingStudent->update([
            'name' => $this->editName,
            'phone' => $this->editPhone,
            'circle_id' => $this->editCircleId,
        ]);

        Flux::toast('تم حفظ بيانات الطالب بنجاح', variant: 'success');
    }

    public function resetToken($studentId)
    {
        $teacher = Auth::guard('teacher')->user();
        $circleIds = $teacher->circles()->pluck('id');

        $student = Student::whereIn('circle_id', $circleIds)->findOrFail($studentId);
        $student->update([
            'access_token' => Str::random(32),
        ]);

        Flux::toast('تم إنشاء رابط جديد للطالب بنجاح', variant: 'success');
    }

    public function with()
    {
        $teacher = Auth::guard('teacher')->user();
        $circles = $teacher->circles;
        $circleIds = $circles->pluck('id');

        $students = Student::whereIn('circle_id', $circleIds)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return [
            'students' => $students,
            'circles' => $circles,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('إدارة طلاب الحلقة') }}</flux:heading>
            <flux:subheading>{{ __('قم بإنشاء حسابات سريعة لطلابك باستخدام روابط الدخول السحرية وإدارة بياناتهم.') }}</flux:subheading>
        </div>
    </div>

    <!-- Quick Create Card -->
    <flux:card>
        <form wire:submit="createStudent" class="flex flex-col md:flex-row items-end gap-4">
            <div class="w-full md:w-2/5">
                <flux:input wire:model="name" label="{{ __('اسم الطالب رباعي') }}"
                    placeholder="{{ __('مثال: محمد أحمد') }}" required />
            </div>
            <div class="w-full md:w-2/5">
                <flux:input wire:model="phone" label="{{ __('رقم هاتف الطالب') }}"
                    placeholder="{{ __('اختياري') }}" />
            </div>
            <flux:button type="submit" variant="primary" icon="user-plus" class="min-w-fit">{{ __('إنشاء للطالب') }}</flux:button>
        </form>
    </flux:card>

    <flux:card class="p-0 overflow-hidden">
        <div class="p-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-4">
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="{{ __('بحث باسم الطالب...') }}"
                class="max-w-xs" />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('اسم الطالب') }}</flux:table.column>
                <flux:table.column>{{ __('واتساب') }}</flux:table.column>
                <flux:table.column>{{ __('الحلقة') }}</flux:table.column>
                <flux:table.column>{{ __('حالة البيانات') }}</flux:table.column>
                <flux:table.column>{{ __('رابط الدخول') }}</flux:table.column>
                <flux:table.column class="w-10"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($students as $student)
                    <flux:table.row wire:key="student-row-{{ $student->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <flux:table.cell class="font-medium whitespace-nowrap" wire:click="viewStudent({{ $student->id }})">
                            {{ $student->name }}
                        </flux:table.cell>
                        <flux:table.cell @click.stop>
                            @if($student->phone)
                                <flux:button as="a" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $student->phone) }}" target="_blank" size="xs" color="green" icon="chat-bubble-left-ellipsis" variant="ghost">
                                    {{ __('تواصل') }}
                                </flux:button>
                            @else
                                <span class="text-zinc-400 text-xs">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell wire:click="viewStudent({{ $student->id }})">
                            {{ $student->circle->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell wire:click="viewStudent({{ $student->id }})">
                            @if($student->is_data_completed)
                                <flux:badge color="green" size="sm" icon="check-circle">{{ __('مكتملة') }}</flux:badge>
                            @else
                                <flux:badge color="amber" size="sm" icon="clock">{{ __('غير مكتملة') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($student->access_token)
                                <div class="flex items-center gap-2"
                                    x-data="{ copied: false, link: '{{ route('magic-link', ['token' => $student->access_token]) }}' }"
                                    @click.stop>
                                    <flux:input readonly copyable class="max-w-xs text-xs"
                                        :value="route('magic-link', ['token' => $student->access_token])" />
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div @click.stop>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="viewStudent({{ $student->id }})" icon="eye">{{ __('عرض وتعديل التفاصيل') }}</flux:menu.item>
                                        <flux:separator />
                                        @if ($student->access_token)
                                            <flux:menu.item as="a" href="{{ route('magic-link.login-as', $student->access_token) }}"
                                                target="_blank" icon="arrow-right">{{ __('الدخول لحساب الطالب') }}
                                            </flux:menu.item>
                                        @endif
                                        <flux:menu.item wire:click="resetToken({{ $student->id }})"
                                            wire:confirm="هل أنت متأكد من تغيير الرابط؟ سيتم إبطال الرابط القديم فوراً."
                                            variant="danger" icon="arrow-path">{{ __('إعادة إنشاء الرابط') }}</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
            {{ $students->links() }}
        </div>
    </flux:card>

    <!-- Student Details Modal -->
    <flux:modal name="student-details" variant="flyout" class="md:w-[500px]">
        @if($viewingStudent)
            <div class="space-y-8">
                <div>
                    <flux:heading size="xl">{{ __('ملف الطالب') }}</flux:heading>
                    <flux:subheading>{{ __('عرض وتعديل بيانات الطالب وخططه') }}</flux:subheading>
                </div>

                <form wire:submit="saveStudentInfo" class="space-y-4">
                    <flux:input wire:model="editName" label="{{ __('اسم الطالب') }}" required />
                    
                    <flux:input wire:model="editPhone" label="{{ __('رقم الهاتف') }}" placeholder="{{ __('اختياري') }}" dir="ltr" class="text-right" />
                    
                    <flux:select wire:model="editCircleId" label="{{ __('الحلقة') }}" required>
                        @foreach($circles as $c)
                            <flux:select.option value="{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex justify-end pt-2">
                        <flux:button type="submit" variant="primary" size="sm" icon="check">{{ __('حفظ التعديلات') }}</flux:button>
                    </div>
                </form>

                <flux:separator />

                <!-- Guardian Info -->
                <div>
                    <flux:heading size="sm" class="mb-3">{{ __('ولي الأمر وطرق التواصل') }}</flux:heading>
                    @if($viewingStudent->guardian)
                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                            <div>
                                <div class="font-medium text-sm">{{ $viewingStudent->guardian->name }}</div>
                                <div class="text-xs text-zinc-500" dir="ltr">{{ $viewingStudent->guardian->phone ?? 'لا يوجد رقم' }}</div>
                            </div>
                            @if($viewingStudent->guardian->phone)
                                <flux:button as="a" target="_blank" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $viewingStudent->guardian->phone) }}" size="sm" icon="chat-bubble-left-ellipsis" color="green">
                                    {{ __('واتساب') }}
                                </flux:button>
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-zinc-500 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                            {{ __('لم يقم الطالب بربط حساب ولي أمر بعد.') }}
                        </div>
                    @endif
                </div>

                <flux:separator />

                <!-- Quran Plans -->
                <div>
                    <flux:heading size="sm" class="mb-3">{{ __('الخطط القرآنية (' . $viewingStudent->plans->count() . ')') }}</flux:heading>
                    
                    <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
                        @forelse($viewingStudent->plans as $plan)
                            <div class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700/50 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">
                                        @if($plan->plan_type === 'hifz_review') {{ __('حفظ ومراجعة') }} @elseif($plan->plan_type === 'hifz') {{ __('حفظ') }} @else {{ __('مراجعة') }} @endif
                                    </span>
                                    <span class="text-xs text-zinc-500">{{ $plan->start_date->format('Y/m/d') }} • {{ $plan->days_count }} يوم</span>
                                </div>
                                <flux:button as="a" href="{{ route('teacher.print-plan', $plan->id) }}" target="_blank" size="xs" variant="ghost" icon="eye"></flux:button>
                            </div>
                        @empty
                            <div class="text-sm text-zinc-500 text-center py-4">{{ __('ليس لديه خطط مسجلة.') }}</div>
                        @endforelse
                    </div>
                </div>

                <flux:separator />

                <!-- Attendance Stats -->
                <div>
                    <flux:heading size="sm" class="mb-3">{{ __('سجل الحضور والغياب الإجمالي') }}</flux:heading>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 bg-green-50 dark:bg-green-500/10 rounded-xl border border-green-100 dark:border-green-500/20">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-500">{{ $stats['present'] ?? 0 }}</div>
                            <div class="text-xs text-green-600/70 dark:text-green-500/70 mt-1">{{ __('حضور') }}</div>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-500/10 rounded-xl border border-red-100 dark:border-red-500/20">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-500">{{ $stats['absent'] ?? 0 }}</div>
                            <div class="text-xs text-red-600/70 dark:text-red-500/70 mt-1">{{ __('غياب') }}</div>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-500/10 rounded-xl border border-amber-100 dark:border-amber-500/20">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-500">{{ $stats['late'] ?? 0 }}</div>
                            <div class="text-xs text-amber-600/70 dark:text-amber-500/70 mt-1">{{ __('تأخر') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>