<?php

namespace CodedMonkey\Dirigent\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class AccountMfaFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'required' => true,
            ])
            ->add('totpCode', TotpCodeType::class, [
                'label' => 'Verification code',
            ]);
    }
}
