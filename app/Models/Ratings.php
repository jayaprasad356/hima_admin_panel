<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ratings extends Model
{
    protected $fillable = [
        'user_id','call_user_id','ratings','title','description',
    ];
}   
