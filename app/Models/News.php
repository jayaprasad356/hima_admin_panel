<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'privacy_policy','support_mail','demo_video','minimum_withdrawals','payment_gateway_type','auto_disable_info','coins_per_referral','money_per_referral','terms_conditions','refund_cancellation'
    ];
}   
