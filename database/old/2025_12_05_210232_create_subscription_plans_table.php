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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Starter, Professional, Enterprise
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency')->default('NGN');
            $table->integer('max_students')->default(0); // 0 = unlimited
            $table->integer('max_instructors')->default(0);
            $table->integer('max_courses')->default(0);
            $table->integer('storage_gb')->default(5);
            $table->json('features')->nullable(); // ['custom_domain', 'analytics', 'api_access']
            $table->boolean('has_custom_domain')->default(false);
            $table->boolean('has_analytics')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->timestamps();

            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
