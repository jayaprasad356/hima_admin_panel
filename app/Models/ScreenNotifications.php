<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenNotifications extends Model
{
    protected $fillable = [
        'title',
        'description',
        'datetime',
        'gender',
        'language',
    ];

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
} 
