<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipping = 'shipping';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * @return array<OrderStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [
                self::Confirmed,
                self::Cancelled,
            ],

            self::Confirmed => [
                self::Shipping,
                self::Cancelled,
            ],

            self::Shipping => [
                self::Completed,
            ],

            self::Completed,
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(
        self $newStatus
    ): bool {
        return in_array(
            $newStatus,
            $this->allowedTransitions(),
            true
        );
    }

    public function isFinal(): bool
    {
        return in_array(
            $this,
            [
                self::Completed,
                self::Cancelled,
            ],
            true
        );
    }
}