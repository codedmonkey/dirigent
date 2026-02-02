<?php

namespace CodedMonkey\Dirigent\Form;

use CodedMonkey\Dirigent\Doctrine\Entity\Credentials;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Package\PackageVcsRepositoryValidator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackageAddVcsFormType extends AbstractType
{
    public function __construct(
        private readonly PackageVcsRepositoryValidator $vcsRepositoryValidator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('repositoryUrl', TextType::class)
            ->add('repositoryCredentials', EntityType::class, [
                'label' => 'Credentials',
                'required' => false,
                'class' => Credentials::class,
                'placeholder' => 'No credentials',
            ])
            ->addEventListener(FormEvents::SUBMIT, $this->onSubmit(...));
    }

    public function onSubmit(SubmitEvent $event): void
    {
        $form = $event->getForm();
        /** @var Package $package */
        $package = $event->getData();

        $package->setFetchStrategy(PackageFetchStrategy::Vcs);

        $validationResult = $this->vcsRepositoryValidator->validate($package);

        if (null === $validationResult['error']) {
            $this->vcsRepositoryValidator->loadResult($package, $validationResult);
        } else {
            $form->get('repositoryUrl')->addError(new FormError($validationResult['error']));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Package::class,
        ]);
    }
}
