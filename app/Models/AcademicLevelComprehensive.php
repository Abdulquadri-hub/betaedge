<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * AcademicLevel Model
 *
 * Represents academic levels/grades in the system (e.g., Grade 9, Grade 10, etc.)
 */
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

    /**
     * Get all courses for this academic level
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get all active courses for this level
     */
    public function activeCourses(): HasMany
    {
        return $this->courses()->where('status', 'active');
    }

    /**
     * Get all students in this academic level
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get active students in this level
     */
    public function activeStudents(): HasMany
    {
        return $this->students()->where('enrollment_status', 'active');
    }

    /**
     * Scope to get active levels only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Get the next academic level
     */
    public function getNextLevel(): ?self
    {
        return self::where('tenant_id', $this->tenant_id)
            ->where('level_number', $this->level_number + 1)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the previous academic level
     */
    public function getPreviousLevel(): ?self
    {
        return self::where('tenant_id', $this->tenant_id)
            ->where('level_number', $this->level_number - 1)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get total number of students in this level
     */
    public function getStudentCount(): int
    {
        return $this->students()->count();
    }

    /**
     * Get total number of active students
     */
    public function getActiveStudentCount(): int
    {
        return $this->activeStudents()->count();
    }

    /**
     * Get total number of courses
     */
    public function getCourseCount(): int
    {
        return $this->courses()->count();
    }

    /**
     * Check if this is the highest level
     */
    public function isHighestLevel(): bool
    {
        return !$this->getNextLevel();
    }

    /**
     * Check if this is the lowest level
     */
    public function isLowestLevel(): bool
    {
        return !$this->getPreviousLevel();
    }
}
