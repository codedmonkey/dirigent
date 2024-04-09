<?php

namespace CodedMonkey\Conductor\Form;

use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class PackageAddRegistryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('packages', TextAreaType::class)
            ->add('registry', EntityType::class, [
                'class' => Registry::class,
            ]);
    }
}
