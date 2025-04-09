<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use CodedMonkey\Dirigent\Form\AccountFormType;
use CodedMonkey\Dirigent\Form\AccountMfaFormType;
use CodedMonkey\Dirigent\Form\ChangePasswordFormType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardAccountController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            AdminUrlGenerator::class => AdminUrlGenerator::class,
        ]);
    }

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    #[Route('/dashboard/account', name: 'dashboard_account')]
    #[IsGranted('ROLE_USER')]
    public function account(Request $request, #[CurrentUser] User $user): Response
    {
        $accountForm = $this->createForm(AccountFormType::class, $user);
        $accountForm->handleRequest($request);

        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            $this->userRepository->save($user, true);

            $this->addFlash('success', 'Your account was successfully updated.');

            $url = $this->container->get(AdminUrlGenerator::class)->setRoute('dashboard_account')->generateUrl();

            return $this->redirect($url);
        }

        $passwordForm = $this->createForm(ChangePasswordFormType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();

            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $passwordForm->get('currentPassword')->addError(new FormError('Your current password is incorrect.'));
            }

            if ($passwordForm->isValid()) {
                $user->setPlainPassword($passwordForm->get('newPassword')->getData());

                $this->userRepository->save($user, true);

                $this->addFlash('success', 'Your password was successfully updated.');

                $url = $this->container->get(AdminUrlGenerator::class)->setRoute('dashboard_account')->generateUrl();

                return $this->redirect($url);
            }
        }

        return $this->render('dashboard/account.html.twig', [
            'accountForm' => $accountForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route('/dashboard/account/mfa', name: 'dashboard_account_mfa')]
    #[IsGranted('ROLE_USER')]
    public function mfa(Request $request, #[CurrentUser] User $user): Response
    {
        if (!$user->isTotpAuthenticationEnabled()) {
            $session = $request->getSession();

            if (null === $totpSecret = $session->get('totp_secret')) {
                $totpSecret = $this->totpAuthenticator->generateSecret();

                $session->set('totp_secret', $totpSecret);
            }

            $form = $this->createForm(AccountMfaFormType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $currentPassword = $form->get('currentPassword')->getData();

                if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $form->get('currentPassword')->addError(new FormError('Your current password is incorrect.'));
                }

                $user->setTotpSecret($totpSecret);

                $totpCode = $form->get('totpCode')->getData();

                if (!$this->totpAuthenticator->checkCode($user, $totpCode)) {
                    $user->setTotpSecret(null);

                    $form->get('totpCode')->addError(new FormError('The verification code is incorrect.'));
                }

                if ($form->isValid()) {
                    $this->userRepository->save($user, true);

                    $this->addFlash('success', 'Multi-factor authentication was successfully enabled.');

                    $session->remove('totp_secret');

                    $url = $this->container->get(AdminUrlGenerator::class)->setRoute('dashboard_account')->generateUrl();

                    return $this->redirect($url);
                }
            }

            return $this->render('dashboard/account/mfa.html.twig', [
                'form' => $form,
                'totpSecret' => $totpSecret,
            ]);
        }
    }
}
