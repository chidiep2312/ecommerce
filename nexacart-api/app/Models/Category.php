<?php


namespace App\Models;

use App\Enums\CategoryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => CategoryStatus::class,
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            CategoryStatus::Active->value
        );
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    
}
