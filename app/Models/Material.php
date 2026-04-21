<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'course_id',
        'title',
        'description',      
        'material_type',    
        'file_url',         
        'file_path',        
        'file_mime_type',
        'file_size_bytes',  
        'display_order',
        'is_published',     
        'view_count',       
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'view_count'      => 'integer',
        'display_order'   => 'integer',
        'is_published'    => 'boolean',
        'published_at'    => 'datetime',
        'metadata'        => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->file_url) return $this->file_url;
        if ($this->file_path) return asset('storage/' . $this->file_path);
        return null;
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size_bytes) return '';
        $kb = $this->file_size_bytes / 1024;
        return $kb >= 1024
            ? round($kb / 1024, 1) . ' MB'
            : round($kb) . ' KB';
    }


    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('material_type', $type);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function isPdf(): bool
    {
        return in_array($this->material_type, ['pdf', 'document']);
    }

    public function isVideo(): bool
    {
        return $this->material_type === 'video';
    }

    public function isLink(): bool
    {
        return $this->material_type === 'link';
    }
}