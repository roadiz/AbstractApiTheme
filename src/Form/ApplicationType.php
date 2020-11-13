<?php

namespace Themes\AbstractApiTheme\Form;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\AbstractApiTheme\Entity\Application;

class ApplicationType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('appName', TextType::class, [
                'label' => 'api.applications.name',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('allowedPreview', CheckboxType::class, [
                'label' => 'api.applications.allowedPreview',
                'help' => 'api.applications.allowedPreview.help',
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'api.applications.enabled',
                'required' => false,
            ])
            ->add('confidential', CheckboxType::class, [
                'label' => 'api.applications.confidential',
                'help' => 'api.applications.confidential.help',
                'required' => false,
            ])
            ->add('refererRegex', TextType::class, [
                'label' => 'api.applications.referer',
                'required' => false,
            ])
        ;
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('entityClass', Application::class);
        $resolver->setRequired('entityManager');
        $resolver->addAllowedTypes('entityManager', [EntityManagerInterface::class]);
        $resolver->setNormalizer('constraints', function (Options $options) {
            return [
                new UniqueEntity([
                    'fields' => 'appName',
                    'entityManager' => $options->offsetGet('entityManager')
                ]),
                new UniqueEntity([
                    'fields' => 'apiKey',
                    'entityManager' => $options->offsetGet('entityManager'),
                    'message' => 'api.applications.api_key_in_use'
                ])
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'application';
    }
}
