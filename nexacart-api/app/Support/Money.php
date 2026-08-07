<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    private const SCALE = 2;

    public static function zero(): string
    {
        return '0.00';
    }

    public static function add(
        string|int $left,
        string|int $right
    ): string {
        return bcadd(
            (string) $left,
            (string) $right,
            self::SCALE
        );
    }

    public static function subtract(
        string|int $left,
        string|int $right
    ): string {
        return bcsub(
            (string) $left,
            (string) $right,
            self::SCALE
        );
    }

    public static function subtractFloorZero(
        string|int $left,
        string|int $right
    ): string {
        $result = self::subtract(
            $left,
            $right
        );

        if (
            bccomp(
                $result,
                self::zero(),
                self::SCALE
            ) < 0
        ) {
            return self::zero();
        }

        return $result;
    }

    public static function multiply(
        string|int $amount,
        int $quantity
    ): string {
        if ($quantity < 0) {
            throw new InvalidArgumentException(
                'Quantity cannot be negative.'
            );
        }

        return bcmul(
            (string) $amount,
            (string) $quantity,
            self::SCALE
        );
    }

    public static function compare(
        string|int $left,
        string|int $right
    ): int {
        return bccomp(
            (string) $left,
            (string) $right,
            self::SCALE
        );
    }

    public static function min(
        string|int $left,
        string|int $right
    ): string {
        return self::compare(
            $left,
            $right
        ) <= 0
            ? (string) $left
            : (string) $right;
    }
}