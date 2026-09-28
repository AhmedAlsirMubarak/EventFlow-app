<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records which deadline alert has already gone out for a task, so each
     * one is sent exactly once — the day before, on the day, the first day
     * it's overdue, and a single escalation after 3 days overdue. Cleared
     * whenever the due date or assignee changes (Task::booted()).
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('due_soon_alerted_at')->nullable();
            $table->timestamp('due_today_alerted_at')->nullable();
            $table->timestamp('overdue_alerted_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['due_soon_alerted_at', 'due_today_alerted_at', 'overdue_alerted_at', 'escalated_at']);
        });
    }
};
