<?php

// ============================================================================
// CONTRACTS/INTERFACES (app/Contracts/)
// ============================================================================


interface TenantRepositoryInterface
{
    public function create(array $data);
    public function findBySlug(string $slug);
    public function findByDomain(string $domain);
    public function update(int $id, array $data);
    public function getStats(int $tenantId): array;
}

interface TenantUserRepositoryInterface
{
    public function create(array $data);
    public function getUserTenants(int $userId);
    public function attachUserToTenant(int $userId, int $tenantId, string $role);
}

interface SubscriptionRepositoryInterface
{
    public function getActivePlans();
    public function getPlanBySlug(string $slug);
    public function createSubscription(array $data);
    public function getActiveSubscription(int $tenantId);
}


// ============================================================================
// REPOSITORIES (app/Repositories/)
// ============================================================================


use App\Models\Tenant;
use App\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TenantRepository implements TenantRepositoryInterface
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return Tenant::create($data);
        });
    }

    public function findBySlug(string $slug)
    {
        return Cache::remember("tenant:slug:{$slug}", 3600, function () use ($slug) {
            return Tenant::where('slug', $slug)->first();
        });
    }

    public function findByDomain(string $domain)
    {
        return Cache::remember("tenant:domain:{$domain}", 3600, function () use ($domain) {
            return Tenant::where('custom_domain', $domain)
                ->orWhere('subdomain', $domain)
                ->first();
        });
    }

    public function update(int $id, array $data)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update($data);
        
        // Clear cache
        Cache::forget("tenant:slug:{$tenant->slug}");
        Cache::forget("tenant:domain:{$tenant->custom_domain}");
        Cache::forget("tenant:{$id}:settings");
        
        return $tenant->fresh();
    }

    public function getStats(int $tenantId): array
    {
        return Cache::remember("tenant:{$tenantId}:stats", 600, function () use ($tenantId) {
            $tenant = Tenant::findOrFail($tenantId);
            return [
                'students' => $tenant->students()->count(),
                'instructors' => $tenant->instructors()->count(),
                'courses' => $tenant->courses()->count(),
                'active_enrollments' => $tenant->students()
                    ->whereHas('enrollments', fn($q) => $q->where('status', 'active'))
                    ->count(),
            ];
        });
    }
}

class TenantUserRepository implements TenantUserRepositoryInterface
{
    public function create(array $data)
    {
        return \App\Models\TenantUser::create($data);
    }

    public function getUserTenants(int $userId)
    {
        return Cache::remember("user:{$userId}:tenants", 3600, function () use ($userId) {
            return \App\Models\TenantUser::where('user_id', $userId)
                ->with('tenant')
                ->where('status', 'active')
                ->get();
        });
    }

    public function attachUserToTenant(int $userId, int $tenantId, string $role)
    {
        Cache::forget("user:{$userId}:tenants");
        
        return $this->create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }
}

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function getActivePlans()
    {
        return Cache::remember('subscription:plans:active', 86400, function () {
            return \App\Models\SubscriptionPlan::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    public function getPlanBySlug(string $slug)
    {
        return Cache::remember("subscription:plan:{$slug}", 86400, function () use ($slug) {
            return \App\Models\SubscriptionPlan::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();
        });
    }

    public function createSubscription(array $data)
    {
        return \App\Models\TenantSubscription::create($data);
    }

    public function getActiveSubscription(int $tenantId)
    {
        return \App\Models\TenantSubscription::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('plan')
            ->first();
    }
}


// ============================================================================
// SERVICES (app/Services/Tenant/)
// ============================================================================

namespace App\Services\Tenant;

use App\Contracts\{TenantRepositoryInterface, TenantUserRepositoryInterface, SubscriptionRepositoryInterface};
use App\Jobs\Tenant\{ProvisionTenantJob, SendWelcomeEmailJob};
use App\Events\Tenant\{TenantCreated, TenantOnboardingCompleted};
use Illuminate\Support\Facades\{DB, Hash, Cache};
use App\Models\User;

class TenantOnboardingService
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo,
        private TenantUserRepositoryInterface $tenantUserRepo,
        private SubscriptionRepositoryInterface $subscriptionRepo,
        private TenantProvisioningService $provisioningService
    ) {}

    public function createTenantWithOwner(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create User Account
            $user = User::create([
                'email' => $data['email'],
                'username' => $this->generateUsername($data['email']),
                'password' => Hash::make($data['password']),
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'phone' => $data['phone'] ?? null,
                'user_type' => 'admin',
                'status' => 'active',
            ]);

            // 2. Create Tenant
            $tenant = $this->tenantRepo->create([
                'name' => $data['school_name'],
                'slug' => Tenant::generateUniqueSlug($data['school_name']),
                'owner_id' => $user->id,
                'timezone' => $data['timezone'] ?? 'Africa/Lagos',
                'country' => $data['country'] ?? 'Nigeria',
                'currency' => $data['currency'] ?? 'NGN',
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
                'onboarding_step' => 'profile',
            ]);

            // 3. Link Owner to Tenant
            $this->tenantUserRepo->attachUserToTenant($user->id, $tenant->id, 'owner');

            // 4. Get Selected Plan (default to free)
            $planSlug = $data['plan'] ?? 'free';
            $plan = $this->subscriptionRepo->getPlanBySlug($planSlug);

            // 5. Create Subscription
            $subscription = $this->subscriptionRepo->createSubscription([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'amount' => $plan->price_monthly,
                'currency' => 'NGN',
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);

            // 6. Dispatch Provisioning Job (Async)
            ProvisionTenantJob::dispatch($tenant->id)->onQueue('provisioning');

            // 7. Send Welcome Email (Async)
            SendWelcomeEmailJob::dispatch($user, $tenant)->onQueue('emails');

            // 8. Fire Event
            event(new TenantCreated($tenant, $user));

            return [
                'user' => $user,
                'tenant' => $tenant,
                'subscription' => $subscription,
                'subdomain' => $tenant->subdomain,
            ];
        });
    }

    public function completeOnboardingStep(int $tenantId, string $step, array $data): bool
    {
        $tenant = $this->tenantRepo->update($tenantId, [
            'onboarding_step' => $step,
            ...$data
        ]);

        Cache::forget("tenant:{$tenantId}:onboarding");

        if ($step === 'completed') {
            $this->completeOnboarding($tenant);
        }

        return true;
    }

    private function completeOnboarding($tenant): void
    {
        $this->tenantRepo->update($tenant->id, [
            'setup_completed' => true,
            'onboarding_step' => 'completed',
        ]);

        event(new TenantOnboardingCompleted($tenant));
    }

    private function generateUsername(string $email): string
    {
        $base = explode('@', $email)[0];
        $username = preg_replace('/[^a-z0-9]/', '', strtolower($base));
        
        $counter = 1;
        $original = $username;
        while (User::where('username', $username)->exists()) {
            $username = $original . $counter++;
        }
        
        return $username;
    }
}

class TenantProvisioningService
{
    public function provisionTenant(int $tenantId): void
    {
        $tenant = \App\Models\Tenant::findOrFail($tenantId);

        // Set active tenant context
        session(['active_tenant_id' => $tenantId]);

        // 1. Create Default Academic Levels (if applicable)
        $this->createDefaultAcademicLevels($tenant);

        // 2. Create Default Settings
        $this->createDefaultSettings($tenant);

        // 3. Setup Storage
        $this->setupStorage($tenant);

        Cache::put("tenant:{$tenantId}:provisioned", true, 3600);
    }

    private function createDefaultAcademicLevels($tenant): void
    {
        $schoolType = $tenant->school_type ?? 'academy';

        $levels = $this->getDefaultLevelsForType($schoolType);

        foreach ($levels as $level) {
            \App\Models\AcademicLevel::create([
                'tenant_id' => $tenant->id,
                ...$level
            ]);
        }
    }

    private function getDefaultLevelsForType(string $type): array
    {
        return match($type) {
            'academy', 'primary' => [
                ['name' => 'Primary 1', 'grade_number' => 1, 'level_type' => 'elementary', 'sort_order' => 1],
                ['name' => 'Primary 2', 'grade_number' => 2, 'level_type' => 'elementary', 'sort_order' => 2],
                ['name' => 'Primary 3', 'grade_number' => 3, 'level_type' => 'elementary', 'sort_order' => 3],
                ['name' => 'Primary 4', 'grade_number' => 4, 'level_type' => 'elementary', 'sort_order' => 4],
                ['name' => 'Primary 5', 'grade_number' => 5, 'level_type' => 'elementary', 'sort_order' => 5],
                ['name' => 'Primary 6', 'grade_number' => 6, 'level_type' => 'elementary', 'sort_order' => 6],
            ],
            'secondary' => [
                ['name' => 'JSS 1', 'grade_number' => 7, 'level_type' => 'middle', 'sort_order' => 7],
                ['name' => 'JSS 2', 'grade_number' => 8, 'level_type' => 'middle', 'sort_order' => 8],
                ['name' => 'JSS 3', 'grade_number' => 9, 'level_type' => 'middle', 'sort_order' => 9],
                ['name' => 'SS 1', 'grade_number' => 10, 'level_type' => 'high', 'sort_order' => 10],
                ['name' => 'SS 2', 'grade_number' => 11, 'level_type' => 'high', 'sort_order' => 11],
                ['name' => 'SS 3', 'grade_number' => 12, 'level_type' => 'high', 'sort_order' => 12],
            ],
            default => []
        };
    }

    private function createDefaultSettings($tenant): void
    {
        $settings = [
            'email_notifications' => true,
            'sms_notifications' => false,
            'allow_self_enrollment' => true,
            'require_payment_approval' => true,
            'attendance_tracking' => true,
        ];

        Cache::put("tenant:{$tenant->id}:settings", $settings, 86400);
    }

    private function setupStorage($tenant): void
    {
        $basePath = storage_path("app/tenants/{$tenant->id}");
        
        $directories = [
            'logos', 'course-materials', 'student-submissions', 
            'assignments', 'certificates', 'reports'
        ];

        foreach ($directories as $dir) {
            if (!file_exists("{$basePath}/{$dir}")) {
                mkdir("{$basePath}/{$dir}", 0755, true);
            }
        }
    }
}


// ============================================================================
// JOBS (app/Jobs/Tenant/)
// ============================================================================

namespace App\Jobs\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use App\Services\Tenant\TenantProvisioningService;

class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    public function __construct(public int $tenantId) {}

    public function handle(TenantProvisioningService $service): void
    {
        $service->provisionTenant($this->tenantId);
    }
}

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $user, public $tenant) {}

    public function handle(): void
    {
        $user->notify(new \App\Notifications\Tenant\WelcomeNotification($this->tenant));
    }
}


// ============================================================================
// EVENTS (app/Events/Tenant/)
// ============================================================================

namespace App\Events\Tenant;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantCreated
{
    use Dispatchable, SerializesModels;
    
    public function __construct(public $tenant, public $owner) {}
}

class TenantOnboardingCompleted
{
    use Dispatchable, SerializesModels;
    
    public function __construct(public $tenant) {}
}


// ============================================================================
// FORM REQUESTS (app/Http/Requests/Tenant/)
// ============================================================================

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class TenantRegistrationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:255', 'unique:tenants,name'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'plan' => ['nullable', 'string', 'exists:subscription_plans,slug'],
            'timezone' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
        ];
    }
}

class OnboardingStepRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return match($this->route('step')) {
            'profile' => [
                'logo' => ['nullable', 'image', 'max:2048'],
                'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'secondary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'description' => ['nullable', 'string', 'max:1000'],
            ],
            'structure' => [
                'school_type' => ['required', 'in:academy,primary,secondary,tutoring,university'],
                'academic_levels' => ['required', 'array', 'min:1'],
            ],
            'instructors' => [
                'instructors' => ['nullable', 'array'],
                'instructors.*.email' => ['required_with:instructors', 'email'],
                'instructors.*.name' => ['required_with:instructors', 'string'],
            ],
            'courses' => [
                'courses' => ['nullable', 'array'],
                'courses.*.title' => ['required_with:courses', 'string'],
                'courses.*.price' => ['required_with:courses', 'numeric', 'min:0'],
            ],
            default => []
        };
    }
}


// ============================================================================
// CONTROLLERS (app/Http/Controllers/Tenant/)
// ============================================================================

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\TenantOnboardingService;
use App\Http\Requests\Tenant\{TenantRegistrationRequest, OnboardingStepRequest};
use Inertia\Inertia;

class TenantOnboardingController extends Controller
{
    public function __construct(private TenantOnboardingService $service) {}

    public function showRegistration()
    {
        $plans = app(\App\Contracts\SubscriptionRepositoryInterface::class)->getActivePlans();
        
        return Inertia::render('Platform/Register', [
            'plans' => $plans,
        ]);
    }

    public function register(TenantRegistrationRequest $request)
    {
        $result = $this->service->createTenantWithOwner($request->validated());

        auth()->login($result['user']);
        session(['active_tenant_id' => $result['tenant']->id]);

        return redirect()->route('tenant.onboarding.wizard')
            ->with('success', 'Welcome! Let\'s set up your school.');
    }

    public function showWizard()
    {
        $tenant = tenant();
        $step = $tenant->onboarding_step ?? 'profile';

        return Inertia::render('Onboarding/Wizard', [
            'tenant' => $tenant,
            'currentStep' => $step,
            'steps' => ['profile', 'structure', 'instructors', 'courses', 'payment'],
        ]);
    }

    public function updateStep(OnboardingStepRequest $request, string $step)
    {
        $this->service->completeOnboardingStep(
            tenantId(),
            $step,
            $request->validated()
        );

        $nextStep = $this->getNextStep($step);

        if ($nextStep === 'completed') {
            return redirect()->route('tenant.dashboard')
                ->with('success', 'Onboarding completed!');
        }

        return redirect()->route('tenant.onboarding.wizard', ['step' => $nextStep]);
    }

    private function getNextStep(string $current): string
    {
        $steps = ['profile', 'structure', 'instructors', 'courses', 'payment', 'completed'];
        $index = array_search($current, $steps);
        return $steps[$index + 1] ?? 'completed';
    }
}


// ============================================================================
// ROUTES (routes/web.php)
// ============================================================================

// Platform Routes (teach.com)
Route::domain(config('app.main_domain'))->group(function () {
    Route::get('/start-school', [TenantOnboardingController::class, 'showRegistration'])
        ->name('platform.register');
    Route::post('/start-school', [TenantOnboardingController::class, 'register'])
        ->name('platform.register.store');
});

// Tenant Routes (subdomain/custom domain)
Route::middleware(['tenant', 'auth', 'tenant.access'])->group(function () {
    Route::prefix('onboarding')->name('tenant.onboarding.')->group(function () {
        Route::get('/wizard', [TenantOnboardingController::class, 'showWizard'])
            ->name('wizard');
        Route::post('/wizard/{step}', [TenantOnboardingController::class, 'updateStep'])
            ->name('wizard.update');
    });
});


// ============================================================================
// SERVICE PROVIDER BINDINGS (app/Providers/RepositoryServiceProvider.php)
// ============================================================================

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\TenantRepositoryInterface::class,
            \App\Repositories\TenantRepository::class
        );
        
        $this->app->bind(
            \App\Contracts\TenantUserRepositoryInterface::class,
            \App\Repositories\TenantUserRepository::class
        );
        
        $this->app->bind(
            \App\Contracts\SubscriptionRepositoryInterface::class,
            \App\Repositories\SubscriptionRepository::class
        );
    }
}

// Register in config/app.php:
// 'providers' => [
//     App\Providers\RepositoryServiceProvider::class,
// ]
