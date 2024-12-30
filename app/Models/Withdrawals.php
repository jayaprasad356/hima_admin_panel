<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawals extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'datetime',
        'status',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(users::class, 'user_id');
    }
}
