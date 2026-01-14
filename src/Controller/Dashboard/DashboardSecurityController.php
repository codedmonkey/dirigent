<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use CodedMonkey\Dirigent\Entity\UserRole;
use CodedMonkey\Dirigent\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class DashboardSecurityController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/login', name: 'dashboard_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->userRepository->noUsers()) {
            return $this->redirectToRoute('dashboard_register');
        }

        return $this->render('@EasyAdmin/page/login.html.twig', [
            'action' => $this->generateUrl('dashboard_login'),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'forgot_password_enabled' => true,
            'forgot_password_path' => $this->generateUrl('dashboard_reset_password_request'),
            'remember_me_enabled' => true,
        ]);
    }

    #[Route('/register', name: 'dashboard_register')]
    public function register(Request $request, Security $security): Response
    {
        $registrationEnabled = $this->getParameter('dirigent.security.registration_enabled');
        $noUsers = $this->userRepository->noUsers();

        // Redirect to the homepage page if registration is disabled, but continue if there are no users yet
        if (!$registrationEnabled && !$noUsers) {
            return $this->redirectToRoute('dashboard');
        }

        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // The first user gets owner privileges
            if ($noUsers) {
                $user->setRole(UserRole::Owner);
            }

            $this->userRepository->save($user, true);

            // Automatically authenticate the user after registration
            return $security->login($user, 'security.authenticator.form_login.main', 'main');
        }

        return $this->render('dashboard/security/register.html.twig', [
            'form' => $form,
        ]);
    }
}
