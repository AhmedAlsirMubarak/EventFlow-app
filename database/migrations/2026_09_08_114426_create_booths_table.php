<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('booth_number');
            $table->decimal('x', 6, 3);
            $table->decimal('y', 6, 3);
            $table->decimal('width', 6, 3);
            $table->decimal('height', 6, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['floor_plan_id', 'booth_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booths');
    }
};
