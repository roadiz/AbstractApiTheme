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
                'label' => 'application.name',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'application.enabled',
                'required' => false,
            ])
            ->add('refererRegex', TextType::class, [
                'label' => 'application.referer',
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