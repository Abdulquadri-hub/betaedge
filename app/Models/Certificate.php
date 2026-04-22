<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'batch_id',
        'certificate_code',
        'final_grade',
        'grade_letter',
        'batch_rank',
        'total_students',
        'attendance_count',
        'total_sessions',
        'issued_at',
        'issued_by',
    ];

    protected $casts = [
        'final_grade'     => 'decimal:2',
        'batch_rank'      => 'integer',
        'total_students'  => 'integer',
        'attendance_count'=> 'integer',
        'total_sessions'  => 'integer',
        'issued_at'       => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }


    public function getAttendanceRateAttribute(): ?float
    {
        if (!$this->total_sessions) return null;
        return round(($this->attendance_count / $this->total_sessions) * 100, 1);
    }

    public function getVerificationUrlAttribute(): string
    {
        return url("/verify/{$this->certificate_code}");
    }

    public static function generateCode(Batch $batch, Tenant $tenant): string
    {
        $schoolInitials = strtoupper(
            implode('', array_map(fn ($w) => $w[0], explode(' ', $tenant->name)))
        );
        $schoolInitials = substr(preg_replace('/[^A-Z]/', '', $schoolInitials), 0, 4);

        $month = now()->format('MY'); 
        $seq   = str_pad(
            self::where('batch_id', $batch->id)->count() + 1,
            3, '0', STR_PAD_LEFT
        );

        return "{$schoolInitials}-{$seq}-{$month}";
    }
}