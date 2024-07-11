<?php

namespace CodedMonkey\Conductor\EventListener;

use CodedMonkey\Conductor\Attribute\IsGrantedAccess;
use CodedMonkey\Conductor\Doctrine\Repository\AccessTokenRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

readonly class SecurityEventListener
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepository,
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

    #[AsEventListener]
    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if ($passport->hasBadge(PasswordCredentials::class)) {
            $passwordBadge = $passport->getBadge(PasswordCredentials::class);
            $password = $passwordBadge->getPassword();

            if (!str_starts_with($password, 'conductor-')) {
                return;
            }

            $accessToken = $this->accessTokenRepository->findOneBy([
                'user' => $passport->getUser(),
                'token' => $password,
            ]);

            if (null === $accessToken || !$accessToken->isValid()) {
                return;
            }

            $passwordBadge->markResolved();
        }
    }
}
