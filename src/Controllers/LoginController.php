<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Themes\Rozier\Forms\LoginType;

class LoginController extends AbstractApiThemeApp
{
    /**
     * @return Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function defaultAction()
    {
        if ($this->isGranted(Role::ROLE_BACKEND_USER)) {
            throw new BadRequestHttpException('User is already authenticated.');
        }

        $form = $this->createForm(LoginType::class);
        $this->assignation['form'] = $form->createView();

        $helper = $this->get('securityAuthenticationUtils');

        $this->assignation['hide_website'] = true;
        $this->assignation['last_username'] = $helper->getLastUsername();
        $this->assignation['error'] = $helper->getLastAuthenticationError();

        return $this->render('login/login.html.twig', $this->assignation, null, 'Rozier');
    }
}
