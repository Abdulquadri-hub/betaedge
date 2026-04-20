<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_payment_configs', function (Blueprint $table) {
            $table->string('public_key')->nullable()->after('tenant_id');
            $table->text('secret_key')->nullable()->after('public_key');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_payment_configs', function (Blueprint $table) {
            $table->dropColumn(['public_key', 'secret_key']);
        });
    }
};