<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardPackagesControllerTest extends WebTestCase
{
    use MockEntityFactoryTrait;
    use WebTestCaseTrait;

    public function testAddMirroring(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $registry = self::getService(RegistryRepository::class)->findOneBy(['name' => 'Packagist']);

        $client->request('GET', '/packages/add-mirroring');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Add packages', [
            'package_add_mirroring_form[packages]' => 'psr/cache',
            'package_add_mirroring_form[registry]' => $registry->getId(),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // todo the submit request should be invoked with ajax, and this assertion should be performed on the initial request
        // however, the assertion is performed on the ajax response making it invalid
        // $this->assertAnySelectorTextSame(
        //     '.text-success',
        //     'The package psr/cache was created successfully.',
        //     'A message showing the package was created must be shown.',
        // );

        $packageRepository = self::getService(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/cache');
        self::assertNotNull($package, 'A package was created.');
    }

    public function testAddVcsRepository(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $client->request('GET', '/packages/add-vcs');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Add VCS repository', [
            'package_add_vcs_form[repositoryUrl]' => 'https://github.com/php-fig/container',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        /** @var PackageRepository $packageRepository */
        $packageRepository = self::getService(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/container');
        self::assertNotNull($package, 'A package was created.');
    }

    public function testEdit(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $client->request('GET', '/packages/psr/log/edit');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Save changes');

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testDelete(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $entityManager = $this->getService(EntityManagerInterface::class);

        $package = $this->createMockPackage();
        $this->persistEntities($package);

        // Fetch package id prior to deleting it
        $packageId = $package->getId();

        $client->request('GET', "/packages/{$package->getName()}/delete");

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $savedPackage = $entityManager->find(Package::class, $packageId);

        $this->assertNull($savedPackage, 'The package was deleted.');
    }
}
