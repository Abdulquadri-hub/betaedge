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
        Schema::create('onboarding_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('profile')->nullable();
            $table->json('plan')->nullable();
            $table->json('payment')->nullable();
            $table->enum('status', ['pending', 'failed', 'processing', 'completed', 'processed', 'error'])->default('pending')->index();
            $table->foreignId('session_id')->nullable()->index();
            $table->string('current_step', 50)->default('profile');
            $table->tinyInteger('progress_percentage')->default(0);
            $table->string('progress_message')->nullable();
            $table->string('job_id')->unique()->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_processes');
    }
};
