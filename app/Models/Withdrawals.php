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
        'reason',
    ];

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
    public function upi()
{
    return $this->hasOne(Upis::class, 'user_id', 'user_id');
}
public function bankDetails()
{
    return $this->hasOne(WithdrawalBankDetail::class, 'withdrawal_id');
}

}
