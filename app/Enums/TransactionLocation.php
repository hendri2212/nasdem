<?php

namespace App\Enums;

enum TransactionLocation: string
{
    case Cash = 'cash';
    case Bank = 'bank';

    /**
     * Get all enum values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
