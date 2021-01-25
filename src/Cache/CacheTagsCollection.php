<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;

final class CacheTagsCollection extends ArrayCollection
{
    public function addNode(Node $node): CacheTagsCollection
    {
        $cacheTag = 'n'.$node->getId();
        if (!$this->contains($cacheTag)) {
            $this->add($cacheTag);
        }
        return $this;
    }

    public function addTag(Tag $tag): CacheTagsCollection
    {
        $cacheTag = 't'.$tag->getId();
        if (!$this->contains($cacheTag)) {
            $this->add($cacheTag);
        }
        return $this;
    }

    public function addDocument(Document $document): CacheTagsCollection
    {
        $cacheTag = 'd'.$document->getId();
        if (!$this->contains($cacheTag)) {
            $this->add($cacheTag);
        }
        return $this;
    }
}
