<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\RegistryPackageMirroring;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DashboardRegistryController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Registry::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextareaField::new('description')
            ->onlyOnForms();

        yield TextField::new('url');

        yield AssociationField::new('credentials')
            ->onlyOnForms();

        yield ChoiceField::new('packageMirroring')
            ->setRequired(true)
            ->setChoices(RegistryPackageMirroring::cases())
            ->setFormTypeOption('choice_label', function ($choice, string $key): string {
                return "registry.package_mirroring.{$key}";
            })
            ->renderExpanded();
    }
}