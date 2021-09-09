<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\ListManagers;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\ListManagers\QueryBuilderListManager;
use Symfony\Component\HttpFoundation\Request;

final class ArchiveQueryBuilderListManager extends QueryBuilderListManager
{
    private string $dateField;

    private array $dates = [];

    public function __construct(
        ?Request $request,
        QueryBuilder $queryBuilder,
        string $dateField = 'publishedAt',
        string $identifier = 'obj',
        bool $debug = false
    ) {
        parent::__construct($request, $queryBuilder, $identifier, $debug);
        $this->dateField = $dateField;
    }


    /**
     * @return Paginator
     */
    protected function getPaginator(): Paginator
    {
        if (null === $this->paginator) {
            $this->paginator = new Paginator($this->queryBuilder, false);
            $this->paginator->setUseOutputWalkers(false);
            /*
             * disable pagination to get all archives
             */
            $this->paginator->getQuery()->setMaxResults(null);
            $this->paginator->getQuery()->setFirstResult(null);
            $this->setItemPerPage(9999999);

            foreach ($this->paginator as $datetime) {
                $year = $datetime[$this->dateField]->format('Y');
                $month = $datetime[$this->dateField]->format('Y-m');

                if (!isset($this->dates[$year])) {
                    $this->dates[$year] = [];
                }
                if (!isset($this->dates[$month])) {
                    $this->dates[$year][$month] = new \DateTime($datetime[$this->dateField]->format('Y-m-01'));
                }
            }
        }
        return $this->paginator;
    }

    /**
     * @inheritDoc
     */
    public function getItemCount(): int
    {
        $this->getPaginator();
        return count($this->dates);
    }

    /**
     * @inheritDoc
     */
    public function getEntities()
    {
        $this->getPaginator();
        return $this->dates;
    }
}
