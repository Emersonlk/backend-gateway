<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'gateway_id',
        'external_id',
        'status',
        'amount',
        'card_last_numbers',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'transaction_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
