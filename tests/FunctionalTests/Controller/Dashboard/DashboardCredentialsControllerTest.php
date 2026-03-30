<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Controller\Dashboard\DashboardCredentialsController;
use CodedMonkey\Dirigent\Doctrine\Entity\Credentials;
use CodedMonkey\Dirigent\Doctrine\Entity\CredentialsType;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(DashboardCredentialsController::class)]
class DashboardCredentialsControllerTest extends WebTestCase
{
    use MockEntityFactoryTrait;
    use WebTestCaseTrait;

    public function testCreate(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $client->request('GET', '/credentials/new');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Create', [
            'Credentials[name]' => 'Test credentials',
            'Credentials[type]' => CredentialsType::HttpBasic->value,
            'Credentials[username]' => 'testuser',
            'Credentials[password]' => 'testpassword',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $credentials = $this->findEntity(Credentials::class, ['name' => 'Test credentials']);

        self::assertNotNull($credentials, 'A credentials entry was created.');
        self::assertSame(CredentialsType::HttpBasic, $credentials->getType());
        self::assertSame('testuser', $credentials->getUsername());
    }

    public function testEdit(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $credentials = new Credentials();
        $credentials->setName('Original name');
        $credentials->setType(CredentialsType::HttpBasic);
        $credentials->setUsername('originaluser');
        $credentials->setPassword('originalpassword');

        $this->persistEntities($credentials);

        $credentialsId = $credentials->getId();

        $client->request('GET', "/credentials/{$credentialsId}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Save changes', [
            'Credentials[name]' => 'Updated name',
            'Credentials[type]' => CredentialsType::HttpBasic->value,
            'Credentials[username]' => 'updateduser',
            'Credentials[password]' => 'updatedpassword',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $this->clearEntities();
        $credentials = $this->findEntity(Credentials::class, $credentialsId);

        self::assertNotNull($credentials);
        self::assertSame('Updated name', $credentials->getName());
        self::assertSame('updateduser', $credentials->getUsername());
    }
}
