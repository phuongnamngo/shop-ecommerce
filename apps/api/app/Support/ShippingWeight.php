<?php

namespace App\Support;

final class ShippingWeight
{
    public static function grams(?int $weightGrams): int
    {
        if ($weightGrams === null || $weightGrams <= 0) {
            return (int) config('commerce.default_weight_grams', 500);
        }

        return $weightGrams;
    }
}
