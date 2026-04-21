<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->string('session_day')->nullable();        
            $table->time('session_time')->nullable();         
            $table->unsignedInteger('session_duration_minutes')->default(90);
            $table->string('session_platform')->nullable();   
            $table->string('session_frequency')->nullable();
            $table->unsignedInteger('display_order')->default(0); 
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('instructor_id')->references('id')->on('instructors')->nullOnDelete();
            $table->unique(['batch_id', 'course_id']);
            $table->index(['tenant_id', 'batch_id']);
            $table->index(['tenant_id', 'course_id']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->unsigned()->nullable()->after('max_students');
            $table->string('price_note')->nullable()->after('price');
        });

        if (Schema::hasColumn('courses', 'session_frequency')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn([
                    'session_frequency',
                    'session_day',
                    'session_time',
                    'session_duration_minutes',
                    'session_platform',
                ]);
            });
        }

        if (Schema::hasColumn('enrollments', 'batch_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id')->nullable()->change();
            });
        }

        if (Schema::hasColumn('batches', 'course_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->after('tenant_id');
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('session_frequency')->nullable()->after('max_students');
            $table->string('session_day')->nullable()->after('session_frequency');
            $table->time('session_time')->nullable()->after('session_day');
            $table->unsignedInteger('session_duration_minutes')->default(90)->after('session_time');
            $table->string('session_platform')->nullable()->after('session_duration_minutes');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn(['price', 'price_note']);
        });

        Schema::dropIfExists('batch_courses');
    }
};