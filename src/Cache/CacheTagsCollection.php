<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;

final class CacheTagsCollection extends ArrayCollection
{
    public function getCacheTagForNode(Node $node): string
    {
        return 'n'.$node->getId();
    }

    public function addNode(Node $node): CacheTagsCollection
    {
        $cacheTag = $this->getCacheTagForNode($node);
        if (!$this->contains($cacheTag)) {
            $this->add($cacheTag);
        }
        return $this;
    }

    public function getCacheTagForTag(Tag $tag): string
    {
        return 't'.$tag->getId();
    }

    public function addTag(Tag $tag): CacheTagsCollection
    {
        $cacheTag = $this->getCacheTagForTag($tag);
        if (!$this->contains($cacheTag)) {
            $this->add($cacheTag);
        }
        return $this;
    }

    public function getCacheTagForDocument(Document $document): string
    {
        return 'd'.$document->getId();
    }

    public function addDocument(Document $document): CacheTagsCollection
    {
        $cacheTag = $this->getCacheTagForDocument($document);
        if (!$this->contains($cacheTag)) {
            $this->add($cacheTag);
        }
        return $this;
    }
}
