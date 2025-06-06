<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use CodedMonkey\Dirigent\Form\AccountFormType;
use CodedMonkey\Dirigent\Form\ChangePasswordFormType;
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
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/account', name: 'dashboard_account')]
    #[IsGranted('ROLE_USER')]
    public function account(Request $request, #[CurrentUser] User $user): Response
    {
        $accountForm = $this->createForm(AccountFormType::class, $user);
        $passwordForm = $this->createForm(ChangePasswordFormType::class);

        $accountForm->handleRequest($request);

        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            $this->userRepository->save($user, true);

            $this->addFlash('success', 'Your account was successfully updated.');

            return $this->redirectToRoute('dashboard_account');
        }

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

                return $this->redirectToRoute('dashboard_account');
            }
        }

        return $this->render('dashboard/account.html.twig', [
            'accountForm' => $accountForm,
            'passwordForm' => $passwordForm,
        ]);
    }
}
