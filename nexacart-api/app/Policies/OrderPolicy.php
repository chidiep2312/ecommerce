<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(
        User $user,
        string $ability
    ): ?bool {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(
        User $user,
        Order $order
    ): bool {
        if ($user->isCustomer()) {
            return $order->user_id === $user->id;
        }

        if ($user->isSeller()) {
            return $order->seller_id === $user->id;
        }

        return false;
    }

    public function cancel(
        User $user,
        Order $order
    ): bool {
        return $user->isCustomer()
            && $order->user_id === $user->id
            && $order->status === OrderStatus::Pending;
    }

    public function updateStatus(
        User $user,
        Order $order
    ): bool {
        return $user->isSeller()
            && $order->seller_id === $user->id;
    }
}