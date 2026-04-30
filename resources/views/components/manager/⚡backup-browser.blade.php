<?php

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public $filename;
    public $selectedTable = '';
    public $selectedRows = [];
    public $selectAll = false;
    public $restoreRelated = false;

    public function mount($filename)
    {
        $this->filename = $filename;
        $this->setupConnection();
    }

    public function setupConnection()
    {
        $path = storage_path('app/backups/' . $this->filename);
        if (!file_exists($path)) {
            abort(404, 'Backup file not found.');
        }

        config([
            'database.connections.backup_db' => [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
                'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            ]
        ]);
    }

    public function getTablesProperty()
    {
        $this->setupConnection();
        $tables = DB::connection('backup_db')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'migrations'");
        return collect($tables)->pluck('name')->toArray();
    }

    public function updatedSelectedTable()
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value && $this->selectedTable) {
            $this->setupConnection();
            $this->selectedRows = DB::connection('backup_db')
                ->table($this->selectedTable)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function restoreSelected()
    {
        if (empty($this->selectedRows) || !$this->selectedTable) {
            Flux::toast('الرجاء تحديد صفوف لاسترجاعها.', variant: 'danger');
            return;
        }

        $this->setupConnection();
        $backupDb = DB::connection('backup_db');
        $mainDb = DB::connection(config('database.default'));

        $mainDb->statement('PRAGMA foreign_keys = OFF;');

        DB::beginTransaction();
        try {
            foreach ($this->selectedRows as $id) {
                $this->restoreRowRecursively($this->selectedTable, $id, $backupDb, $mainDb);
            }
            DB::commit();
            $mainDb->statement('PRAGMA foreign_keys = ON;');
            $this->selectedRows = [];
            $this->selectAll = false;
            Flux::toast('تم استرجاع البيانات بنجاح.', variant: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $mainDb->statement('PRAGMA foreign_keys = ON;');
            Flux::toast('حدث خطأ أثناء الاسترجاع: ' . $e->getMessage(), variant: 'danger');
        }
    }

    private function restoreRowRecursively($table, $id, $backupDb, $mainDb, &$restored = [])
    {
        $key = "{$table}_{$id}";
        if (isset($restored[$key]))
            return;

        $row = $backupDb->table($table)->where('id', $id)->first();
        if (!$row)
            return;

        $restored[$key] = true;

        if ($this->restoreRelated) {
            $fks = $backupDb->select("PRAGMA foreign_key_list('{$table}')");
            foreach ($fks as $fk) {
                $fromCol = $fk->from;
                $toTable = $fk->table;
                $toCol = $fk->to;
                $relatedId = $row->{$fromCol};

                if ($relatedId && $toCol === 'id') {
                    $this->restoreRowRecursively($toTable, $relatedId, $backupDb, $mainDb, $restored);
                }
            }
        }

        $rowArray = (array) $row;
        $mainDb->table($table)->updateOrInsert(['id' => $id], $rowArray);
    }

    public function with(): array
    {
        $this->setupConnection();
        $rows = null;
        $columns = [];

        if ($this->selectedTable) {
            $backupDb = DB::connection('backup_db');
            $rows = $backupDb->table($this->selectedTable)->paginate(15);
            if ($rows->count() > 0) {
                $columns = array_keys((array) $rows->first());
            } else {
                $colsInfo = $backupDb->select("PRAGMA table_info('{$this->selectedTable}')");
                $columns = collect($colsInfo)->pluck('name')->toArray();
            }
        }

        return [
            'tables' => $this->tables,
            'rows' => $rows,
            'columns' => $columns,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('manager.settings') }}" icon="arrow-right" variant="subtle"
            class="rtl:rotate-180" />
        <div>
            <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-white">تصفح النسخة الاحتياطية
            </flux:heading>
            <flux:subheading class="dir-ltr text-right">{{ $filename }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
            <flux:card>
                <flux:heading size="lg" class="mb-4">الجداول</flux:heading>
                <div class="space-y-1 max-h-[600px] overflow-y-auto pr-2">
                    @foreach($tables as $table)
                        <button wire:click="$set('selectedTable', '{{ $table }}')"
                            class="w-full text-right px-3 py-2 rounded-lg text-sm transition-colors {{ $selectedTable === $table ? 'bg-indigo-50 text-indigo-700 font-bold dark:bg-indigo-900/30 dark:text-indigo-400' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                            {{ $table }}
                        </button>
                    @endforeach
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-3">
            <flux:card>
                @if($selectedTable)
                    <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center mb-6">
                        <div>
                            <flux:heading size="lg">{{ $selectedTable }}</flux:heading>
                            <flux:subheading>تحديد الصفوف لاسترجاعها للبيانات الحالية</flux:subheading>
                        </div>
                        <div class="flex items-center gap-4">
                            <flux:checkbox wire:model="restoreRelated" label="استرجاع السجلات المرتبطة (العلاقات)" />
                            <flux:button wire:click="restoreSelected" variant="primary"
                                class="bg-indigo-600 hover:bg-indigo-700 border-none text-white"
                                :disabled="empty($selectedRows)">
                                استرجاع المحدد ({{ count($selectedRows) }})
                            </flux:button>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <table class="w-full text-sm text-right">
                            <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400">
                                <tr>
                                    @if(in_array('id', $columns))
                                        <th class="px-4 py-3 w-10">
                                            <flux:checkbox wire:model.live="selectAll" />
                                        </th>
                                    @endif
                                    @foreach($columns as $col)
                                        <th class="px-4 py-3 font-medium">{{ $col }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @forelse($rows as $row)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                        @if(in_array('id', $columns))
                                            <td class="px-4 py-3">
                                                <flux:checkbox wire:model.live="selectedRows" value="{{ $row->id }}" />
                                            </td>
                                        @endif
                                        @foreach($columns as $col)
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 truncate max-w-[200px]"
                                                title="{{ $row->$col }}">
                                                {{ Str::limit($row->$col, 50) }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($columns) + 1 }}" class="px-4 py-8 text-center text-zinc-500">
                                            الجدول فارغ
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 dir-ltr">
                        {{ $rows->links() }}
                    </div>
                @else
                    <div class="text-center py-12 text-zinc-500">
                        <flux:icon icon="table-cells" class="mx-auto size-12 mb-4 opacity-50" />
                        <p>الرجاء اختيار جدول من القائمة لاستعراض محتوياته</p>
                    </div>
                @endif
            </flux:card>
        </div>
    </div>
</div>