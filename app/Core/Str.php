<?php
declare(strict_types=1);

namespace App\Core;

final class Str
{
    /** Tronca a $max caratteri (usa mbstring se disponibile, altrimenti byte). */
    public static function cut(string $value, int $max): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
    }
}
