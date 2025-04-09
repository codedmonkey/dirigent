<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use CodedMonkey\Dirigent\Form\AccountFormType;
use CodedMonkey\Dirigent\Form\ChangePasswordFormType;
use CodedMonkey\Dirigent\Form\MfaClearFormType;
use CodedMonkey\Dirigent\Form\MfaSetupFormType;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardAccountController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    #[Route('/account', name: 'dashboard_account')]
    #[IsGranted('ROLE_USER')]
    public function account(Request $request, #[CurrentUser] User $user): Response
    {
        $accountForm = $this->createForm(AccountFormType::class, $user);
        $accountForm->handleRequest($request);

        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            $this->userRepository->save($user, true);

            $this->addFlash('success', 'Your account was successfully updated.');

            return $this->redirectToRoute('dashboard_account');
        }

        $passwordForm = $this->createForm(ChangePasswordFormType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $user->setPlainPassword($passwordForm->get('newPassword')->getData());

            $this->userRepository->save($user, true);

            $this->addFlash('success', 'Your password was successfully updated.');

            return $this->redirectToRoute('dashboard_account');
        }

        return $this->render('dashboard/account/account.html.twig', [
            'accountForm' => $accountForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route('/account/mfa', name: 'dashboard_account_mfa')]
    #[IsGranted('ROLE_USER')]
    public function mfa(Request $request, #[CurrentUser] User $user): Response
    {
        if ($user->isTotpAuthenticationEnabled()) {
            return $this->clearMfa($request, $user);
        }

        return $this->setupMfa($request, $user);
    }

    private function setupMfa(Request $request, User $user): Response
    {
        $session = $request->getSession();

        if (null === $totpSecret = $session->get('totp_secret')) {
            $totpSecret = $this->totpAuthenticator->generateSecret();

            $session->set('totp_secret', $totpSecret);
        }

        $user->setTotpSecret($totpSecret);

        $form = $this->createForm(MfaSetupFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->save($user, true);

            $this->addFlash('success', 'Multi-factor authentication was successfully enabled.');

            $session->remove('totp_secret');

            return $this->redirectToRoute('dashboard_account');
        }

        return $this->render('dashboard/account/mfa_setup.html.twig', [
            'form' => $form,
            'totpContent' => $this->totpAuthenticator->getQRContent($user),
        ]);
    }

    public function clearMfa(Request $request, User $user): Response
    {
        $form = $this->createForm(MfaClearFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpSecret(null);

            $this->userRepository->save($user, true);

            $this->addFlash('warning', 'Multi-factor authentication was successfully disabled.');

            return $this->redirectToRoute('dashboard_account');
        }

        return $this->render('dashboard/account/mfa_clear.html.twig', [
            'form' => $form,
        ]);
    }
}
