<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')
                  ->nullable()
                  ->after('course_id');

            $table->unsignedInteger('total_enrolled')
                  ->default(0)
                  ->after('actual_participants')
                  ->comment('Snapshot of batch enrollment at session creation');

            $table->text('notes')
                  ->nullable()
                  ->after('session_notes');

            $table->foreign('batch_id')
                  ->references('id')
                  ->on('batches')
                  ->nullOnDelete();

            $table->index(['tenant_id', 'batch_id']);
            $table->index(['batch_id', 'scheduled_start']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->enum('enrollment_status', ['open', 'closed'])
                  ->default('open')
                  ->after('status');

            $table->string('whatsapp_link')->nullable()->after('notes');

            $table->index(['tenant_id', 'enrollment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropIndex(['tenant_id', 'batch_id']);
            $table->dropIndex(['batch_id', 'scheduled_start']);
            $table->dropColumn(['batch_id', 'total_enrolled', 'notes']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'enrollment_status']);
            $table->dropColumn(['enrollment_status', 'whatsapp_link']);
        });
    }
};