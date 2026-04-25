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
        Schema::table('instructors', function (Blueprint $table) {
            $table->enum('invite_status', ['sent', 'accepted', 'declined'])->default('accepted')->after('status');
            $table->timestamp('invited_at')->nullable()->after('invite_status');
            $table->timestamp('accepted_at')->nullable()->after('invited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropColumn(['invite_status', 'invited_at', 'accepted_at']);
        });
    }
};
