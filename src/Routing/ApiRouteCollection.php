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
use Themes\AbstractApiTheme\Controllers\NodesSourcesListingApiController;
use Themes\AbstractApiTheme\Controllers\NodeTypeListingApiController;
use Themes\AbstractApiTheme\Controllers\NodeTypeSingleApiController;
use Themes\AbstractApiTheme\Controllers\NodeTypeTagsApiController;
use Themes\AbstractApiTheme\Controllers\RootApiController;
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
    private $userControllerClass;
    /**
     * @var string
     */
    private $nodeTypeListingControllerClass;
    /**
     * @var string
     */
    private $nodeTypeSingleControllerClass;
    /**
     * @var string
     */
    private $nodeTypeTagsControllerClass;

    /**
     * @param NodeTypes $nodeTypesBag
     * @param Settings $settingsBag
     * @param Stopwatch|null $stopwatch
     * @param string $apiPrefix
     * @param string $apiVersion
     * @param array|null $nodeTypeWhitelist
     * @param string $rootControllerClass
     * @param string $nodeTypeListingControllerClass
     * @param string $nodeTypeSingleControllerClass
     * @param string $userControllerClass
     * @param string $nodeTypeTagsControllerClass
     */
    final public function __construct(
        NodeTypes $nodeTypesBag,
        Settings $settingsBag,
        Stopwatch $stopwatch = null,
        $apiPrefix = '/api',
        $apiVersion = '1.0',
        $nodeTypeWhitelist = null,
        $rootControllerClass = RootApiController::class,
        $nodeTypeListingControllerClass = NodeTypeListingApiController::class,
        $nodeTypeSingleControllerClass = NodeTypeSingleApiController::class,
        $userControllerClass = UserApiController::class,
        $nodeTypeTagsControllerClass = NodeTypeTagsApiController::class
    ) {
        $this->stopwatch = $stopwatch;
        $this->settingsBag = $settingsBag;
        $this->nodeTypesBag = $nodeTypesBag;
        $this->apiVersion = $apiVersion;
        $this->apiPrefix = $apiPrefix;

        $this->routePrefix = $this->apiPrefix . '/' . $this->apiVersion;
        $this->nodeTypeWhitelist = $nodeTypeWhitelist;
        $this->rootControllerClass = $rootControllerClass;
        $this->userControllerClass = $userControllerClass;
        $this->nodeTypeListingControllerClass = $nodeTypeListingControllerClass;
        $this->nodeTypeSingleControllerClass = $nodeTypeSingleControllerClass;
        $this->nodeTypeTagsControllerClass = $nodeTypeTagsControllerClass;
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
            'get_listing_nodes_sources',
            new Route(
                $this->routePrefix . '/nodes-sources',
                [
                    // TODO: Variabilize NS listing controller
                    '_controller' => NodesSourcesListingApiController::class . '::defaultAction',
                    'nodeTypeId' => null
                ],
                [],
                [],
                '',
                [],
                ['GET'],
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
                    /** @var NodeType|null $nodeType */
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
                    '_controller' => $this->nodeTypeListingControllerClass . '::defaultAction',
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
            'get_tags_'.mb_strtolower($nodeType->getName()),
            new Route(
                $this->routePrefix . '/' . mb_strtolower($nodeType->getName()) . '/tags',
                [
                    '_controller' => $this->nodeTypeTagsControllerClass . '::defaultAction',
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
                    '_controller' => $this->nodeTypeSingleControllerClass . '::defaultAction',
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
                    '_controller' => $this->nodeTypeSingleControllerClass . '::bySlugAction',
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
