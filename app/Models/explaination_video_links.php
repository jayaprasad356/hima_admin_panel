<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class explaination_video_links extends Model
{
    use HasFactory;

    protected $fillable = [
        'language',
        'video_link',
    ];

}
