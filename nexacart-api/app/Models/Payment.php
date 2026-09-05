<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
     protected $fillable = [
       'order_id',
       'provider',
       'request_id',
       'amount',
       'status',
       'provider_order_id',
       'provider_response_code',
       'paid_at',
       'transaction_id',
       'provider_message'
     
    ];


       public function order()
    {
        return $this->belongsTo(
            Order::class,
            'order_id'
        );
    }

}
