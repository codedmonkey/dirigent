<?php

namespace CodedMonkey\Dirigent\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class OauthController extends AbstractController
{
    #[Route('/oauth/connect/{provider}', name: 'oauth_connect')]
    public function connect(ClientRegistry $clientRegistry, string $provider): RedirectResponse
    {
        return $clientRegistry
            ->getClient($provider)
            ->redirect(scopes: [], options: []);
    }

    #[Route('/oauth/check/{provider}', name: 'oauth_check')]
    public function check(): never
    {
        throw new \RuntimeException('should not be reached - handled by OAuth authenticator instead');
    }
}
