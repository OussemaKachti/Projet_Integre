<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ValidEmail extends Constraint
{
    public $message = 'The email "{{ value }}" is not a valid email address.';
    public $invalidDomainMessage = 'The email domain must end with a valid top-level domain.';
}