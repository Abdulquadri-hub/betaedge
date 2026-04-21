<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->boolean('email_new_enrollment')->default(true);
            $table->boolean('email_payment_received')->default(true);
            $table->boolean('email_batch_complete')->default(true);
            $table->boolean('email_complaint')->default(true);
            $table->boolean('email_weekly_summary')->default(true);
            $table->boolean('sms_new_enrollment')->default(false);
            $table->boolean('sms_payment_received')->default(true);
            $table->boolean('sms_complaint')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('name');
            $table->string('phone')->nullable()->after('owner_email');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->enum('enrollment_mode', ['automatic', 'manual'])->default('automatic')->after('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_notification_preferences');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'phone', 'whatsapp', 'enrollment_mode']);
        });
    }
};