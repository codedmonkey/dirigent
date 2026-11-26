<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\AccessToken;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Tests\Helper\EntityManagerTestTrait;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardAccessTokenControllerTest extends WebTestCase
{
    use EntityManagerTestTrait;
    use MockEntityFactoryTrait;
    use WebTestCaseTrait;

    public function testIndex(): void
    {
        $client = static::createClient();
        $user = $this->loginUser();

        $token = $this->createMockAccessToken($user, 'Honey Honey');
        $adminUser = $this->findEntity(User::class, ['username' => 'admin']);
        $adminToken = $this->createMockAccessToken($adminUser, 'Fernando');

        $this->persistEntities($token, $adminToken);

        $client->request('GET', '/access-tokens');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('table', 'Honey Honey');
        $this->assertSelectorTextNotContains('table', 'Fernando', 'The access token list must not contain tokens from other users.');
    }

    public function testNew(): void
    {
        $client = static::createClient();
        $user = $this->loginUser();

        $client->request('GET', '/access-tokens/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Create', ['AccessToken[name]' => 'Honey Honey']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $this->clearEntities();

        $accessToken = $this->findEntity(AccessToken::class, ['name' => 'Honey Honey']);

        self::assertNotNull($accessToken);
        self::assertSame($user->getId(), $accessToken->getUser()->getId());
    }

    public function testDelete(): void
    {
        $client = static::createClient();
        $user = $this->loginUser();

        $token = $this->createMockAccessToken($user, 'Honey Honey');

        $this->persistEntities($token);

        $id = $token->getId();

        $crawler = $client->request('GET', '/access-tokens');
        $this->assertResponseIsSuccessful();
        $csrfToken = $crawler->filter('#action-confirmation-form input[name="token"]')->attr('value');
        $client->request('POST', '/access-tokens/' . $id . '/delete', ['token' => $csrfToken]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $this->clearEntities();

        self::assertNull($this->findEntity(AccessToken::class, $id));
    }

    public function testDeleteAnotherUsersTokenFails(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $adminUser = $this->findEntity(User::class, ['username' => 'admin']);
        $adminToken = $this->createMockAccessToken($adminUser, 'Fernando');

        $this->persistEntities($adminToken);

        $adminTokenId = $adminToken->getId();

        $crawler = $client->request('GET', '/access-tokens');
        $this->assertResponseIsSuccessful();
        $csrfToken = $crawler->filter('#action-confirmation-form input[name="token"]')->attr('value');
        $client->request('POST', "/access-tokens/$adminTokenId/delete", ['token' => $csrfToken]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->clearEntities();

        $savedToken = $this->findEntity(AccessToken::class, $adminTokenId);

        self::assertNotNull($savedToken);
        self::assertSame('Fernando', $savedToken->getName());
        self::assertNull($savedToken->getExpiresAt());
    }

    public function testAnonymousRequestsRequireLogin(): void
    {
        $client = static::createClient();

        foreach (['/access-tokens', '/access-tokens/new'] as $url) {
            $client->request('GET', $url);
            $this->assertResponseRedirects('/login');
        }
    }
}
