<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceClick extends Model
{
    protected $fillable = [
        'listing_id',
        'user_id',
        'clicked_at',
        'user_agent',
        'ip_address',
        'referrer',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public $timestamps = false;

    // Relationships
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
