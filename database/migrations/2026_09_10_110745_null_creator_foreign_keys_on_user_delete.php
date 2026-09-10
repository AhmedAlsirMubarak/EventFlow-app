<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose creator/uploader column cascades a user delete into destroying
     * every record they ever created. Deleting one user should never delete an
     * event, task, document, meeting, decision, idea, or floor plan.
     *
     * @var array<string, string>
     */
    private array $columns = [
        'events' => 'created_by',
        'ideas' => 'created_by',
        'tasks' => 'created_by',
        'documents' => 'created_by',
        'document_versions' => 'uploaded_by',
        'meetings' => 'created_by',
        'decisions' => 'created_by',
        'floor_plans' => 'created_by',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->columns as $table => $column) {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropForeign([$column]);
                $table->unsignedBigInteger($column)->nullable()->change();
                $table->foreign($column)->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Deliberately does not restore the NOT NULL constraint: a real user deletion
     * under the new behavior may have already left rows with a null author, and
     * silently deleting those rows to satisfy a rollback would be worse than a
     * rollback that leaves the column nullable.
     */
    public function down(): void
    {
        foreach ($this->columns as $table => $column) {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }
};
