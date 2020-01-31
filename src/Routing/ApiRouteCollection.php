<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\src\Routing;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Routing\RoadizRouteCollection;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;
use Themes\AbstractApiTheme\src\Controllers\NodeTypeApiController;

final class ApiRouteCollection extends RoadizRouteCollection
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
     * ApiRouteCollection constructor.
     *
     * @param NodeTypes              $nodeTypesBag
     * @param ThemeResolverInterface $themeResolver
     * @param Settings               $settingsBag
     * @param Stopwatch|null         $stopwatch
     * @param bool                   $isPreview
     * @param string                 $apiPrefix
     * @param string                 $apiVersion
     */
    public function __construct(
        NodeTypes $nodeTypesBag,
        ThemeResolverInterface $themeResolver,
        Settings $settingsBag,
        Stopwatch $stopwatch = null,
        $isPreview = false,
        $apiPrefix = '/api',
        $apiVersion = '1.0'
    ) {
        parent::__construct($themeResolver, $settingsBag, $stopwatch, $isPreview);
        $this->nodeTypesBag = $nodeTypesBag;
        $this->apiVersion = $apiVersion;
        $this->apiPrefix = $apiPrefix;
    }

    public function parseResources(): void
    {
        parent::parseResources();

        try {
            /** @var NodeType[] $nodeTypes */
            $nodeTypes = $this->nodeTypesBag->all();

            /** @var NodeType $nodeType */
            foreach ($this->nodeTypesBag->all() as $nodeType) {
                if ($nodeType->isReachable()) {
                    $this->addCollection($this->getCollectionForNodeType($nodeType));
                }
            }
        } catch (\Exception $e) {
            /*
             * Database tables don't exist yet
             * Need Install
             */
        }
    }

    private function getCollectionForNodeType(NodeType $nodeType): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->add(
            'get_listing_'.mb_strtolower($nodeType->getName()),
            new Route(
                $this->apiPrefix . '/' . $this->apiVersion . '/' . mb_strtolower($nodeType->getName()),
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
                $this->apiPrefix . '/' . $this->apiVersion . '/' . mb_strtolower($nodeType->getName()) . '/{id}',
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
