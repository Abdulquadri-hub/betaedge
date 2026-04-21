<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Enrollment extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'batch_id',
        'course_id',              
        'enrollment_route',       
        'enrollment_payment_id',  
        'enrollment_date',
        'status',                 
        'progress_percentage',
        'final_grade',
        'grade_letter',
        'notes',
    ];

    protected $casts = [
        'enrollment_date'    => 'datetime',
        'progress_percentage'=> 'decimal:2',
        'final_grade'        => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(EnrollmentPayment::class, 'enrollment_payment_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'batch_id', 'batch_id')
            ->where('student_id', $this->student_id);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->payment?->isCompleted() ?? false;
    }

    public function markActive(): void
    {
        $this->update(['status' => 'active', 'enrollment_date' => $this->enrollment_date ?? now()]);
    }

    public function markCompleted(float $finalGrade): void
    {
        $letter = match(true) {
            $finalGrade >= 90 => 'A',
            $finalGrade >= 80 => 'B',
            $finalGrade >= 70 => 'C',
            $finalGrade >= 60 => 'D',
            default           => 'F',
        };

        $this->update([
            'status'      => 'completed',
            'final_grade' => $finalGrade,
            'grade_letter'=> $letter,
        ]);
    }
}