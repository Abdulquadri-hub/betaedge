<?php

namespace App\Models;


use Filament\Panel;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'email',
        'username',
        'password',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'user_type',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // public function canAccessPanel(Panel $panel): bool
    // {
    //     $panelId = $panel->getId();

    //     // if($this->user_type === "admin") {
    //     //     return true;
    //     // }

    //     return $this->user_type === $panelId;
    // }

    public function student(): HasOne {
        return $this->hasOne(Student::class);
    }

    public function instructor(): HasOne {
        return $this->hasOne(Instructor::class);
    }

    public function parent(): HasOne {
        return $this->hasOne(ParentModel::class);
    }

    public function tenants() {

        return $this->belongsToMany(Tenant::class, 'tenant_users')->withPivot(['role', 'permissions', 'status', 'joined_at'])->withTimestamps();
    }

    public function currentTenant() {
        $tenantId = session('active_tenant_id');
        
        if (!$tenantId) return null;

        return $this->tenants()->where('tenant_id', $tenantId)->first();
    }

    public function tenantUsers() {
        return $this->hasMany(TenantUser::class);
    }

    public function activeTenantUsers()
    {
        return $this->tenantUsers()->where('status', 'active');
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function getFullNameAttribute(): string {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getNameAttribute(): string {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type) {
        return $query->where('user_type', $type);
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function isInstructor(): bool
    {
        return $this->user_type === 'instructor';
    }

    public function isStudent(): bool
    {
        return $this->user_type === 'student';
    }

    public function isParent(): bool
    {
        return $this->user_type === 'parent';
    }

    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenants()->where('tenant_id', $tenantId)->exists();
    }

    public function getRoleInTenant(int $tenantId): ?string {
        $tenantUser = $this->tenantUsers()
                          ->where('tenant_id', $tenantId)
                          ->first();

        return $tenantUser?->role;
    }

    public function isOwnerOf(Tenant $tenant): bool {
        return $this->tenantUsers()
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'owner')
                    ->exists();
    }

     public function isInstructorAt(Tenant $tenant): bool {
        return $this->tenantUsers()
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'instructor')
                    ->exists();
    }

    public function canAccessTenant(int $tenantId): bool {
        return $this->tenantUsers()
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->exists();
    }

    public function switchTenant(int $tenantId): bool {
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

    public function getAccessibleTenants() {
        return $this->tenants()
                    ->wherePivot('status', 'active')
                    ->get();
    }

}