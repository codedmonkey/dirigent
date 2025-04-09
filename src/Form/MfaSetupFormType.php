<?php

namespace CodedMonkey\Dirigent\Form;

use CodedMonkey\Dirigent\Validator\UserMfaCode;
use CodedMonkey\Dirigent\Validator\UserPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class MfaSetupFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'required' => true,
                'constraints' => [new UserPassword()],
            ])
            ->add('totpCode', TotpCodeType::class, [
                'label' => 'auth_code',
                'translation_domain' => 'SchebTwoFactorBundle',
                'constraints' => [new UserMfaCode()],
            ]);
    }
}
