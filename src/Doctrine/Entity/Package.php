<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity(repositoryClass: PackageRepository::class)]
class Package
{
    #[Column]
    #[GeneratedValue]
    #[Id]
    public ?int $id = null;

    #[Column]
    public ?string $name = null;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;
}
