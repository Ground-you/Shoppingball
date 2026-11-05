<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seller extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'seller_name',
        'manager_name',
        'contact_phone',
        'business_registration_number',
        'business_address',
        'business_license_file_url',
        'representative_name',
        'settlement_bank_name',
        'settlement_account_number_enc',
        'settlement_account_holder',
        'status_id',
        'reviewed_by_admin_id',
        'reviewed_at',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(SellerMember::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
