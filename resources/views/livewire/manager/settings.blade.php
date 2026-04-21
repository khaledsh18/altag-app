<div class="space-y-6">
    <div class="flex items-center gap-3">
        <div class="p-2 rounded-lg bg-zinc-50 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
            <flux:icon icon="cog" />
        </div>
        <div>
            <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-white">إعدادات الانضباط</flux:heading>
            <flux:subheading>تخصيص القوانين الخاصة بشؤون الغياب والتأخير للطلاب</flux:subheading>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-xs p-6 max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div class="space-y-4">
                <flux:input type="number" label="حد الغياب المسموح" wire:model="absenceLimit" min="1" description="عدد أيام الغياب التي إذا تجاوزها الطالب سيتم اعتباره متجاوزاً وتحويله للإدارة." />
                
                <flux:input type="number" label="حد التأخير المسموح" wire:model="latenessLimit" min="1" description="عدد أيام التأخير التي إذا تجاوزها الطالب سيتم اعتباره متجاوزاً وتحويله للإدارة." />
                
                <flux:input type="number" label="فترة الحساب (بالأيام)" wire:model="calculationPeriodDays" min="1" description="يحدد الإطار الزمني الذي يتم حساب الغياب والتأخير خلاله (مثلاً: آخر 30 يوماً)." />
            </div>

            <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex justify-end">
                <flux:button type="submit" variant="primary" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white">
                    حفظ الإعدادات
                </flux:button>
            </div>
        </form>
    </div>
</div>
