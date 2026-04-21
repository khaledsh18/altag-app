<?php

namespace App\Console\Commands;

use App\Models\Circle;
use App\Models\Manager;
use App\Models\Stage;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportAltagData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'altag:import {path=altag-data.json} {--fresh : Delete existing data before importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Data from altag-data.json file to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = base_path($this->argument('path'));

        if (! File::exists($path)) {
            $this->error("File {$path} not found.");

            return;
        }

        $jsonData = json_decode(File::get($path), true);

        if (! $jsonData) {
            $this->error("Invalid JSON data in file {$path}.");

            return;
        }

        $data = $jsonData;

        $this->info("Importing data from {$path}...");

        if ($this->option('fresh')) {
            $this->info('Wiping existing data...');

            Schema::disableForeignKeyConstraints();

            DB::table('attendances')->truncate();
            DB::table('student_plan_days')->truncate();
            DB::table('student_plans')->truncate();
            DB::table('students')->truncate();
            DB::table('teachers')->truncate();
            DB::table('circles')->truncate();
            DB::table('stages')->truncate();
            DB::table('circle_teacher')->truncate();
            DB::table('managers')->truncate();
            DB::table('guardians')->truncate();

            Schema::enableForeignKeyConstraints();

            $this->info('Existing data wiped successfully.');
        }

        DB::beginTransaction();
        try {

            // Setup passwords and configs
            $allPassword = $data['all_password'] ?? 'password';
            $allEmails = $data['all_emails'] ?? 'random';
            $managerPassword = $data['manager_password'] ?? $allPassword;
            $managerName = $data['manager_name'] ?? 'Manager';
            $managerEmails = $data['manager_emails'] ?? 'manager@example.com';

            $defaultPassword = Hash::make($allPassword);

            // Import Manager
            Manager::updateOrCreate(
                ['email' => $managerEmails],
                [
                    'name' => $managerName,
                    'password' => Hash::make($managerPassword),
                ]
            );
            $this->info('Manager imported.');

            // Import Stages
            $stagesGroups = $data['stages'] ?? [];

            // In the provided json file, "stages" is a nested array. E.g. [ [ { "stage": "ثانوية", ... } ] ]
            $stagesList = [];
            foreach ($stagesGroups as $group) {
                if (is_array($group)) {
                    foreach ($group as $item) {
                        if (is_array($item)) {
                            $stagesList[] = $item;
                        }
                    }
                }
            }

            foreach ($stagesList as $stageData) {
                if (! isset($stageData['stage_name'])) {
                    continue;
                }

                $stage = Stage::firstOrCreate(
                    ['name' => $stageData['stage_name']]
                );

                $circles = $stageData['circles'] ?? [];

                foreach ($circles as $circleData) {
                    $circleName = $circleData['circle_name'] ?? null;
                    $teacherName = $circleData['teacher'] ?? null;

                    if (! $circleName) {
                        continue;
                    }

                    $circle = Circle::firstOrCreate(
                        [
                            'name' => $circleName,
                            'stage_id' => $stage->id,
                        ]
                    );

                    // Import Teacher for circle
                    if ($teacherName) {
                        $email = 't_'.uniqid().'@example.com';

                        $teacher = Teacher::firstOrCreate(
                            ['name' => $teacherName],
                            [
                                'email' => $email,
                                'password' => $defaultPassword,
                                'is_approved' => true,
                                'access_token' => Str::random(32),
                                'is_data_completed' => false,
                            ]
                        );

                        // Attach to circle
                        $circle->teachers()->syncWithoutDetaching([$teacher->id]);
                    }

                    // Import Students
                    $students = $circleData['students'] ?? [];
                    foreach ($students as $studentName) {
                        $email = 's_'.uniqid().'@example.com';

                        Student::firstOrCreate(
                            ['name' => $studentName],
                            [
                                'circle_id' => $circle->id,
                                'email' => $email,
                                'password' => $defaultPassword,
                                'is_approved' => true,
                                'access_token' => Str::random(32),
                                'is_data_completed' => false,
                            ]
                        );
                    }
                }
            }

            DB::commit();
            $this->info('Stages, circles, teachers, and students imported successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to import data: '.$e->getMessage());
        }
    }
}
