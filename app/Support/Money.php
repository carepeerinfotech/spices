<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount, string $currency = 'INR'): string
    {
        $value = number_format((float) $amount, 2);

        return match (strtoupper($currency)) {
            'INR' => '₹'.$value,
            'USD' => '$'.$value,
            default => $currency.' '.$value,
        };
    }
}
