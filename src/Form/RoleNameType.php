<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\UnicodeString;

class RoleNameType extends AbstractType
{
    /**
     * @var string
     */
    protected $rolePrefix;
    /**
     * @var string
     */
    protected $baseRole;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param string $rolePrefix
     * @param string $baseRole
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(string $rolePrefix, string $baseRole, EntityManagerInterface $entityManager)
    {
        $this->rolePrefix = $rolePrefix;
        $this->baseRole = $baseRole;
        $this->entityManager = $entityManager;
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
            $roles = $this->entityManager->getRepository(Role::class)->findAll();
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
