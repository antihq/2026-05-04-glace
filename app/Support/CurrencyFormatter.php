<?php

namespace App\Support;

class CurrencyFormatter
{
    public static function cents(int $cents): string
    {
        $dollars = abs($cents / 100);

        return ($cents < 0 ? '-' : '').'$'.number_format($dollars, 2);
    }

    public static function percent(float $percent): string
    {
        return ($percent > 0 ? '+' : '').number_format($percent, 2).'%';
    }

    public static function deltaCents(int $cents): string
    {
        return ($cents > 0 ? '+' : '').self::cents($cents);
    }
}
