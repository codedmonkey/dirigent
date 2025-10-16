<?php

namespace CodedMonkey\Dirigent\Validator;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword as BaseConstraint;

class UserPassword extends BaseConstraint
{
    public string $message = 'The password is incorrect';
}
