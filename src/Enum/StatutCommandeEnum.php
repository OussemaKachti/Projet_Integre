<?php
namespace App\Enum;

enum StatutCommandeEnum: string
{
    case EN_COURS = 'EN_COURS';
    case CONFIRMEE= 'CONFIRMEE';
    case ANNULEE = 'ANNULEE';

    public static function getValues(): array
    {
        return [
            self::EN_COURS->value,
            self::CONFIRMEE->value,
            self::ANNULEE->value,
        ];
    }
}