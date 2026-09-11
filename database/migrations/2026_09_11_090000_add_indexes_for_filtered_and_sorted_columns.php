<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Foreign keys are already auto-indexed by foreignId()->constrained(); these are
     * the status/date columns actually filtered or sorted on in application code today
     * (task/request/reminder status filters, due-date and held-at sorting, etc.) that
     * had no index at all until now.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status');
            $table->index('due_date');
        });

        Schema::table('stakeholder_requests', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->index('status');
            $table->index('remind_at');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index('category');
            $table->index('visibility');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->index('held_at');
        });

        Schema::table('decisions', function (Blueprint $table) {
            $table->index('decision_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('stakeholder_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['remind_at']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['visibility']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropIndex(['held_at']);
        });

        Schema::table('decisions', function (Blueprint $table) {
            $table->dropIndex(['decision_date']);
        });
    }
};
