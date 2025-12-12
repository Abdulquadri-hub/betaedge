<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAttendance extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [ 
        'tenant_id', 'instructor_id', 'date', 'clock_in', 'clock_out', 'status'
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function instructor(): BelongsTo {
        return $this->belongsTo(Instructor::class);
    }

    public function scopeAbsent() {
        return $this->where('status', 'absent');
    }

    public function scopePresent() {
        return $this->where('status', 'present');
    }

    public function clockIn(): void {
        $this->update([
            'clock_in' => now(),
            'status' => 'present'
        ]);
    }

    public function clockOut(): void {
        $this->update([
            'clock_out' => now(),
            'status' => 'absent'
        ]);
    }
}
