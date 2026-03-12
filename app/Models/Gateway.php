<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'priority',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
