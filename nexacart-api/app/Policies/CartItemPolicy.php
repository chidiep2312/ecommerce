<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function update(
        User $user,
        CartItem $cartItem
    ): bool {
        return $cartItem->cart()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function delete(
        User $user,
        CartItem $cartItem
    ): bool {
        return $cartItem->cart()
            ->where('user_id', $user->id)
            ->exists();
    }
}