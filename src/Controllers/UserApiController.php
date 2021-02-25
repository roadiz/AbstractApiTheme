<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Themes\AbstractApiTheme\AbstractApiThemeApp;

class UserApiController extends AbstractApiThemeApp
{
    protected function getSerializationGroups(): array
    {
        return [
            'user',
            'roles',
        ];
    }
    /**
     * @return SerializationContext
     */
    protected function getSerializationContext(): SerializationContext
    {
        $context = SerializationContext::create()
            ->enableMaxDepthChecks();
        if (count($this->getSerializationGroups()) > 0) {
            $context->setGroups($this->getSerializationGroups());
        }

        return $context;
    }

    /**
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function getUserAction(Request $request)
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('securityTokenStorage');
        $token = $tokenStorage->getToken();

        if (null === $token) {
            throw $this->createNotFoundException();
        }

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        $response = new JsonResponse(
            $serializer->serialize(
                $token,
                'json',
                $this->getSerializationContext()
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );

        return $this->makeResponseCachable($request, $response, 0, false);
    }
}
