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
         Schema::create('marketplace_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('rating'); // 1-5
            $table->text('review')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('helpful_count')->default(0);
            $table->integer('reported_count')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_reviews');
    }
};
