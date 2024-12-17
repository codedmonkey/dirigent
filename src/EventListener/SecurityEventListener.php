<?php

namespace CodedMonkey\Dirigent\EventListener;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Doctrine\Repository\AccessTokenRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

readonly class SecurityEventListener
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
        #[Autowire(service: 'access_token_hasher')]
        private PasswordHasherInterface $accessTokenHasher,
        #[Autowire(param: 'dirigent.security.public_access')]
        private bool $publicAccess,
    ) {
    }

    #[AsEventListener]
    public function checkAccessIsGranted(ControllerEvent $event): void
    {
        if (null !== ($event->getAttributes(IsGrantedAccess::class)[0] ?? null)) {
            if (!$this->publicAccess && !$this->authorizationChecker->isGranted('ROLE_USER')) {
                throw new AccessDeniedException();
            }
        }
    }

    #[AsEventListener]
    public function checkAccessTokenIsValid(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if ($passport->hasBadge(PasswordCredentials::class)) {
            $passwordBadge = $passport->getBadge(PasswordCredentials::class);
            $password = $passwordBadge->getPassword();

            if (!str_starts_with($password, 'dirigent-')) {
                return;
            }

            $accessTokens = $this->accessTokenRepository->findBy([
                'user' => $passport->getUser(),
            ]);

            foreach ($accessTokens as $accessToken) {
                if ($accessToken->isValid() && $this->accessTokenHasher->verify($accessToken->getToken(), $password)) {
                    $passwordBadge->markResolved();
                }
            }
        }
    }
}
