<?php

namespace Themes\AbstractApiTheme\Form;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
     * @return void
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
            ->add('enabled', CheckboxType::class, [
                'label' => 'api.applications.enabled',
                'help' => 'api.applications.enabled.help',
                'required' => false,
            ])
            ->add('confidential', CheckboxType::class, [
                'label' => 'api.applications.confidential',
                'help' => 'api.applications.confidential.help',
                'required' => false,
            ])
        ;

        if ($builder->getData()->isConfidential()) {
            $builder
                ->add('grantTypes', ChoiceType::class, [
                    'label' => 'api.applications.grantTypes',
                    'help' => 'api.applications.grantTypes.help',
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                    'choices' => [
                        Application::GRANT_CLIENT_CREDENTIALS => Application::GRANT_CLIENT_CREDENTIALS,
                        Application::GRANT_AUTHORIZATION_CODE => Application::GRANT_AUTHORIZATION_CODE
                    ]
                ])
                ->add('redirectUri', TextType::class, [
                    'label' => 'api.applications.redirectUri',
                    'help' => 'api.applications.redirectUri.help',
                    'required' => false
                ])
                ->add('roles', CollectionType::class, [
                    'label' => 'api.applications.roles',
                    'help' => 'api.applications.roles.help',
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'attr' => [
                        'class' => 'rz-collection-form-type'
                    ],
                    'entry_type' => RoleNameType::class,
                    'entry_options' => [
                        'label' => false,
                        'entityManager' => $options['entityManager'],
                        'rolePrefix' => $options['rolePrefix'],
                        'baseRole' => $options['baseRole'],
                    ]
                ])
            ;
        } else {
            $builder
                ->add('allowedPreview', CheckboxType::class, [
                    'label' => 'api.applications.allowedPreview',
                    'help' => 'api.applications.allowedPreview.help',
                    'required' => false,
                ])
                ->add('refererRegex', TextType::class, [
                    'label' => 'api.applications.referer',
                    'required' => false,
                ])
            ;
        }
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('entityClass', Application::class);
        $resolver->setRequired('entityManager');
        $resolver->setRequired('rolePrefix');
        $resolver->setRequired('baseRole');
        $resolver->addAllowedTypes('entityManager', [EntityManagerInterface::class]);
        $resolver->addAllowedTypes('rolePrefix', ['string']);
        $resolver->addAllowedTypes('baseRole', ['string']);
        $resolver->setNormalizer('constraints', function (Options $options) {
            return [
                new UniqueEntity([
                    'fields' => 'appName',
                ]),
                new UniqueEntity([
                    'fields' => 'apiKey',
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
