<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Controller\Dashboard\DashboardAccountController;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(DashboardAccountController::class)]
class DashboardAccountControllerTest extends WebTestCase
{
    use MockEntityFactoryTrait;
    use WebTestCaseTrait;

    public function testMfaUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/account/mfa');

        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);
    }

    public function testMfaSetup(): void
    {
        $client = static::createClient();
        $totpFactory = $this->getService(TotpFactory::class, 'scheb_two_factor.security.totp_factory');

        $user = $this->createMockUser();
        $this->persistEntities($user);

        $client->loginUser($user);

        $client->request('GET', '/account/mfa');

        $client->submitForm('Enable MFA authentication', [
            'mfa_setup_form[currentPassword]' => 'PlainPassword99',
            'mfa_setup_form[totpCode]' => $totpFactory->createTotpForUser($user)->now(),
        ]);

        $this->assertResponseRedirects('/account', Response::HTTP_FOUND);

        $this->clearEntities();
        $user = $this->getService(EntityManagerInterface::class)->find(User::class, $user->getId());

        $this->assertNotNull($user->getTotpSecret());
    }

    public function testMfaSetupWrongPassword(): void
    {
        $client = static::createClient();
        $totpFactory = $this->getService(TotpFactory::class, 'scheb_two_factor.security.totp_factory');

        $user = $this->createMockUser();
        $this->persistEntities($user);

        $client->loginUser($user);

        $client->request('GET', '/account/mfa');

        $client->submitForm('Enable MFA authentication', [
            'mfa_setup_form[currentPassword]' => 'OddPassword11',
            'mfa_setup_form[totpCode]' => $totpFactory->createTotpForUser($user)->now(),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->clearEntities();
        $user = $this->getService(EntityManagerInterface::class)->find(User::class, $user->getId());

        $this->assertNull($user->getTotpSecret());
    }

    public function testMfaSetupWrongTotpCode(): void
    {
        $client = static::createClient();

        $user = $this->createMockUser();
        $this->persistEntities($user);

        $client->loginUser($user);

        $client->request('GET', '/account/mfa');

        $client->submitForm('Enable MFA authentication', [
            'mfa_setup_form[currentPassword]' => 'PlainPassword99',
            'mfa_setup_form[totpCode]' => 'abcdef',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->clearEntities();
        $user = $this->getService(EntityManagerInterface::class)->find(User::class, $user->getId());

        $this->assertNull($user->getTotpSecret());
    }

    public function testMfaClear(): void
    {
        $client = static::createClient();

        $user = $this->createMockUser(mfaEnabled: true);
        $this->persistEntities($user);

        $client->loginUser($user);

        $client->request('GET', '/account/mfa');

        $client->submitForm('Disable MFA authentication', [
            'mfa_clear_form[currentPassword]' => 'PlainPassword99',
        ]);

        $this->assertResponseRedirects('/account', Response::HTTP_FOUND);

        $this->clearEntities();
        $user = $this->getService(EntityManagerInterface::class)->find(User::class, $user->getId());

        $this->assertNull($user->getTotpSecret());
    }

    public function testMfaClearWrongPassword(): void
    {
        $client = static::createClient();

        $user = $this->createMockUser(mfaEnabled: true);
        $this->persistEntities($user);

        $client->loginUser($user);

        $client->request('GET', '/account/mfa');

        $client->submitForm('Disable MFA authentication', [
            'mfa_clear_form[currentPassword]' => 'OddPassword11',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->clearEntities();
        $user = $this->getService(EntityManagerInterface::class)->find(User::class, $user->getId());

        $this->assertNotNull($user->getTotpSecret());
    }

    public function testMfaQrCode(): void
    {
        $client = static::createClient();
        $this->loginUser();

        // Set TOTP secret to session
        $client->request('GET', '/account/mfa');

        $client->request('GET', '/account/mfa/qr-code');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testMfaQrCodeUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/account/mfa/qr-code');

        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);
    }

    public function testMfaQrCodeNoSetup(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/account/mfa/qr-code');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
