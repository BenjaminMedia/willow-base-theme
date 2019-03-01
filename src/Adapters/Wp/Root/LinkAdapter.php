<?php

namespace Bonnier\Willow\Base\Adapters\Wp\Root;

use Bonnier\Willow\Base\Models\Contracts\Root\LinkContract;

/**
 * Class LinkItemAdapter
 *
 * @package \Bonnier\Willow\Base\Adapters\Wp
 */
class LinkAdapter implements LinkContract
{
    protected $url;
    protected $title;
    protected $target;

    public function __construct(string $url = null, string $title = null, string $target = null)
    {
        $this->url = $url;
        $this->title = $title;
        $this->target = $target;
    }

    public function getTitle(): ?string
    {
        return $this->title ?: null;
    }

    public function getUrl(): ?string
    {
        return $this->url ?: null;
    }

    public function getTarget(): ?string
    {
        return $this->target ?: null;
    }
}
