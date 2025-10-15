<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\EasyAdmin;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;

class MfaAuthenticationConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return User::class === $entityDto->getFqcn() && 'totpAuthenticationEnabled' === $field->getProperty();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        /** @var User $user */
        $user = $entityDto->getInstance();

        if (!$user->isTotpAuthenticationEnabled()) {
            $field->setFormTypeOption('disabled', true);
        }
    }
}
