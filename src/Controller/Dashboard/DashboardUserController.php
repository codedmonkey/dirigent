<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Entity\UserRole;
use CodedMonkey\Dirigent\Form\NewPasswordType;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

#[AdminRoute(path: '/users', name: 'users')]
class DashboardUserController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle(Crud::PAGE_INDEX, 'Users')
            ->setDefaultSort(['username' => 'ASC'])
            ->setEntityPermission('ROLE_ADMIN');
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $impersonate = Action::new('impersonate', 'Impersonate')
            ->linkToUrl(fn (User $user): string => $this->generateUrl('dashboard', [
                '_switch_user' => $user->getUserIdentifier(),
            ]));

        return $actions
            ->add(Crud::PAGE_INDEX, $impersonate)
            ->setPermission('impersonate', 'ROLE_ALLOWED_TO_SWITCH');
    }

    #[\Override]
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
        yield ChoiceField::new('role')
            ->setChoices(UserRole::cases())
            ->renderAsBadges([
                UserRole::User->value => 'primary',
                UserRole::Admin->value => 'success',
                UserRole::Owner->value => 'success',
            ])
            ->setSortable(false);
        yield BooleanField::new('totpAuthenticationEnabled', 'Multi-factor authentication')
            ->setHelp('form.user.help.totp-authentication-enabled')
            ->onlyOnForms();
    }
}
