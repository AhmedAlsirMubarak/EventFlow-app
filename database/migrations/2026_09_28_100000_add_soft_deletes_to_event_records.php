<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The everyday records people create and delete inside an event. Deleting
     * one now moves it to "Recently deleted" (restorable) instead of destroying
     * it outright; `model:prune` removes it for good after 30 days.
     *
     * @var list<string>
     */
    private array $tables = [
        'tasks',
        'ideas',
        'meetings',
        'decisions',
        'stakeholder_requests',
        'reminders',
        'documents',
        'lessons_learned',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
