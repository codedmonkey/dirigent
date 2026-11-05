<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\AccessToken;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminRoute(path: '/access-tokens', name: 'access_tokens')]
#[IsGranted('ROLE_USER')]
class DashboardAccessTokenController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AccessToken::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Access token')
            ->setEntityLabelInPlural('Access tokens')
            ->overrideTemplate('crud/index', 'dashboard/access_token/index.html.twig');
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $ownsToken = new Expression('object.getUser() != null and object.getUser().getId() === user.getId()');

        return $actions
            ->setPermission(Action::DELETE, $ownsToken)
            ->disable(Action::DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield DateTimeField::new('expiresAt');
    }

    #[\Override]
    public function createEntity(string $entityFqcn): ?object
    {
        /** @var User $user */
        $user = $this->getUser();

        return new AccessToken($user);
    }

    #[\Override]
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        /** @var User $user */
        $user = $this->getUser();

        $builder = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $builder->andWhere($builder->expr()->eq('entity.user', $user->getId()));

        return $builder;
    }

    #[AsEventListener(event: BeforeEntityPersistedEvent::class)]
    public function beforeEntityPersisted(BeforeEntityPersistedEvent $event): void
    {
        $accessToken = $event->getEntityInstance();

        if (!$accessToken instanceof AccessToken) {
            return;
        }

        $this->addFlash('access-token', $accessToken->getPlainToken());
    }
}
