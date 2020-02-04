<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\src\Routing;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Routing\DeferredRouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;
use Themes\AbstractApiTheme\src\Controllers\NodeTypeApiController;
use Themes\AbstractApiTheme\src\Controllers\RootApiController;

final class ApiRouteCollection extends DeferredRouteCollection
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
     * ApiRouteCollection constructor.
     *
     * @param NodeTypes              $nodeTypesBag
     * @param Settings               $settingsBag
     * @param Stopwatch|null         $stopwatch
     * @param bool                   $isPreview
     * @param string                 $apiPrefix
     * @param string                 $apiVersion
     */
    public function __construct(
        NodeTypes $nodeTypesBag,
        Settings $settingsBag,
        Stopwatch $stopwatch = null,
        $isPreview = false,
        $apiPrefix = '/api',
        $apiVersion = '1.0'
    ) {
        $this->stopwatch = $stopwatch;
        $this->isPreview = $isPreview;
        $this->settingsBag = $settingsBag;
        $this->nodeTypesBag = $nodeTypesBag;
        $this->apiVersion = $apiVersion;
        $this->apiPrefix = $apiPrefix;

        $this->routePrefix = $this->apiPrefix . '/' . $this->apiVersion;
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
                    '_controller' => RootApiController::class . '::getRootAction',
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
            /** @var NodeType[] $nodeTypes */
            $nodeTypes = $this->nodeTypesBag->all();
            /** @var NodeType $nodeType */
            foreach ($this->nodeTypesBag->all() as $nodeType) {
                $this->addCollection($this->getCollectionForNodeType($nodeType));
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
                    '_controller' => NodeTypeApiController::class . '::getListingAction',
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
                    '_controller' => NodeTypeApiController::class . '::getDetailAction',
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
        return $collection;
    }
}
