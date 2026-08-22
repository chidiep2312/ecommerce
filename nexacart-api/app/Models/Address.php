<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_name',
        'phone',
        'province',
        'district',
        'ward',
        'address_line',
        'is_default',
        'province_id',
        'district_id',
        'ward_code'

    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function getFullAddressAttribute():
        string {
        return implode(
            ', ',
            array_filter([
                $this->address_line,
                $this->ward,
                $this->district,
                $this->province,
            ])
        );
    }
}