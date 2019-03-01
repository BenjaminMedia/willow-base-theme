<?php

namespace Bonnier\Willow\Base\Models\Base\Root;

use Bonnier\Willow\Base\Models\Contracts\Composites\Contents\Types\LinkContract;

class Link implements LinkContract
{
    protected $hyperlink;

    public function __construct(HyperlinkContract $hyperlink)
    {
        $this->hyperlink = $hyperlink;
    }

    public function getTitle(): ?string
    {
        return $this->hyperlink->getTitle();
    }

    public function getUrl(): ?string
    {
        return $this->hyperlink->getUrl();
    }

    public function getRelationship(): ?string
    {
        return $this->hyperlink->getRelationship();
    }

    public function getTarget(): ?string
    {
        return $this->hyperlink->getTarget();
    }

    public function getType(): ?string
    {
        // TODO: Implement getType() method.
    }

    public function isLocked(): bool
    {
        // TODO: Implement isLocked() method.
    }

    public function getStickToNext(): bool
    {
        // TODO: Implement getStickToNext() method.
    }
}
