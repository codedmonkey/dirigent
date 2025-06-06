<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\Tests\FunctionalTests\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardPackagesControllerTest extends WebTestCase
{
    use WebTestCaseTrait;

    public function testAddMirroring(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $registry = $client->getContainer()->get(RegistryRepository::class)->findOneBy(['name' => 'Packagist']);

        $client->request('GET', '/packages/add-mirroring');
        $client->submitForm('Add packages', [
            'package_add_mirroring_form[packages]' => 'psr/cache',
            'package_add_mirroring_form[registry]' => $registry->getId(),
        ]);

        $this->assertResponseStatusCodeSame(200);

        // todo the submit request should be invoked with ajax, and this assertion should be performed on the initial request
        // however, the assertion is performed on the ajax response making it invalid
        // $this->assertAnySelectorTextSame(
        //     '.text-success',
        //     'The package psr/cache was created successfully.',
        //     'A message showing the package was created must be shown.',
        // );

        /** @var PackageRepository $packageRepository */
        $packageRepository = $client->getContainer()->get(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/cache');
        self::assertNotNull($package, 'A package was created.');

        $packageRepository->remove($package, true);
    }

    public function testAddVcsRepository(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $client->request('GET', '/packages/add-vcs');
        $client->submitForm('Add VCS repository', [
            'package_add_vcs_form[repositoryUrl]' => 'https://github.com/php-fig/container',
        ]);

        $this->assertResponseStatusCodeSame(302);

        /** @var PackageRepository $packageRepository */
        $packageRepository = $client->getContainer()->get(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/container');
        self::assertNotNull($package, 'A package was created.');

        $packageRepository->remove($package, true);
    }

    public function testEdit(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $client->request('GET', '/packages/psr/log/edit');
        $client->submitForm('Save changes');

        $this->assertResponseStatusCodeSame(302);
    }
}
