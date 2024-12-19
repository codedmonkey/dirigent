<?php

namespace CodedMonkey\Dirigent\Validator;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniquePackageValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniquePackage) {
            throw new UnexpectedTypeException($constraint, UniquePackage::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Package) {
            throw new UnexpectedValueException($value, Package::class);
        }

        if ($this->packageRepository->findOneByName($value->getName())) {
            $this->context->buildViolation('A package with the name ' . $value->getName() . ' already exists.')
                ->atPath('repositoryUrl')
                ->addViolation();
        }
    }
}
