<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Form\ResetPasswordFormType;
use CodedMonkey\Dirigent\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class DashboardResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
    ) {
    }

    #[Route('/reset-password', name: 'dashboard_reset_password_request')]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail($form->get('email')->getData());
        }

        return $this->render('dashboard/reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/sent', name: 'dashboard_reset_password_sent')]
    public function sent(): Response
    {
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('dashboard/reset_password/sent.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    #[Route('/reset-password/reset/{token}', name: 'dashboard_reset_password')]
    public function passwordReset(Request $request, ?string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('dashboard_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $exception) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                $exception->getReason()
            ));

            return $this->redirectToRoute('dashboard_reset_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            $user->setPlainPassword($form->get('plainPassword')->getData());
            $this->entityManager->flush();

            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('dashboard_login');
        }

        return $this->render('dashboard/reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $email): RedirectResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        if ($user) {
            try {
                $resetToken = $this->resetPasswordHelper->generateResetToken($user);

                $email = (new TemplatedEmail())
                    ->from(new Address('i@e.x', 'Example'))
                    ->to($user->getEmail())
                    ->subject('Your password reset request')
                    ->htmlTemplate('email/reset_password.html.twig')
                    ->context([
                        'resetToken' => $resetToken,
                    ]);

                $this->mailer->send($email);

                $this->setTokenObjectInSession($resetToken);
            } catch (ResetPasswordExceptionInterface $exception) {
            }
        }

        return $this->redirectToRoute('dashboard_reset_password_sent');
    }
}
