<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\EasyAdmin\DateIntervalField;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[AdminRoute(path: '/registries', name: 'registries')]
class DashboardRegistryController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Registry::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Registries')
            ->setDefaultSort(['mirroringPriority' => 'ASC'])
            ->setEntityPermission('ROLE_ADMIN');
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $upAction = Action::new('moveUp', icon: 'fa fa-arrow-up')
            ->linkToCrudAction('moveUp');
        $downAction = Action::new('moveDown', icon: 'fa fa-arrow-down')
            ->linkToCrudAction('moveDown');

        return $actions
            ->add(Crud::PAGE_INDEX, $downAction)
            ->add(Crud::PAGE_INDEX, $upAction);
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')
            ->setSortable(false);

        yield TextareaField::new('description')
            ->onlyOnForms();

        yield TextField::new('url')
            ->setSortable(false);

        yield AssociationField::new('credentials')
            ->onlyOnForms();

        yield FormField::addPanel('Test');

        yield ChoiceField::new('packageMirroring')
            ->setSortable(false)
            ->setTemplatePath('dashboard/fields/registry_package_mirroring.html.twig')
            ->setRequired(true)
            ->setChoices(RegistryPackageMirroring::cases())
            ->setFormTypeOption('choice_label', static function (RegistryPackageMirroring $choice): string {
                return "registry.package-mirroring.{$choice->value}";
            })
            ->renderExpanded();

        yield DateIntervalField::new('dynamicUpdateDelay')
            ->setFormTypeOptions([
                'with_years' => false,
                'with_months' => false,
                'with_weeks' => false,
                'with_days' => false,
                'with_hours' => true,
                'with_minutes' => true,
            ])
            ->onlyOnForms();
    }

    #[AdminRoute(path: '/{entityId}/move-up', name: 'moveUp')]
    public function moveUp(AdminContext $context, RegistryRepository $registryRepository): RedirectResponse
    {
        $registry = $context->getEntity()->getInstance();

        $registryRepository->increaseMirroringPriority($registry);

        $url = $this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl();

        return $this->redirect($url);
    }

    #[AdminRoute(path: '/{entityId}/move-down', name: 'moveDown')]
    public function moveDown(AdminContext $context, RegistryRepository $registryRepository): RedirectResponse
    {
        $registry = $context->getEntity()->getInstance();

        $registryRepository->decreaseMirroringPriority($registry);

        $url = $this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl();

        return $this->redirect($url);
    }
}
