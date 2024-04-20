<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Conductor\Doctrine\Repository\RegistryRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DashboardRegistryController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Registry::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Registries')
            ->setDefaultSort(['mirroringPriority' => 'ASC']);
//            ->showEntityActionsInlined();
    }

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

        yield ChoiceField::new('packageMirroring')
            ->setSortable(false)
            ->setRequired(true)
            ->setChoices(RegistryPackageMirroring::cases())
            ->setFormTypeOption('choice_label', function ($choice, string $key): string {
                return "registry.package_mirroring.{$key}";
            })
            ->renderExpanded();
    }

    public function moveUp(AdminContext $context, RegistryRepository $registryRepository): RedirectResponse
    {
        $registry = $context->getEntity()->getInstance();

        $registryRepository->increaseMirroringPriority($registry);

        $url = $this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl();

        return $this->redirect($url);
    }

    public function moveDown(AdminContext $context, RegistryRepository $registryRepository): RedirectResponse
    {
        $registry = $context->getEntity()->getInstance();

        $registryRepository->decreaseMirroringPriority($registry);

        $url = $this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl();

        return $this->redirect($url);
    }
}
