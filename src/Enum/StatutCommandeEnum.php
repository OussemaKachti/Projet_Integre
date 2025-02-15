<?php
namespace App\Enum;

enum StatutCommandeEnum: string
{
    case EN_COURS = 'en_cours';
    case CONFIRMEE= 'confirmee';
    case ANNULEE = 'annulee';

    public static function getValues(): array
    {
        return [
            self::EN_COURS->value,
            self::CONFIRMEE->value,
            self::ANNULEE->value,
        ];
    }
}