<?php
namespace App\Enum;

enum StatutClubEnum: string
{
    case EN_ATTENTE = 'en_attente';
    case ACCEPTE = 'accepte';
    case REFUSE = 'refuse';
    case ACTIVE = 'active';

    public static function getValues(): array
    {
        return [
            self::EN_ATTENTE->value,
            self::ACCEPTE->value,
            self::REFUSE->value,
            self::ACTIVE->value,
        ];
    }
}