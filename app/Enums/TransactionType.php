<?php

namespace App\Enums;

enum TransactionType: string
{
    case Debit = 'debit';
    case Credit = 'credit';

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
