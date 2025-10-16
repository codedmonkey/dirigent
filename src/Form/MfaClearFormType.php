<?php

namespace CodedMonkey\Dirigent\Form;

use CodedMonkey\Dirigent\Validator\UserPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class MfaClearFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'required' => true,
                'constraints' => [new UserPassword()],
            ]);
    }
}
