<?php

namespace CodedMonkey\Conductor\Form;

use CodedMonkey\Conductor\Doctrine\Entity\Credentials;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PackageAddVcsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('repositoryUrl', TextType::class)
            ->add('repositoryCredentials', EntityType::class, [
                'required' => false,
                'class' => Credentials::class,
            ]);
    }
}
