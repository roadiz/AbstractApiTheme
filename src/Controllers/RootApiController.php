<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use FOS\JsRoutingBundle\Response\RoutesResponse;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Themes\AbstractApiTheme\AbstractApiThemeApp;

class RootApiController extends AbstractApiThemeApp
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getRootAction(Request $request)
    {
        $response = new RoutesResponse($request->getBaseUrl(), $this->get('api.route_collection'), '', $request->getHost(), $request->getScheme());

        $finalResponse = [];
        $finalRoutes = [];

        // Find siblings
        foreach ($response->getRoutes() as $routeName => $route) {
            $finalRoutes[$routeName] = $route;
        }

        // Build the final response
        $finalResponse['base_url'] = $response->getBaseUrl();
        $finalResponse['prefix'] = $response->getPrefix();
        $finalResponse['host'] = $response->getHost();
        $finalResponse['scheme'] = $response->getScheme();
        $finalResponse['routes'] = $finalRoutes;


        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        $response = new JsonResponse(
            $serializer->serialize(
                $finalResponse,
                'json'
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );

        /** @var int $cacheTtl */
        $cacheTtl = $this->get('api.cache.ttl');
        if ($cacheTtl > 0) {
            $this->makeResponseCachable($request, $response, $cacheTtl);
        }

        return $response;
    }
}
