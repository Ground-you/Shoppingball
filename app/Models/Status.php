<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = [
        'status_group',
        'status_code',
        'status_name',
        'display_order',
    ];
}
