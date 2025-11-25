<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $table = 'expenses';

    protected $fillable = [
        'user_id',
        'expenses_category_id',
        'amount',
        'payment_method',
        'description',
        'date',
        'ref_no',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationship dengan User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship dengan ExpenseCategory
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expenses_category_id');
    }

    // Accessor untuk format amount
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }

    // Scope untuk filter by user
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope untuk filter by category
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('expenses_category_id', $categoryId);
    }

    // Scope untuk filter by date range
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}