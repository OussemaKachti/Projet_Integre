<?php
namespace App\Enum;

enum RoleEnum: string
{
    case NON_MEMBRE = 'nonMembre';
    case MEMBRE = 'membre';
    case PRESIDENT_CLUB = 'presidentClub';
    case ADMINISTRATEUR = 'administrateur';
    case USER = 'user';

    public static function getValues(): array
    {
        return [
            self::NON_MEMBRE->value,
            self::MEMBRE->value,
            self::PRESIDENT_CLUB->value,
            self::ADMINISTRATEUR->value,
            self::USER->value,
        ];
    }
}