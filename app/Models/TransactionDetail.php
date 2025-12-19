<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'customer_id',
        'service_id',
        'add_on_id',
        'weight',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'id' => 'integer',
        'transaction_id' => 'integer',
        'customer_id' => 'integer',
        'service_id' => 'integer',
        'add_on_id' => 'integer',
        'quantity' => 'integer',
        'weight' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function addOn()
    {
        return $this->belongsTo(AddOn::class);
    }
}