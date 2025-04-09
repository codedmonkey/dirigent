<?php

namespace CodedMonkey\Dirigent\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class NewPasswordType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'new_password' => false,
            'type' => PasswordType::class,
            'options' => [
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'first_options' => function (Options $options): array {
                $label = $options['new_password'] ? 'New password' : 'Password';

                return [
                    'constraints' => self::constraints(false),
                    'label' => $label,
                ];
            },
            'second_options' => function (Options $options): array {
                $label = $options['new_password'] ? 'Repeat new password' : 'Repeat password';

                return [
                    'label' => $label,
                ];
            },
            'invalid_message' => 'The password fields must match',
        ]);
    }

    public function getParent(): string
    {
        return RepeatedType::class;
    }

    public static function constraints(bool $nullable = true): array
    {
        $constraints = [
            new Length([
                'min' => 8,
                'minMessage' => 'Your password must be at least {{ limit }} characters',
                'max' => 4096, // max length allowed by Symfony for security reasons
            ]),
            new PasswordStrength(minScore: PasswordStrength::STRENGTH_WEAK),
            new NotCompromisedPassword(),
        ];

        if (!$nullable) {
            $constraints[] = new NotBlank([
                'message' => 'Enter a password',
            ]);
        }

        return $constraints;
    }
}
