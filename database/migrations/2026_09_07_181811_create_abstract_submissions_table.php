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
        Schema::create('abstract_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_call_id')->constrained()->cascadeOnDelete();
            $table->foreignId('abstract_track_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('title');
            $table->text('abstract_body');
            $table->string('keywords')->nullable();
            $table->string('speaker_name');
            $table->string('speaker_email');
            $table->string('speaker_phone')->nullable();
            $table->text('speaker_bio')->nullable();
            $table->string('speaker_organization')->nullable();
            $table->string('status')->default('Submitted');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->string('public_token', 40)->unique();
            $table->timestamp('submitted_at');
            $table->string('disk_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abstract_submissions');
    }
};
