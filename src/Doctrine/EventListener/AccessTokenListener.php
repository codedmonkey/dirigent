<?php

namespace CodedMonkey\Conductor\Doctrine\EventListener;

use CodedMonkey\Conductor\Doctrine\Entity\AccessToken;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

#[AsEntityListener(Events::prePersist, entity: AccessToken::class)]
readonly class AccessTokenListener
{
    public function __construct(
        #[Autowire(service: 'access_token_hasher')]
        private PasswordHasherInterface $accessTokenHasher,
    ) {
    }

    public function prePersist(AccessToken $accessToken): void
    {
        $this->hashToken($accessToken);
    }

    private function hashToken(AccessToken $accessToken): void
    {
        $token = $this->accessTokenHasher->hash($accessToken->getPlainToken());
        $accessToken->hashCredentials($token);
    }
}
