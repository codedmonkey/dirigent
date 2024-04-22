<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Doctrine\Entity\Credentials;
use CodedMonkey\Conductor\Doctrine\Entity\CredentialsType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DashboardCredentialsController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Credentials::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort(['name' => 'ASC'])
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextareaField::new('description')
            ->onlyOnForms();
        yield ChoiceField::new('type')
            ->setRequired(true)
            ->setChoices(CredentialsType::cases())
            ->setFormTypeOption('choice_label', function ($choice, string $key): string {
                return "credentials.type.{$key}";
            })
            ->renderExpanded();
        yield TextField::new('username')
            ->onlyOnForms();
        yield TextField::new('password')
            ->onlyOnForms();
    }
}
