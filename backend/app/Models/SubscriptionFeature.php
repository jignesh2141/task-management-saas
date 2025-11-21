<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan',
        'feature_key',
        'feature_name',
        'description',
        'is_enabled',
        'limit_value',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Scope a query to only include enabled features.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope a query to only include features for a specific plan.
     */
    public function scopeForPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
    }

    /**
     * Check if feature has a limit.
     */
    public function hasLimit(): bool
    {
        return $this->limit_value !== null;
    }
}
