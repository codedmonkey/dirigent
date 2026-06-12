<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Controller\Dashboard\DashboardResetPasswordController;
use CodedMonkey\Dirigent\Doctrine\Entity\ResetPasswordRequest;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Tests\Helper\EntityManagerTestTrait;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(DashboardResetPasswordController::class)]
class DashboardResetPasswordControllerTest extends WebTestCase
{
    use EntityManagerTestTrait;
    use MockEntityFactoryTrait;
    use WebTestCaseTrait;

    public function testResetPassword(): void
    {
        $client = static::createClient();

        $user = $this->createMockUser();
        $user->setEmail(sprintf('%s@example.com', $user->getUsername()));
        $this->persistEntities($user);

        $client->request('GET', '/reset-password');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Send email', [
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);

        $this->assertResponseRedirects('/reset-password/sent', Response::HTTP_FOUND);
        $this->assertQueuedEmailCount(1);

        /** @var Email $email */
        $email = $this->getMailerMessage();

        $this->assertEmailAddressContains($email, 'to', $user->getEmail());
        $this->assertEmailHtmlBodyContains($email, '/reset-password/reset/');

        $this->assertNotNull($this->findEntity(ResetPasswordRequest::class, ['user' => $user->getId()]));

        preg_match('#/reset-password/reset/[\w-]+#', $email->getHtmlBody(), $matches);
        $resetUrl = $matches[0];

        $client->request('GET', $resetUrl);

        $this->assertResponseRedirects('/reset-password/reset', Response::HTTP_FOUND);

        $client->followRedirect();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Reset', [
            'reset_password_form[plainPassword][first]' => 'BrandNewPassword42',
            'reset_password_form[plainPassword][second]' => 'BrandNewPassword42',
        ]);

        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);

        $this->clearEntities();
        $user = $this->findEntity(User::class, $user->getId());
        $passwordHasher = $this->getService(UserPasswordHasherInterface::class, 'security.user_password_hasher');

        $this->assertTrue($passwordHasher->isPasswordValid($user, 'BrandNewPassword42'));
        $this->assertNull($this->findEntity(ResetPasswordRequest::class, ['user' => $user->getId()]));
    }

    public function testResetPasswordWithMismatchedPasswords(): void
    {
        $client = static::createClient();

        $user = $this->createMockUser();
        $user->setEmail(sprintf('%s@example.com', $user->getUsername()));
        $this->persistEntities($user);

        $client->request('GET', '/reset-password');

        $client->submitForm('Send email', [
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);

        /** @var Email $email */
        $email = $this->getMailerMessage();

        preg_match('#/reset-password/reset/[\w-]+#', $email->getHtmlBody(), $matches);
        $resetUrl = $matches[0];

        $client->request('GET', $resetUrl);
        $client->followRedirect();

        $crawler = $client->submitForm('Reset', [
            'reset_password_form[plainPassword][first]' => 'BrandNewPassword42',
            'reset_password_form[plainPassword][second]' => 'AnotherPassword11',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('The password fields must match', $crawler->filter('.invalid-feedback')->text());

        $this->clearEntities();
        $user = $this->findEntity(User::class, $user->getId());
        $passwordHasher = $this->getService(UserPasswordHasherInterface::class, 'security.user_password_hasher');

        $this->assertTrue($passwordHasher->isPasswordValid($user, 'PlainPassword99'));
        $this->assertNotNull($this->findEntity(ResetPasswordRequest::class, ['user' => $user->getId()]));
    }

    public function testRequestPasswordResetForUnknownEmail(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reset-password');

        $client->submitForm('Send email', [
            'reset_password_request_form[email]' => 'unknown@example.com',
        ]);

        $this->assertResponseRedirects('/reset-password/sent', Response::HTTP_FOUND);
        $this->assertQueuedEmailCount(0);

        $client->followRedirect();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testSentPageWithoutResetToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reset-password/sent');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reset-password/reset/' . str_repeat('a', 40));

        $this->assertResponseRedirects('/reset-password/reset', Response::HTTP_FOUND);

        $client->followRedirect();

        $this->assertResponseRedirects('/reset-password', Response::HTTP_FOUND);

        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertCount(1, $crawler->filter('.alert-danger'));
    }

    public function testResetPasswordWithoutToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reset-password/reset');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
