<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique(); // one submission per school
            $table->unsignedBigInteger('submitted_by');       // user_id of owner
            $table->enum('id_type', [
                'nin',         
                'bvn',          
                'passport',     
                'drivers_license',
                'cac',          
                'voters_card', 
            ]);
            $table->string('id_number')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('business_name')->nullable();
            $table->string('rc_number')->nullable();          
            $table->string('passport_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])
                ->default('pending')
                ->index();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'status']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('verification_status', [
                'unverified',    
                'pending',      
                'under_review',  
                'verified',     
                'rejected',    
            ])->default('unverified')->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('verification_status');
        });
        Schema::dropIfExists('kyc_submissions');
    }
};