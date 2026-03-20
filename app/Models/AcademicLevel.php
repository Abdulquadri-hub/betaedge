<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademicLevel extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'level_number',
        'description',
        'is_active',
    ];

    protected $casts = [
        'level_number' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function activeCourses(): HasMany
    {
        return $this->courses()->where('status', 'active');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function activeStudents(): HasMany
    {
        return $this->students()->where('enrollment_status', 'active');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    public function getNextLevel(): ?self
    {
        return self::where('tenant_id', $this->tenant_id)
            ->where('level_number', $this->level_number + 1)
            ->where('is_active', true)
            ->first();
    }

    public function getPreviousLevel(): ?self
    {
        return self::where('tenant_id', $this->tenant_id)
            ->where('level_number', $this->level_number - 1)
            ->where('is_active', true)
            ->first();
    }

    public function getStudentCount(): int
    {
        return $this->students()->count();
    }

    public function getActiveStudentCount(): int
    {
        return $this->activeStudents()->count();
    }

    public function getCourseCount(): int
    {
        return $this->courses()->count();
    }

    public function isHighestLevel(): bool
    {
        return !$this->getNextLevel();
    }

    public function isLowestLevel(): bool
    {
        return !$this->getPreviousLevel();
    }
}
