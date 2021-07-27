<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\UnicodeString;

class RoleNameType extends AbstractType
{
    protected string $rolePrefix;
    protected string $baseRole;
    protected ManagerRegistry $managerRegistry;

    /**
     * @param string $rolePrefix
     * @param string $baseRole
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(string $rolePrefix, string $baseRole, ManagerRegistry $managerRegistry)
    {
        $this->rolePrefix = $rolePrefix;
        $this->baseRole = $baseRole;
        $this->managerRegistry = $managerRegistry;
    }

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

        /*
         * Use normalizer to populate choices from ChoiceType
         */
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            $roles = $this->managerRegistry->getRepository(Role::class)->findAll();
            $roles = array_filter($roles, function (Role $role) {
                return (new UnicodeString($role->getRole()))->startsWith($this->rolePrefix);
            });

            /** @var Role $role */
            foreach ($roles as $role) {
                $choices[$role->getRole()] = $role->getRole();
            }
            $choices[Role::ROLE_BACKEND_USER] = Role::ROLE_BACKEND_USER;
            $choices[$this->baseRole] = $this->baseRole;
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
