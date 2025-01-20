<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItems extends Model
{
    protected $fillable = ['cart_id','meal_id', 'count'];

    public function cart()
    {
        $this->belongsTo(Cart::class, 'cart_id');
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class, 'meal_id');
    }
}
