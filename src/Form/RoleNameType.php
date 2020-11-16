<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\UnicodeString;

class RoleNameType extends AbstractType
{
    /**
     * {@inheritdoc}
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'roles' => new ArrayCollection(),
            'multiple' => false,
        ]);

        $resolver->setRequired('entityManager');
        $resolver->setRequired('rolePrefix');
        $resolver->setRequired('baseRole');
        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);
        $resolver->setAllowedTypes('rolePrefix', ['string']);
        $resolver->setAllowedTypes('baseRole', ['string']);

        /*
         * Use normalizer to populate choices from ChoiceType
         */
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            /** @var EntityManager $entityManager */
            $entityManager = $options['entityManager'];
            $roles = $entityManager->getRepository(Role::class)->findAll();
            $roles = array_filter($roles, function (Role $role) use ($options) {
                return (new UnicodeString($role->getRole()))->startsWith($options['rolePrefix']);
            });

            /** @var Role $role */
            foreach ($roles as $role) {
                $choices[$role->getRole()] = $role->getRole();
            }
            $choices[Role::ROLE_BACKEND_USER] = Role::ROLE_BACKEND_USER;
            $choices[$options['baseRole']] = $options['baseRole'];
            ksort($choices);
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
