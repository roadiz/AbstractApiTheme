<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Routing;

use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Routing\DeferredRouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;
use Themes\AbstractApiTheme\Controllers\NodesSourcesListingApiController;
use Themes\AbstractApiTheme\Controllers\NodesSourcesSearchApiController;
use Themes\AbstractApiTheme\Controllers\NodeTypeArchivesApiController;
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
    protected NodeTypes $nodeTypesBag;
    /**
     * @var string
     */
    protected string $apiVersion;
    /**
     * @var string
     */
    protected string $apiPrefix;
    /**
     * @var string
     */
    protected string $routePrefix;
    /**
     * @var Stopwatch|null
     */
    protected ?Stopwatch $stopwatch;
    /**
     * @var Settings
     */
    protected Settings $settingsBag;
    /**
     * @var array|null
     */
    protected ?array $nodeTypeWhitelist;
    /**
     * @var class-string
     */
    private string $rootControllerClass;
    /**
     * @var class-string
     */
    private string $userControllerClass;
    /**
     * @var class-string
     */
    private string $nodeTypeListingControllerClass;
    /**
     * @var class-string
     */
    private string $nodeTypeSingleControllerClass;
    /**
     * @var class-string
     */
    private string $nodeTypeTagsControllerClass;
    /**
     * @var class-string
     */
    private string $nodesSourcesListingApiControllerClass;
    /**
     * @var class-string
     */
    private string $nodesSourcesSearchApiControllerClass;
    /**
     * @var class-string
     */
    private string $nodeTypeArchivesControllerClass;

    /**
     * @param NodeTypes $nodeTypesBag
     * @param Settings $settingsBag
     * @param Stopwatch|null $stopwatch
     * @param string $apiPrefix
     * @param string $apiVersion
     * @param array|null $nodeTypeWhitelist
     * @param class-string $rootControllerClass
     * @param class-string $nodeTypeListingControllerClass
     * @param class-string $nodeTypeSingleControllerClass
     * @param class-string $userControllerClass
     * @param class-string $nodeTypeTagsControllerClass
     * @param class-string $nodesSourcesListingApiControllerClass
     * @param class-string $nodesSourcesSearchApiControllerClass
     * @param class-string $nodeTypeArchivesControllerClass
     */
    final public function __construct(
        NodeTypes $nodeTypesBag,
        Settings $settingsBag,
        Stopwatch $stopwatch = null,
        string $apiPrefix = '/api',
        string $apiVersion = '1.0',
        ?array $nodeTypeWhitelist = null,
        string $rootControllerClass = RootApiController::class,
        string $nodeTypeListingControllerClass = NodeTypeListingApiController::class,
        string $nodeTypeSingleControllerClass = NodeTypeSingleApiController::class,
        string $userControllerClass = UserApiController::class,
        string $nodeTypeTagsControllerClass = NodeTypeTagsApiController::class,
        string $nodesSourcesListingApiControllerClass = NodesSourcesListingApiController::class,
        string $nodesSourcesSearchApiControllerClass = NodesSourcesSearchApiController::class,
        string $nodeTypeArchivesControllerClass = NodeTypeArchivesApiController::class
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
        $this->nodesSourcesListingApiControllerClass = $nodesSourcesListingApiControllerClass;
        $this->nodesSourcesSearchApiControllerClass = $nodesSourcesSearchApiControllerClass;
        $this->nodeTypeArchivesControllerClass = $nodeTypeArchivesControllerClass;
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
                    '_format' => 'json'
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
                    '_format' => 'json'
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
                    '_controller' => $this->nodesSourcesListingApiControllerClass . '::defaultAction',
                    '_format' => 'json',
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

        $this->add(
            'get_search_nodes_sources',
            new Route(
                $this->routePrefix . '/nodes-sources/search',
                [
                    '_controller' => $this->nodesSourcesSearchApiControllerClass . '::defaultAction',
                    '_format' => 'json',
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

        $this->add(
            'get_single_nodes_sources_by_path',
            new Route(
                $this->routePrefix . '/nodes-sources/by-path',
                [
                    '_controller' => $this->nodeTypeSingleControllerClass . '::byPathAction',
                    '_format' => 'json',
                    'nodeTypeId' => null
                ],
                [
                    'path' => '[\/[a-z0-9A-Z\-\_]+'
                ],
                [],
                '',
                [],
                ['GET'],
                ''
            )
        );

        try {
            if (null === $this->nodeTypeWhitelist) {
                /** @var NodeTypeInterface[] $nodeTypes */
                $nodeTypes = $this->nodeTypesBag->all();
                foreach ($nodeTypes as $nodeType) {
                    $this->addCollection($this->getCollectionForNodeType($nodeType));
                }
            } else {
                foreach ($this->nodeTypeWhitelist as $nodeTypeName) {
                    /** @var NodeTypeInterface|null $nodeType */
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

    private function getCollectionForNodeType(NodeTypeInterface $nodeType): RouteCollection
    {
        if (!method_exists($nodeType, 'getId')) {
            throw new \InvalidArgumentException('Node-type must implement getId method.');
        }
        $collection = new RouteCollection();
        $collection->add(
            'get_listing_'.mb_strtolower($nodeType->getName()),
            new Route(
                $this->routePrefix . '/' . mb_strtolower($nodeType->getName()),
                [
                    '_controller' => $this->nodeTypeListingControllerClass . '::defaultAction',
                    '_format' => 'json',
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
                    '_format' => 'json',
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
            'get_archives_'.mb_strtolower($nodeType->getName()),
            new Route(
                $this->routePrefix . '/' . mb_strtolower($nodeType->getName()) . '/archives',
                [
                    '_controller' => $this->nodeTypeArchivesControllerClass . '::defaultAction',
                    '_format' => 'json',
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
                    '_format' => 'json',
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
            'get_localized_single_'.mb_strtolower($nodeType->getName()),
            new Route(
                $this->routePrefix . '/' . mb_strtolower($nodeType->getName()) . '/{id}/{_locale}',
                [
                    '_controller' => $this->nodeTypeSingleControllerClass . '::defaultAction',
                    '_format' => 'json',
                    'nodeTypeId' => $nodeType->getId()
                ],
                [
                    'id' => '[0-9]+',
                    '_locale' => '[a-z]{2,3}'
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
                    '_format' => 'json',
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
