<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity(repositoryClass: PackageRepository::class)]
class Package
{
    #[Column]
    #[GeneratedValue]
    #[Id]
    public ?int $id = null;

    #[Column]
    public ?string $name = null;

    #[Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    #[ManyToOne]
    public ?Registry $mirrorRegistry = null;
}
