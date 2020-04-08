<?php

namespace Themes\AbstractApiTheme\Controllers\Admin;

use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Form\ApplicationType;
use Themes\Rozier\RozierApp;

class ApplicationController extends RozierApp
{
    const ITEM_PER_PAGE = 20;

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_API');

        $elm = new EntityListManager(
            $request,
            $this->get('em'),
            $this->get('api.application_class'),
            [],
            [
                'createdAt' => 'DESC'
            ]
        );
        $elm->setItemPerPage(static::ITEM_PER_PAGE);
        $elm->handle();

        $this->assignation['applications'] = $elm->getEntities();
        $this->assignation['filters'] = $elm->getAssignation();

        return $this->render(
            'admin/applications/list.html.twig',
            $this->assignation,
            null,
            AbstractApiThemeApp::getThemeDir()
        );
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     * @throws \Twig_Error_Runtime
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_API');

        /** @var Application $application */
        $application = $this->get('api.application_factory');

        $form = $this->createForm(ApplicationType::class, $application, [
            'entityManager' => $this->get('em'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->persist($application);
            $this->get('em')->flush();

            $msg = $this->getTranslator()->trans(
                'api.applications.%name%.was_created',
                [
                    '%name%' => $application->getAppName(),
                ]
            );
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')->generate('adminApiApplicationsDetails', [
                'id' => $application->getId(),
            ]));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render(
            'admin/applications/add.html.twig',
            $this->assignation,
            null,
            AbstractApiThemeApp::getThemeDir()
        );
    }

    public function editAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_API');

        /** @var Application|null $application */
        $application = $this->get('em')->find($this->get('api.application_class'), $id);

        if (null === $application) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ApplicationType::class, $application, [
            'entityManager' => $this->get('em'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->flush();

            $msg = $this->getTranslator()->trans(
                'api.applications.%name%.was_updated',
                [
                    '%name%' => $application->getAppName(),
                ]
            );
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')->generate('adminApiApplicationsDetails', [
                'id' => $application->getId(),
            ]));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['application'] = $application;

        return $this->render(
            'admin/applications/edit.html.twig',
            $this->assignation,
            null,
            AbstractApiThemeApp::getThemeDir()
        );
    }

    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_API');

        /** @var Application|null $application */
        $application = $this->get('em')->find($this->get('api.application_class'), $id);

        if (null === $application) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->remove($application);
            $this->get('em')->flush();

            $msg = $this->getTranslator()->trans(
                'api.applications.%name%.was_deleted',
                [
                    '%name%' => $application->getAppName(),
                ]
            );
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')->generate('adminApiApplications'));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['application'] = $application;

        return $this->render(
            'admin/applications/delete.html.twig',
            $this->assignation,
            null,
            AbstractApiThemeApp::getThemeDir()
        );
    }
}
