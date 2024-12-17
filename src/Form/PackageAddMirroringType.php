<?php

namespace CodedMonkey\Dirigent\Form;

use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class PackageAddMirroringType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('packages', TextareaType::class)
            ->add('registry', EntityType::class, [
                'class' => Registry::class,
                'query_builder' => function (RegistryRepository $repository) {
                    return $repository->createPackageMirroringQueryBuilder('manual');
                },
            ]);
    }
}
