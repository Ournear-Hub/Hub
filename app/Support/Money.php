<?php

namespace App\Support;

final class Money
{
    public static function of(mixed $value): string
    {
        return bcadd(is_numeric($value) ? (string) $value : '0', '0', 2);
    }

    public static function add(string $left, string $right): string
    {
        return bcadd(self::of($left), self::of($right), 2);
    }

    public static function sub(string $left, string $right): string
    {
        return bcsub(self::of($left), self::of($right), 2);
    }

    public static function gstAmount(string $amount, string $rate): string
    {
        return bcmul(self::of($amount), bcdiv(self::of($rate), '100', 6), 2);
    }

    public static function percent(string $amount, string $rate): string
    {
        return self::gstAmount($amount, $rate);
    }

    public static function cmp(string $left, string $right): int
    {
        return bccomp(self::of($left), self::of($right), 2);
    }
}
