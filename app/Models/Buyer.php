<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Buyer extends Authenticatable
{
    use SoftDeletes;

    protected $fillable = [
        'login_id',
        'password_hash',
        'buyer_name',
        'nickname',
        'email',
        'phone',
        'marketing_consent',
        'status_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'marketing_consent' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
