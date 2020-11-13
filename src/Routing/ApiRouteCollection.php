<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Routing;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Routing\DeferredRouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;
use Themes\AbstractApiTheme\Controllers\NodeTypeApiController;
use Themes\AbstractApiTheme\Controllers\RootApiController;
use Themes\AbstractApiTheme\Controllers\TokenController;
use Themes\AbstractApiTheme\Controllers\UserApiController;

class ApiRouteCollection extends DeferredRouteCollection
{
    /**
     * @var NodeTypes
     */
    protected $nodeTypesBag;
    /**
     * @var string
     */
    protected $apiVersion;
    /**
     * @var string
     */
    protected $apiPrefix;
    /**
     * @var string
     */
    protected $routePrefix;
    /**
     * @var Stopwatch|null
     */
    protected $stopwatch;
    /**
     * @var bool
     */
    protected $isPreview;
    /**
     * @var Settings
     */
    protected $settingsBag;
    /**
     * @var array|null
     */
    protected $nodeTypeWhitelist;
    /**
     * @var string
     */
    private $rootControllerClass;
    /**
     * @var string
     */
    private $nodeTypeControllerClass;
    /**
     * @var string
     */
    private $userControllerClass;

    /**
     * ApiRouteCollection constructor.
     *
     * @param NodeTypes $nodeTypesBag
     * @param Settings $settingsBag
     * @param Stopwatch|null $stopwatch
     * @param bool $isPreview
     * @param string $apiPrefix
     * @param string $apiVersion
     * @param array|null $nodeTypeWhitelist
     * @param string $rootControllerClass
     * @param string $nodeTypeControllerClass
     * @param string $userControllerClass
     */
    final public function __construct(
        NodeTypes $nodeTypesBag,
        Settings $settingsBag,
        Stopwatch $stopwatch = null,
        $isPreview = false,
        $apiPrefix = '/api',
        $apiVersion = '1.0',
        $nodeTypeWhitelist = null,
        $rootControllerClass = RootApiController::class,
        $nodeTypeControllerClass = NodeTypeApiController::class,
        $userControllerClass = UserApiController::class
    ) {
        $this->stopwatch = $stopwatch;
        $this->isPreview = $isPreview;
        $this->settingsBag = $settingsBag;
        $this->nodeTypesBag = $nodeTypesBag;
        $this->apiVersion = $apiVersion;
        $this->apiPrefix = $apiPrefix;

        $this->routePrefix = $this->apiPrefix . '/' . $this->apiVersion;
        $this->nodeTypeWhitelist = $nodeTypeWhitelist;
        $this->rootControllerClass = $rootControllerClass;
        $this->nodeTypeControllerClass = $nodeTypeControllerClass;
        $this->userControllerClass = $userControllerClass;
    }

    public function parseResources(): void
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('apiRouteCollection');
        }
        $this->add(
            'api_public_root',
            new Route(
                $this->routePrefix,
                [
                    '_controller' => $this->rootControllerClass . '::getRootAction',
                ],
                [],
                [],
                '',
                [],
                ['GET'],
                ''
            )
        );

        $this->add(
            'api_user_me',
            new Route(
                $this->routePrefix . '/me',
                [
                    '_controller' => $this->userControllerClass . '::getUserAction',
                ],
                [],
                [],
                '',
                [],
                ['GET'],
                ''
            )
        );

        $this->add(
            'api_post_token',
            new Route(
                '/token',
                [
                    '_controller' => TokenController::class . '::defaultAction',
                ],
                [],
                [],
                '',
                [],
                ['POST'],
                ''
            )
        );

        try {
            if (null === $this->nodeTypeWhitelist) {
                /** @var NodeType[] $nodeTypes */
                $nodeTypes = $this->nodeTypesBag->all();
                foreach ($nodeTypes as $nodeType) {
                    $this->addCollection($this->getCollectionForNodeType($nodeType));
                }
            } else {
                foreach ($this->nodeTypeWhitelist as $nodeTypeName) {
                    /** @var NodeType|null $nodeTypes */
                    $nodeType = $this->nodeTypesBag->get($nodeTypeName);
                    if (null !== $nodeType) {
                        $this->addCollection($this->getCollectionForNodeType($nodeType));
                    }
                }
            }
        } catch (\Exception $e) {
            /*
             * Database tables don't exist yet
             * Need Install
             */
        }
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('apiRouteCollection');
        }
    }

    private function getCollectionForNodeType(NodeType $nodeType): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->add(
            'get_listing_'.mb_strtolower($nodeType->getName()),
            new Route(
                $this->routePrefix . '/' . mb_strtolower($nodeType->getName()),
                [
                    '_controller' => $this->nodeTypeControllerClass . '::getListingAction',
                    'nodeTypeId' => $nodeType->getId()
                ],
                [],
                [],
                '',
                [],
                ['GET'],
                ''
            )
        );
        $collection->add(
            'get_single_'.mb_strtolower($nodeType->getName()),
            new Route(
                $this->routePrefix . '/' . mb_strtolower($nodeType->getName()) . '/{id}',
                [
                    '_controller' => $this->nodeTypeControllerClass . '::getDetailAction',
                    'nodeTypeId' => $nodeType->getId()
                ],
                [
                    'id' => '[0-9]+'
                ],
                [],
                '',
                [],
                ['GET'],
                ''
            )
        );
        $collection->add(
            'get_single_slug_'.mb_strtolower($nodeType->getName()),
            new Route(
                $this->routePrefix . '/' . mb_strtolower($nodeType->getName()) . '/by-slug/{slug}',
                [
                    '_controller' => $this->nodeTypeControllerClass . '::getDetailBySlugAction',
                    'nodeTypeId' => $nodeType->getId()
                ],
                [
                    'slug' => '[a-z\-0-9]+'
                ],
                [],
                '',
                [],
                ['GET'],
                ''
            )
        );

        return $collection;
    }
}
