<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddOn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    // ══════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ══════════════════════════════════════════════════════════════
    
    /**
     * Relasi ke transaction details
     */
    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    // ══════════════════════════════════════════════════════════════
    // ACCESSORS & MUTATORS
    // ══════════════════════════════════════════════════════════════
    
    /**
     * Get formatted price for display
     */
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Get usage count
     */
    public function getUsageCountAttribute()
    {
        return $this->transactionDetails()->count();
    }

    /**
     * Get total revenue from this add-on
     */
    public function getTotalRevenueAttribute()
    {
        return $this->transactionDetails()->sum('line_total');
    }

    // ══════════════════════════════════════════════════════════════
    // SCOPES
    // ══════════════════════════════════════════════════════════════
    
    /**
     * Scope untuk add-ons yang sering digunakan
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('transactionDetails')
            ->orderBy('transaction_details_count', 'desc')
            ->limit($limit);
    }

    /**
     * Scope untuk add-ons yang belum pernah digunakan
     */
    public function scopeUnused($query)
    {
        return $query->doesntHave('transactionDetails');
    }

    /**
     * Scope untuk add-ons berdasarkan harga
     */
    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }
}