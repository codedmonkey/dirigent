<?php

namespace CodedMonkey\Dirigent\Form;

use CodedMonkey\Dirigent\Doctrine\Entity\Credentials;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\Package\PackageVcsRepositoryValidator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackageFormType extends AbstractType
{
    public function __construct(
        private readonly PackageVcsRepositoryValidator $vcsRepositoryValidator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'disabled' => true,
                'help' => 'Editing the package name is not possible, it\'s automatically retrieved from the source code.',
            ])
            ->add('repositoryUrl', TextType::class)
            ->add('repositoryCredentials', EntityType::class, [
                'label' => 'Credentials',
                'required' => false,
                'class' => Credentials::class,
                'placeholder' => 'No credentials',
            ])
            ->add('mirrorRegistry', TextType::class, [
                'disabled' => true,
                'help' => 'Adding a mirror registry to a package is not possible. Delete the package first.',
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData'])
            ->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    public function onPostSetData(PostSetDataEvent $event): void
    {
        $form = $event->getForm();
        /** @var Package $package */
        $package = $event->getData();

        if ($package->getMirrorRegistry()) {
            $form
                ->add('repositoryUrl', TextType::class, [
                    'disabled' => true,
                    'help' => 'The repository URL is automatically retrieved from the mirror registry. Remove the mirror registry first.',
                ])
                ->add('mirrorRegistry', EntityType::class, [
                    'class' => Registry::class,
                    'required' => false,
                    'query_builder' => static function (RegistryRepository $repository) use ($package) {
                        return $repository->createQueryBuilder('registry')
                            ->where('registry.id = :id')
                            ->setParameter('id', $package->getMirrorRegistry()->getId());
                    },
                ])
                ->add('fetchStrategy', EnumType::class, [
                    'class' => PackageFetchStrategy::class,
                    'expanded' => true,
                    'disabled' => !$package->getRepositoryUrl(),
                    'choice_label' => static function (PackageFetchStrategy $choice): string {
                        return "package.fetch-strategy.{$choice->value}";
                    },
                ]);

            if (PackageFetchStrategy::Mirror === $package->getFetchStrategy()) {
                $form
                    ->add('repositoryCredentials', EntityType::class, [
                        'label' => 'Credentials',
                        'disabled' => true,
                        'class' => Credentials::class,
                        'placeholder' => 'No credentials',
                    ]);
            }
        }
    }

    public function onSubmit(SubmitEvent $event): void
    {
        $form = $event->getForm();
        /** @var Package $package */
        $package = $event->getData();

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
