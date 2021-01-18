<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\ListManagers;

use RZ\Roadiz\Core\ListManagers\QueryBuilderListManager;

final class TagQueryBuilderListManager extends QueryBuilderListManager
{
    /**
     * @inheritDoc
     */
    protected function handleSearchParam(string $search): void
    {
        /*
         * Use tt prefix for tagTranslation
         */
        $this->queryBuilder->andWhere($this->queryBuilder->expr()->orX(
            $this->queryBuilder->expr()->like($this->identifier . '.tagName', ':search'),
            $this->queryBuilder->expr()->like('tt.name', ':search')
        ))->setParameter('search', '%' . $search . '%');
    }
}
