<?php

namespace Bonnier\Willow\Base\Adapters\Wp\Root;

use Bonnier\Willow\Base\Adapters\Wp\Root\LinkAdapter;
use Bonnier\Willow\Base\Models\Contracts\Root\HyperlinkContract;
use Bonnier\Willow\Base\Models\Contracts\Widgets\CommercialSpotHyperlinkContract;

class HyperlinkAdapter implements HyperlinkContract
{
    protected $link;
    protected $relationship;
    protected $target;

    public function __construct(LinkAdapter $link, string $relationship = null, string $target = null)
    {
        $this->link = $link;
        $this->relationship = $relationship;
        $this->target = $target;
    }

    public function getTitle(): ?string
    {
        return $this->link->getTitle() ?: null;
    }

    public function getUrl(): ?string
    {
        return $this->link->getUrl() ?: null;
    }

    public function getRelationship(): ?string
    {
        return $this->relationship ?: null;
    }

    public function getTarget(): ?string
    {
        return $this->target ?: null;
    }
}
