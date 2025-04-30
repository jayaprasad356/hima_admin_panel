<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'coins',
        'datetime',
        'amount',
        'payment_type',
        'reason',
        'status',
    ];

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

}
