<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Doctrine\Entity\User;
use CodedMonkey\Conductor\Form\NewPasswordType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class DashboardUserController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle(Crud::PAGE_INDEX, 'Users')
            ->setDefaultSort(['username' => 'ASC'])
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureActions(Actions $actions): Actions
    {
        $impersonate = Action::new('impersonate', 'Impersonate')
            ->linkToUrl(function (User $user): string {
                return $this->generateUrl('dashboard', [
                    '_switch_user' => $user->getUserIdentifier(),
                ]);
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $impersonate)
            ->setPermission('impersonate', 'ROLE_ALLOWED_TO_SWITCH');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('username');
        yield TextField::new('name');
        yield EmailField::new('email');
        yield TextField::new('plainPassword')
            ->setLabel('Password')
            ->setFormType(PasswordType::class)
            ->setFormTypeOption('constraints', NewPasswordType::constraints())
            ->onlyOnForms();
        yield ChoiceField::new('roles')
            ->setChoices([
                'User' => 'ROLE_USER',
                'Admin' => 'ROLE_ADMIN',
                'Owner' => 'ROLE_SUPER_ADMIN',
            ])
            ->renderAsBadges([
                'ROLE_USER' => 'primary',
                'ROLE_ADMIN' => 'success',
                'ROLE_SUPER_ADMIN' => 'success',
            ])
            ->renderExpanded()
            ->allowMultipleChoices()
            ->setSortable(false);
    }
}
