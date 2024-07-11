<?php

namespace CodedMonkey\Conductor\EventListener;

use CodedMonkey\Conductor\Attribute\IsGrantedAccess;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class SecurityEventListener
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        #[Autowire(param: 'conductor.security.public_access')]
        private bool $publicAccess,
    ) {
    }

    #[AsEventListener]
    public function onKernelController(ControllerEvent $event): void
    {
        if (null !== ($event->getAttributes(IsGrantedAccess::class)[0] ?? null)) {
            if (!$this->publicAccess && !$this->authorizationChecker->isGranted('ROLE_USER')) {
                throw new AccessDeniedException();
            }
        }
    }
}
