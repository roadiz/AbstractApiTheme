<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use Themes\AbstractApiTheme\AbstractApiThemeApp;

final class GrantPermissionController extends AbstractApiThemeApp
{
    /**
     * @param array $roles
     * @param ClientEntityInterface $client
     * @param string $grant
     * @return \Symfony\Component\HttpFoundation\Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function defaultAction(
        array $roles,
        ClientEntityInterface $client,
        string $grant
    ) {
        return $this->render('security/grant.html.twig', [
            'roles' => $roles,
            'client' => $client,
            'grant' => $grant
        ]);
    }
}
