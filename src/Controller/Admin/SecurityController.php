<?php

namespace CodedMonkey\Conductor\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('@EasyAdmin/page/login.html.twig', [
            'action' => $this->generateUrl('login'),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'username_label' => 'Email',
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on the firewall.');
    }
}
