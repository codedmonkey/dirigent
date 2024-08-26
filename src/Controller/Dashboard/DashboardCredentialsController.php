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
            ->setEntityPermission('ROLE_ADMIN')
            ->overrideTemplate('layout', 'dashboard/credentials/layout.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextareaField::new('description')
            ->onlyOnForms();
        yield ChoiceField::new('type')
            ->setTemplatePath('dashboard/fields/credentials_type.html.twig')
            ->setRequired(true)
            ->setChoices(CredentialsType::cases())
            ->setFormTypeOption('choice_label', function (CredentialsType $choice): string {
                return "credentials.type.{$choice->value}";
            })
            ->renderExpanded();
        yield TextField::new('username')
            ->setFormTypeOption('row_attr', ['data-credentials-field' => 'username'])
            ->onlyOnForms();
        yield TextField::new('password')
            ->setFormTypeOption('row_attr', ['data-credentials-field' => 'password'])
            ->onlyOnForms();
        yield TextField::new('token')
            ->setFormTypeOption('row_attr', ['data-credentials-field' => 'token'])
            ->onlyOnForms();
    }
}
