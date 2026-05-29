<?php

namespace CodedMonkey\Dirigent\EasyAdmin;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Contracts\Translation\TranslatableInterface;

class DateIntervalField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return new self()
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(DateIntervalType::class);
    }
}
