<?php



// src/Security/UserChecker.php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() !== User::STATUS_ACTIVE) {
            // Throw an exception with a custom message
            throw new CustomUserMessageAuthenticationException('Your account has been deactivated. Please contact administration.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Update last login time
        $user->updateLastLogin();
    }
}