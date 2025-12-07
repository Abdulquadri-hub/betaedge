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
       Schema::create('tenant_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('active_students')->default(0);
            $table->integer('active_instructors')->default(0);
            $table->integer('total_enrollments')->default(0);
            $table->integer('new_enrollments')->default(0);
            $table->integer('completed_courses')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->integer('class_sessions_held')->default(0);
            $table->decimal('average_attendance_rate', 5, 2)->default(0);
            $table->bigInteger('storage_used_bytes')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_analytics');
    }
};
