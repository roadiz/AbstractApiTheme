<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers\Admin;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Form\ApplicationType;
use Themes\AbstractApiTheme\Model\ApplicationFactory;
use Themes\Rozier\Controllers\AbstractAdminController;

class ApplicationController extends AbstractAdminController
{
    private string $applicationClass;
    private ApplicationFactory $applicationFactory;

    public function __construct(
        string $applicationClass,
        ApplicationFactory $applicationFactory,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($serializer, $urlGenerator);
        $this->applicationClass = $applicationClass;
        $this->applicationFactory = $applicationFactory;
    }

    /**
     * @return string
     */
    protected function getTemplateNamespace(): string
    {
        return 'AbstractApiTheme';
    }

    protected function supports(PersistableInterface $item): bool
    {
        return $item instanceof $this->applicationClass;
    }

    protected function getNamespace(): string
    {
        return 'api.application';
    }

    protected function createEmptyItem(Request $request): PersistableInterface
    {
        return ($this->applicationFactory)();
    }

    protected function getTemplateFolder(): string
    {
        return 'admin/applications';
    }

    protected function getRequiredRole(): string
    {
        return 'ROLE_ADMIN_API';
    }

    protected function getEntityClass(): string
    {
        return $this->applicationClass;
    }

    protected function getFormType(): string
    {
        return ApplicationType::class;
    }

    protected function getDefaultRouteName(): string
    {
        return 'adminApiApplications';
    }

    protected function getEditRouteName(): string
    {
        return 'adminApiApplicationsDetails';
    }

    protected function getEntityName(PersistableInterface $item): string
    {
        if ($item instanceof Application) {
            return $item->getName();
        }
        throw new \InvalidArgumentException('Item must be instance of ' . Application::class);
    }

    protected function getDefaultOrder(): array
    {
        return [
            'createdAt' => 'DESC'
        ];
    }
}
