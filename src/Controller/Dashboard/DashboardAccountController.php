<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use CodedMonkey\Dirigent\Form\AccountFormType;
use CodedMonkey\Dirigent\Form\ChangePasswordFormType;
use CodedMonkey\Dirigent\Form\MfaClearFormType;
use CodedMonkey\Dirigent\Form\MfaSetupFormType;
use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\Color\Color;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardAccountController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
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

        if ($passwordForm->isSubmitted()) {
            $this->validatePassword($passwordForm->get('currentPassword'), $user);

            if ($passwordForm->isValid()) {
                $user->setPlainPassword($passwordForm->get('newPassword')->getData());

                $this->userRepository->save($user, true);

                $this->addFlash('success', 'Your password was successfully updated.');

                return $this->redirectToRoute('dashboard_account');
            }
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
            $form = $this->createForm(MfaClearFormType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $this->validatePassword($form->get('currentPassword'), $user);

                if ($form->isValid()) {
                    $user->setTotpSecret(null);

                    $this->userRepository->save($user, true);

                    $this->addFlash('warning', 'Multi-factor authentication was successfully disabled.');

                    return $this->redirectToRoute('dashboard_account');
                }
            }

            return $this->render('dashboard/account/mfa_clear.html.twig', [
                'form' => $form,
            ]);
        }

        $session = $request->getSession();

        if (null === $totpSecret = $session->get('totp_secret')) {
            $totpSecret = $this->totpAuthenticator->generateSecret();

            $session->set('totp_secret', $totpSecret);
        }

        $user->setTotpSecret($totpSecret);

        $form = $this->createForm(MfaSetupFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $totpCode = $form->get('totpCode')->getData();

            if (!$this->totpAuthenticator->checkCode($user, $totpCode)) {
                $form->get('totpCode')->addError(new FormError('account.error.totp-incorrect'));
            } else {
                // Only validate password if TOTP code is correct
                $this->validatePassword($form->get('currentPassword'), $user);
            }

            if ($form->isValid()) {
                $this->userRepository->save($user, true);

                $this->addFlash('success', 'Multi-factor authentication was successfully enabled.');

                $session->remove('totp_secret');

                return $this->redirectToRoute('dashboard_account');
            }
        }

        return $this->render('dashboard/account/mfa_setup.html.twig', [
            'form' => $form,
            'totpContent' => $this->totpAuthenticator->getQRContent($user),
        ]);
    }

    #[Route('/account/mfa/qr-code', name: 'dashboard_account_mfa_qr_code', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mfaQrCode(Request $request, #[CurrentUser] User $user): Response
    {
        $session = $request->getSession();

        if (null === $totpSecret = $session->get('totp_secret')) {
            throw $this->createAccessDeniedException();
        }

        $darkMode = 'dark' === $request->query->getString('mode', 'light');

        $user->setTotpSecret($totpSecret);

        $builder = new QrCodeBuilder(data: $this->totpAuthenticator->getQRContent($user));
        $result = $builder->build(
            foregroundColor: $darkMode ? new Color(212, 212, 212) : null,
            backgroundColor: $darkMode ? new Color(17, 21, 23) : null,
        );

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }

    private function validatePassword(FormInterface $passwordField, User $user): void
    {
        $currentPassword = $passwordField->getData();

        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            $passwordField->addError(new FormError('account.error.password-incorrect'));
        }
    }
}
