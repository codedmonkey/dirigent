<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\Credentials;
use CodedMonkey\Dirigent\Doctrine\Entity\CredentialsType;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: '/credentials', name: 'credentials')]
class DashboardCredentialsController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Credentials::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort(['name' => 'ASC'])
            ->setEntityPermission('ROLE_ADMIN')
            ->overrideTemplate('layout', 'dashboard/credentials/layout.html.twig');
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextareaField::new('description')
            ->onlyOnForms();
        yield ChoiceField::new('type')
            ->setTemplatePath('dashboard/fields/credentials_type.html.twig')
            ->setRequired(true)
            ->setChoices(CredentialsType::cases())
            ->setFormTypeOption('choice_label', static function (CredentialsType $choice): string {
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
