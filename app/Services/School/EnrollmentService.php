<?php

namespace App\Services\School;


use App\Mail\EnrollmentWelcomeMail;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\EnrollmentPayment;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Contracts\Services\School\EnrollmentServiceInterface;
use App\Contracts\Services\School\PaystackServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(
        private readonly PaystackServiceInterface $paystack
    ) {}
     
    public function registerAndBeginEnrollment(
        Batch  $batch,
        Tenant $tenant,
        array  $studentData,
        ?array $parentData,
        string $callbackUrl
    ): array {
        $this->paystack->setTenant($tenant);

        return DB::transaction(function () use ($batch, $tenant, $studentData, $parentData, $callbackUrl) {

            $this->guardBatchAvailability($batch);

            $student = $this->createStudentAccount($tenant, $studentData);

            $isAdult = $this->isAdult($studentData['date_of_birth']);
            $route   = $isAdult ? 'adult_direct' : 'parent_payment';

            $payerEmail = $studentData['email'];
            $payerName  = $studentData['name'];

            if (!$isAdult && $parentData) {
                $parent     = $this->createParentAccount($tenant, $parentData, $student);
                $payerEmail = $parentData['email'];
                $payerName  = $parentData['name'];
            }

            $existing = Enrollment::where('student_id', $student->id)
                ->where('batch_id', $batch->id)
                ->whereNotIn('status', ['dropped'])
                ->first();

            if ($existing) {
                throw new \RuntimeException(
                    "This student is already enrolled in this batch."
                );
            }

            $enrollment = Enrollment::create([
                'tenant_id'         => $tenant->id,
                'student_id'        => $student->id,
                'batch_id'          => $batch->id,
                'enrollment_route'  => $route,
                'status'            => 'pending',
                'enrollment_date'   => now(),
            ]);

            $amountNaira = (int) ($batch->price ?? 0);
            $split       = PaystackServiceInterface::calculateSplit($amountNaira);
            $reference   = PaystackServiceInterface::generateReference($tenant->slug);

            $payment = EnrollmentPayment::create([
                'tenant_id'          => $tenant->id,
                'batch_id'           => $batch->id,
                'student_id'         => $student->id,
                'enrollment_id'      => $enrollment->id,
                'paystack_reference' => $reference,
                'amount_kobo'        => $split['amount_kobo'],
                'platform_fee_kobo'  => $split['platform_fee_kobo'],
                'school_amount_kobo' => $split['school_amount_kobo'],
                'currency'           => 'NGN',
                'status'             => 'pending',
                'ip_address'         => request()->ip(),
            ]);

            // Link payment to enrollment
            $enrollment->update(['enrollment_payment_id' => $payment->id]);

            // ── Initialize Paystack ────────────────────────────────────────
            $paystackData = $this->paystack->initializePayment(
                email:       $payerEmail,
                amountNaira: $amountNaira,
                reference:   $reference,
                metadata: [
                    'batch_id'        => $batch->id,
                    'batch_name'      => $batch->batch_name,
                    'student_id'      => $student->id,
                    'student_name'    => $studentData['name'],
                    'enrollment_id'   => $enrollment->id,
                    'payment_id'      => $payment->id,
                    'payer_name'      => $payerName,
                    'enrollment_route'=> $route,
                ],
                callbackUrl: $callbackUrl
            );

            // Store access code on payment
            $payment->update([
                'paystack_access_code'       => $paystackData['access_code'],
                'paystack_authorization_url' => $paystackData['authorization_url'],
            ]);

            return [
                'enrollment'       => $enrollment,
                'payment'          => $payment,
                'authorization_url'=> $paystackData['authorization_url'],
                'access_code'      => $paystackData['access_code'],
                'student'          => $student,
            ];
        });
    }

    /**
     * Complete enrollment after successful Paystack payment.
     *
     * Called from both:
     * - The Paystack callback URL (redirect)
     * - The Paystack webhook (reliable, async)
     *
     * Idempotent — safe to call multiple times for the same reference.
     */
    public function completeEnrollment(string $paystackReference): Enrollment
    {
        return DB::transaction(function () use ($paystackReference) {

            $payment = EnrollmentPayment::where('paystack_reference', $paystackReference)
                ->lockForUpdate()
                ->firstOrFail();

            // Already processed — idempotent
            if ($payment->isCompleted()) {
                return $payment->enrollment;
            }

            $this->paystack->setTenant($payment->tenant);
            $paystackData = $this->paystack->verifyPayment($paystackReference);

            // Mark payment completed
            $payment->markCompleted($paystackData);

            // Activate enrollment
            $enrollment = $payment->enrollment;
            $enrollment->markActive();

            // Add student to batch_student pivot
            $batch   = $payment->batch;
            $student = $payment->student;

            $alreadyInBatch = $batch->students()
    ->wherePivot('student_id', $student->id)
    ->exists();

            if (!$alreadyInBatch) {
                $batch->students()->attach($student->id, [
                    'status'      => 'active',
                    'enrolled_at' => now(),
                    'tenant_id'   => $batch->tenant_id,
                ]);
            }

            // Update batch enrollment count in the batch
            // (activeStudents() count is computed, no denormalized count column needed)

            // Send welcome notification (queued)
            try {
                $this->sendWelcomeNotification($enrollment, $batch, $student);
            } catch (\Throwable $e) {
                // Don't fail enrollment if email fails
                Log::error('Welcome email failed', [
                    'enrollment_id' => $enrollment->id,
                    'error'         => $e->getMessage(),
                ]);
            }

            return $enrollment->fresh();
        });
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function guardBatchAvailability(Batch $batch): void
    {
        // if (!$batch->is_published) {
        //     throw new \RuntimeException("This batch is not open for enrollment.");
        // }

        if ($batch->enrollment_status !== 'open') {
            throw new \RuntimeException("Enrollment for this batch is currently closed.");
        }

        if ($batch->isFull()) {
            throw new \RuntimeException(
                "This batch is full. Please check other available batches."
            );
        }

        if (is_null($batch->price)) {
            throw new \RuntimeException("Batch price is not configured.");
        }
    }

    private function createStudentAccount(Tenant $tenant, array $data): Student
    {
        // Re-use existing account if email matches
        $existing = User::where('email', $data['email'])->first();

        if ($existing) {
            if ($existing->user_type !== 'student') {
                throw new \RuntimeException(
                    "An account with this email already exists but is not a student account."
                );
            }

            $student = $existing->student;
            if (!$student) {
                throw new \RuntimeException("Account exists but student record is missing.");
            }

            return $student;
        }

        // Create new user
        $user = User::create([
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'password'          => Hash::make($data['password']),
            'user_type'         => 'student',
            'is_active'            => true,
            'email_verified_at' => now(), 
        ]);

        // Create student record
        $dob   = $data['date_of_birth'] ?? null;
        $level = $this->inferAcademicLevel($tenant, $data);

        $student = Student::create([
            'first_name'        => $this->firstName($data['name']),
            'last_name'         => $this->lastName($data['name']),
            'tenant_id'         => $tenant->id,
            'user_id'           => $user->id,
            'student_id'        => $this->generateStudentId($tenant),
            'date_of_birth'     => $dob,
            'enrollment_status' => 'active',
            'enrollment_date'   => now(),
            'academic_level_id' => $level,
        ]);

        // Link student to tenant
        \App\Models\TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id'   => $user->id,
            'role'      => 'student',
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        return $student;
    }

    private function createParentAccount(Tenant $tenant, array $data, Student $student): ParentModel
    {
        // Re-use existing parent account if email matches
        $existing = User::where('email', $data['email'])->first();

        if ($existing) {
            $parent = $existing->parent;

            if (!$parent) {
                // Create parent record for existing user
                $parent = ParentModel::firstOrCreate(
                    ['user_id' => $existing->id, 'tenant_id' => $tenant->id],
                    ['parent_id' => $this->generateParentId()]
                );
            }
        } else {
            $user = User::create([
                'first_name'        => $this->firstName($data['name']),
                'last_name'         => $this->lastName($data['name']),
                'email'             => $data['email'],
                'phone'             => $data['phone'] ?? null,
                'password'          => Hash::make($data['password'] ?? \Illuminate\Support\Str::random(12)),
                'user_type'         => 'parent',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]);

            $parent = ParentModel::create([
                'tenant_id' => $tenant->id,
                'user_id'   => $user->id,
                'parent_id' => $this->generateParentId(),
            ]);

            \App\Models\TenantUser::create([
                'tenant_id' => $tenant->id,
                'user_id'   => $user->id,
                'role'      => 'parent',
                'status'    => 'active',
                'joined_at' => now(),
            ]);
        }

        // Link parent → student
        $alreadyLinked = $student->parents()->where('parent_id', $parent->id)->exists();

        if (!$alreadyLinked) {
            $student->parents()->attach($parent->id, [
                'relationship'        => $data['relationship'] ?? 'guardian',
                'is_primary_contact'  => true,
                'can_view_grades'     => true,
                'can_view_attendance' => true,
                'tenant_id'           => $tenant->id,
            ]);
        }

        return $parent;
    }

    private function sendWelcomeNotification(Enrollment $enrollment, Batch $batch, Student $student): void
    {
        // Queue a welcome email
        $user = $student->user;
        if ($user) {
            // Mail::to($user->email)->queue(EnrollmentWelcomeMail($enrollment, $batch, $student));
        }
    }

    private function isAdult(string $dateOfBirth): bool
    {
        return Carbon::parse($dateOfBirth)->age;
    }

    private function inferAcademicLevel(Tenant $tenant, array $data): ?int
    {
        // Future: could infer from batch's courses' academic levels
        return null;
    }

    private function firstName(string $name): string
    {
        return explode(' ', trim($name))[0] ?? $name;
    }

    private function lastName(string $name): string
    {
        $parts = explode(' ', trim($name));
        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    }

    private function generateStudentId(Tenant $tenant): string
    {
        $prefix = strtoupper(substr($tenant->slug, 0, 3));
        $count  = Student::where('tenant_id', $tenant->id)->count() + 1;
        return $prefix . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function generateParentId(): string
    {
        return 'PAR-' . strtoupper(\Illuminate\Support\Str::random(8));
    }
}