<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Repository\KeywordRepository;
use CodedMonkey\Dirigent\Tests\Helper\EntityManagerTestTrait;
use CodedMonkey\Dirigent\Tests\Helper\KernelTestCaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KeywordRepositoryTest extends KernelTestCase
{
    use EntityManagerTestTrait;
    use KernelTestCaseTrait;

    public function testGetByNameDoesNotReturnCachedDetachedEntity(): void
    {
        $repository = $this->getService(KeywordRepository::class);
        $entityManager = $this->getService(EntityManagerInterface::class);
        $keyword = $repository->getByName(uniqid('keyword-', true));

        $entityManager->flush();
        $entityManager->clear();

        $managedKeyword = $repository->getByName($keyword->getName());

        $this->assertNotSame($keyword, $managedKeyword);
        $this->assertSame($keyword->getId(), $managedKeyword->getId());
        $this->assertTrue($entityManager->contains($managedKeyword));
    }
}
