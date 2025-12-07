<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantScope - Automatically filters queries by tenant_id
 * Applied globally to all tenant-specific models
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $tenantId = $this->getCurrentTenantId();
        
        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }

    protected function getCurrentTenantId(): ?int
    {
        // Get from session (set by middleware)
        if (session()->has('active_tenant_id')) {
            return session('active_tenant_id');
        }

        // Get from authenticated user's current tenant
        if (auth()->check() && auth()->user()->currentTenant) {
            return auth()->user()->currentTenant->id;
        }

        return null;
    }
}


namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToTenant Trait
 * Apply to all models that belong to a tenant
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Automatically add tenant_id on creation
        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = self::getCurrentTenantId();
            }
        });

        // Apply global scope
        static::addGlobalScope(new TenantScope());
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public static function getCurrentTenantId(): ?int
    {
        if (session()->has('active_tenant_id')) {
            return session('active_tenant_id');
        }

        if (auth()->check() && auth()->user()->currentTenant) {
            return auth()->user()->currentTenant->id;
        }

        return null;
    }

    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId);
    }

    public function scopeAcrossAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}


namespace App\Models;

/**
 * Updated User Model to support multi-tenancy
 */
class User extends \Illuminate\Foundation\Auth\User
{
    // ... existing code ...

    // NEW: Multi-tenant relationships
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
                    ->withPivot(['role', 'permissions', 'status', 'joined_at'])
                    ->withTimestamps();
    }

    public function currentTenant()
    {
        $tenantId = session('active_tenant_id');
        
        if (!$tenantId) return null;

        return $this->tenants()->where('tenant_id', $tenantId)->first();
    }

    public function tenantUsers()
    {
        return $this->hasMany(TenantUser::class);
    }

    public function activeTenantUsers()
    {
        return $this->tenantUsers()->where('status', 'active');
    }

    // NEW: Multi-tenant helper methods
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenants()->where('tenant_id', $tenantId)->exists();
    }

    public function getRoleInTenant(int $tenantId): ?string
    {
        $tenantUser = $this->tenantUsers()
                          ->where('tenant_id', $tenantId)
                          ->first();

        return $tenantUser?->role;
    }

    public function isOwnerOf(Tenant $tenant): bool
    {
        return $this->tenantUsers()
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'owner')
                    ->exists();
    }

    public function isInstructorAt(Tenant $tenant): bool
    {
        return $this->tenantUsers()
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'instructor')
                    ->exists();
    }

    public function canAccessTenant(int $tenantId): bool
    {
        return $this->tenantUsers()
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->exists();
    }

    public function switchTenant(int $tenantId): bool
    {
        if (!$this->canAccessTenant($tenantId)) {
            return false;
        }

        session(['active_tenant_id' => $tenantId]);

        // Update last accessed
        $this->tenantUsers()
             ->where('tenant_id', $tenantId)
             ->update(['last_accessed_at' => now()]);

        return true;
    }

    public function getAccessibleTenants()
    {
        return $this->tenants()
                    ->wherePivot('status', 'active')
                    ->get();
    }
}


/**
 * Update existing models to use BelongsToTenant trait
 */

// Add to Student model
class Student extends Model
{
    use BelongsToTenant; // Add this trait

    // Add tenant_id to fillable
    protected $fillable = [
        'tenant_id', // Add this
        'user_id', 
        'student_id', 
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to Instructor model
class Instructor extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'user_id',
        'instructor_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to Course model
class Course extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'course_code',
        'title',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to AcademicLevel model
class AcademicLevel extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'name',
        'grade_number',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to Enrollment model
class Enrollment extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'student_id',
        'course_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to Assignment model
class Assignment extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'course_id',
        'instructor_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to ClassSession model
class ClassSession extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'course_id',
        'instructor_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to ParentModel
class ParentModel extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'user_id',
        'parent_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to Material model
class Material extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'course_id',
        'instructor_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to Submission model
class Submission extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'assignment_id',
        'student_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to Attendance model
class Attendance extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'class_session_id',
        'student_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to EnrollmentRequest model
class EnrollmentRequest extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'student_id',
        'course_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}

// Add to StudentPromotion model
class StudentPromotion extends Model
{
    use BelongsToTenant; // Add this trait

    protected $fillable = [
        'tenant_id', // Add this
        'student_id',
        'from_level_id',
        // ... rest of existing fillable
    ];

    // ... rest of existing code
}
