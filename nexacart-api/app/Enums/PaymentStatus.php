<?php

namespace App\Enums;
enum PaymentStatus: string
{
    case Pending = 'pending';

    case Paid = 'paid';

    case Failed = 'failed';

    case Cancelled = 'cancelled';

    case Refunded = 'refunded';

 public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [
                self::Paid,
                self::Failed,
                self::Cancelled,
            ],

            self::Paid => [
                self::Refunded,
                
            ],
            self::Failed => [
                  self::Paid,
                  self::Cancelled,
            ],

            self::Refunded,
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

    

}

