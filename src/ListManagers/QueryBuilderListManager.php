<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\ListManagers;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\ListManagers\AbstractEntityListManager;
use Symfony\Component\HttpFoundation\Request;

class QueryBuilderListManager extends AbstractEntityListManager
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var Paginator|null
     */
    protected $paginator;

    /**
     * @param Request|null $request
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(?Request $request, QueryBuilder $queryBuilder)
    {
        parent::__construct($request);
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @inheritDoc
     */
    public function handle($disabled = false)
    {
        if (false === $disabled && null !== $this->request) {
            if ($this->request->query->get('field') &&
                $this->request->query->get('ordering')) {
                $this->queryBuilder->addOrderBy(
                    $this->request->query->get('field'),
                    $this->request->query->get('ordering')
                );
                $this->queryArray['field'] = $this->request->query->get('field');
                $this->queryArray['ordering'] = $this->request->query->get('ordering');
            }

            if ($this->request->query->get('search') != "") {
                // TODO: handle search in QB
                $this->queryArray['search'] = $this->request->query->get('search');
            }

            if ($this->request->query->has('item_per_page') &&
                $this->request->query->get('item_per_page') > 0) {
                $this->setItemPerPage($this->request->query->get('item_per_page'));
            }

            if ($this->request->query->has('page') &&
                $this->request->query->get('page') > 1) {
                $this->setPage($this->request->query->get('page'));
            } else {
                $this->setPage(1);
            }
        } else {
            /*
             * Disable pagination and paginator
             */
            $this->disablePagination();
        }
    }

    /**
     * @inheritDoc
     */
    public function setPage($page)
    {
        parent::setPage($page);
        $this->queryBuilder->setFirstResult($this->getItemPerPage() * ($page - 1));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setItemPerPage($itemPerPage)
    {
        parent::setItemPerPage($itemPerPage);
        $this->queryBuilder->setMaxResults((int) $itemPerPage);
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function getItemCount()
    {
        if (null !== $this->paginator) {
            return $this->paginator->count();
        }
        throw new \InvalidArgumentException('Call EntityListManagerInterface::handle before counting entities.');
    }

    /**
     * @inheritDoc
     */
    public function getEntities()
    {
        if (null !== $this->paginator) {
            return $this->paginator;
        }
        throw new \InvalidArgumentException('Call EntityListManagerInterface::handle before getting entities.');
    }
}
