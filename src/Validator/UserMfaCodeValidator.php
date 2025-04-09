<?php

namespace CodedMonkey\Dirigent\Validator;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserMfaCodeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TotpAuthenticator $totpAuthenticator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UserMfaCode) {
            throw new UnexpectedTypeException($constraint, UserMfaCode::class);
        }

        if (null === $value || '' === $value) {
            $this->context->buildViolation('code_invalid')
                ->setTranslationDomain('SchebTwoFactorBundle')
                ->addViolation();

            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof TwoFactorInterface) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" class must implement the "%s" interface.', get_debug_type($user), TwoFactorInterface::class));
        }

        if (!$this->totpAuthenticator->checkCode($user, $value)) {
            $this->context->buildViolation('code_invalid')
                ->setTranslationDomain('SchebTwoFactorBundle')
                ->addViolation();
        }
    }
}
