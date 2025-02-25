<?php
namespace App\Enum;

enum RoleEnum: string
{
    case NON_MEMBRE = 'nonMembre';
    case MEMBRE = 'membre';
    case PRESIDENT_CLUB = 'presidentClub';
    case ADMINISTRATEUR = 'administrateur';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::getValues(), true);
    }

    public static function fromString(string $role): ?RoleEnum
    {
        foreach (self::cases() as $case) {
            if ($case->value === $role) {
                return $case;
            }
        }
        return null; // Return null if the string doesn't match any enum value
    }
}