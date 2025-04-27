<?php

namespace CodedMonkey\Dirigent\Form;

use CodedMonkey\Dirigent\Doctrine\Entity\Credentials;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Package\PackageVcsRepositoryValidator;
use JsonSchema\Validator as JsonValidator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackageAddComposerFormType extends AbstractType
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contents', TextareaType::class)
            ->add('type', HiddenType::class)
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSubmit(PreSubmitEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        $contentsField = $form->get('contents');
        $rawContents = $data['contents'];

        if (!json_validate($rawContents)) {
            $contentsField->addError(new FormError(json_last_error_msg()));

            return;
        }

        $contents = json_decode($rawContents);

        $composerSchemaValidator = new JsonValidator();
        $composerSchemaPath = "$this->projectDir/vendor/composer/composer/res/composer-schema.json";
        $composerSchemaValidator->validate($contents, (object) ['$ref' => "file://$composerSchemaPath"]);

        if ($composerSchemaValidator->isValid()) {
            $event->setData([
                'contents' => $rawContents,
                'type' => 'composer',
            ]);

            return;
        }

        $satisSchemaValidator = new JsonValidator();
        $satisSchemaPath = "$this->projectDir/vendor/composer/satis/res/satis-schema.json";
        $satisSchemaValidator->validate($contents, (object) ['$ref' => "file://$satisSchemaPath"]);

        if ($satisSchemaValidator->isValid()) {
            $event->setData([
                'contents' => $rawContents,
                'type' => 'satis',
            ]);

            return;
        }

        $contentsField->addError(new FormError('Failed to match schema of file'));
    }
}
