<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleNameType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'roles' => new ArrayCollection(),
            'multiple' => false,
        ]);

        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);

        /*
         * Use normalizer to populate choices from ChoiceType
         */
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            /** @var EntityManager $entityManager */
            $entityManager = $options['entityManager'];
            $roles = $entityManager->getRepository(Role::class)->findAll();

            /** @var Role $role */
            foreach ($roles as $role) {
                $choices[$role->getRole()] = $role->getRole();
            }
            return $choices;
        });
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'roles_names';
    }
}
