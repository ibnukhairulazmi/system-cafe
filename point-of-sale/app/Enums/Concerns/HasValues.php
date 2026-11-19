<?php

namespace App\Enums\Concerns;

trait HasValues
{
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
