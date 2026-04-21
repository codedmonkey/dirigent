<?php

namespace CodedMonkey\Dirigent\Security;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OauthAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'oauth_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $providerName = $request->attributes->get('provider');
        $client = $this->clientRegistry->getClient($providerName);
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $providerName) {
                $remoteUser = $client->fetchUserFromToken($accessToken);
                $data = $remoteUser->toArray();

                $user = $this->entityManager->getRepository(User::class)->findOneBy([
                    'oauthProvider' => $providerName,
                    'oauthSub' => $data['sub'],
                ]);

                if (!$user) {
                    $user = new User();
                    $user->setOauthProvider($providerName);
                    $user->setOauthSub($data['sub']);
                }

                $user->setUsername($data['username']);
                $user->setName($data['name']);
                $user->setEmail($data['email']);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // todo provide user with failure message if it doesn't leak any security details
        return new RedirectResponse($this->router->generate('dashboard_login'));
    }
}
