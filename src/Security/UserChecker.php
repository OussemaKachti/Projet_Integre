<?php



// src/Security/UserChecker.php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isDisabled()) {
            throw new CustomUserMessageAccountStatusException('Your account has been disabled. Please contact an administrator.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // You can add additional checks after authentication if needed
    }
}