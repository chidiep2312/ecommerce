<?php

namespace App\Models;

use App\Enums\BrandStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    protected $casts = [
        'status' => BrandStatus::class,
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            BrandStatus::Active->value
        );
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }



}