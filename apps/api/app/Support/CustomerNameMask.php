<?php

namespace App\Support;

final class CustomerNameMask
{
    public static function public(?string $name): string
    {
        $tokens = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($tokens) || $tokens === []) {
            return '?';
        }

        $last = (string) array_pop($tokens);
        $maskedLast = mb_substr($last, 0, 1).'.';
        if ($tokens === []) {
            return $maskedLast;
        }

        return implode(' ', $tokens).' '.$maskedLast;
    }
}
