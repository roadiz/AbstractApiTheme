<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers\Admin;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use Symfony\Component\HttpFoundation\Request;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Form\ApplicationType;
use Themes\Rozier\Controllers\AbstractAdminController;

class ApplicationController extends AbstractAdminController
{
    protected function supports(PersistableInterface $item): bool
    {
        $className = $this->get('api.application_class');
        return $item instanceof $className;
    }

    protected function getNamespace(): string
    {
        return 'api.application';
    }

    protected function createEmptyItem(Request $request): PersistableInterface
    {
        return $this->get('api.application_factory');
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
        return $this->get('api.application_class');
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
