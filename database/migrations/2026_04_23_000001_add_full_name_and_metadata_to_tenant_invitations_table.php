<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_invitations', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_invitations', 'full_name')) {
                $table->string('full_name')->nullable()->after('email');
            }

            if (!Schema::hasColumn('tenant_invitations', 'metadata')) {
                $table->json('metadata')->nullable()->after('accepted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_invitations', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_invitations', 'metadata')) {
                $table->dropColumn('metadata');
            }

            if (Schema::hasColumn('tenant_invitations', 'full_name')) {
                $table->dropColumn('full_name');
            }
        });
    }
};
