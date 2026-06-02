<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Form;

use CodedMonkey\Dirigent\Validator\UserPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'required' => true,
                'constraints' => [new UserPassword()],
            ])
            ->add('newPassword', NewPasswordType::class, [
                'new_password' => true,
            ]);
    }
}
