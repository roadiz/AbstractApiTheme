<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use RZ\Roadiz\CMS\Utils\NodeApi;
use RZ\Roadiz\CMS\Utils\TagApi;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\OptionsResolver\Options;

abstract class AbstractApiRequestOptionsResolver
{
    protected TagApi $tagApi;
    protected NodeApi $nodeApi;

    /**
     * @param TagApi      $tagApi
     * @param NodeApi     $nodeApi
     */
    public function __construct(TagApi $tagApi, NodeApi $nodeApi)
    {
        $this->tagApi = $tagApi;
        $this->nodeApi = $nodeApi;
    }

    /**
     * @return array Options only used for request and views, not filtering database entities
     */
    abstract protected function getMetaOptions(): array;

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected function normalizeBoolean($value): bool
    {
        return $value === true ||
            $value === 'true' ||
            $value === 'ON' ||
            $value === 'on' ||
            $value === 'yes' ||
            $value === '1' ||
            $value === 1;
    }

    /**
     * @param mixed $value
     *
     * @return array|\DateTime
     * @throws \Exception
     */
    protected function normalizeDateTimeFilter($value)
    {
        if (is_string($value)) {
            return new \DateTime($value);
        }
        if (is_array($value)) {
            if (isset($value['after']) && isset($value['before'])) {
                return ['BETWEEN', new \DateTime($value['after']), new \DateTime($value['before'])];
            }
            if (isset($value['strictly_after']) && isset($value['strictly_before'])) {
                return ['BETWEEN', new \DateTime($value['strictly_after']), new \DateTime($value['strictly_before'])];
            }
            if (isset($value['after'])) {
                return ['>=', new \DateTime($value['after'])];
            }
            if (isset($value['strictly_after'])) {
                return ['>', new \DateTime($value['strictly_after'])];
            }
            if (isset($value['before'])) {
                return ['<=', new \DateTime($value['before'])];
            }
            if (isset($value['strictly_before'])) {
                return ['<', new \DateTime($value['strictly_before'])];
            }
        }
        return $value;
    }

    protected function limitPublishedAtEndDate(\DateTime $endDate): \DateTime
    {
        $now = new \DateTime();
        if ($endDate > $now) {
            return $now;
        }
        return $endDate;
    }

    /**
     * Support archive parameter with year or year-month
     *
     * @param Options $options
     * @param mixed   $value
     *
     * @return array|\DateTime
     * @throws \Exception
     */
    protected function normalizePublishedAtFilter(Options $options, $value)
    {
        /*
         * Support archive parameter with year or year-month
         */
        if (null !== $options['archive'] && $options['archive'] !== '') {
            $archive = $options['archive'];
            if (preg_match('#[0-9]{4}\-[0-9]{2}\-[0-9]{2}#', $archive) > 0) {
                $startDate = new \DateTime($archive . ' 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1D'));

                return ['BETWEEN', $startDate, $this->limitPublishedAtEndDate($endDate)];
            } elseif (preg_match('#[0-9]{4}\-[0-9]{2}#', $archive) > 0) {
                $startDate = new \DateTime($archive . '-01 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1M'));

                return ['BETWEEN', $startDate, $this->limitPublishedAtEndDate($endDate)];
            } elseif (preg_match('#[0-9]{4}#', $archive) > 0) {
                $startDate = new \DateTime($archive . '-01-01 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1Y'));

                return ['BETWEEN', $startDate, $this->limitPublishedAtEndDate($endDate)];
            }
        }
        return $this->normalizeDateTimeFilter($value);
    }

    /**
     * @param mixed $value
     *
     * @return Tag|null
     */
    protected function normalizeTagFilter($value): ?Tag
    {
        if (null !== $value && $value instanceof Tag) {
            return $value;
        }
        if (null !== $value && is_string($value)) {
            return $this->tagApi->getOneBy([
                'tagName' => $value,
            ]);
        }
        if (null !== $value && is_numeric($value)) {
            return $this->tagApi->getOneBy([
                'id' => $value,
            ]);
        }
        return null;
    }

    /**
     * @param mixed $value
     *
     * @return Node|null
     */
    protected function normalizeNodeFilter($value): ?Node
    {
        if (null !== $value && $value instanceof Node) {
            return $value;
        }
        if (null !== $value && is_numeric($value)) {
            return $this->nodeApi->getOneBy([
                'id' => $value,
            ]);
        }
        /*
         * Test against an IRI
         */
        if (null !== $value &&
            is_string($value) &&
            1 === preg_match(
                '#/(?<nodeType>[a-zA-Z\-\_0-9]+)/(?<id>[0-9]+)/(?<locale>[a-z]{2,3})#',
                $value,
                $matches
            )
        ) {
            return $this->nodeApi->getOneBy([
                'id' => (int) $matches['id'],
            ]);
        }
        /*
         * Test against nodeName
         */
        if (null !== $value && is_string($value)) {
            return $this->nodeApi->getOneBy([
                'nodeName' => $value,
            ]);
        }
        return null;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getCriteriaFromOptions(array &$options): array
    {
        $activeOptions = array_filter($options, function ($value) {
            return null !== $value;
        });
        return array_filter($activeOptions, function ($key) {
            return !array_key_exists($key, $this->getMetaOptions());
        }, ARRAY_FILTER_USE_KEY);
    }
}
