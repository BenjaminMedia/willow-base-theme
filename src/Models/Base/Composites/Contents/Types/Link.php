<?php

namespace Bonnier\Willow\Base\Models\Base\Composites\Contents\Types;

use Bonnier\Willow\Base\Models\Base\Composites\Contents\AbstractContent;
use Bonnier\Willow\Base\Models\Contracts\Root\LinkContract;

/**
 * Class Link
 *
 * @property LinkContract $model
 *
 * @package Bonnier\Willow\Base\Models\Base\Composites\Contents\Types
 */
class Link extends AbstractContent implements LinkContract
{
    /**
     * Link constructor.
     *
     * @param \Bonnier\Willow\Base\Models\Contracts\Root\LinkContract $link
     */
    public function __construct(LinkContract $link)
    {
        parent::__construct($link);
    }

    public function getTitle(): ?string
    {
        return $this->model->getTitle();
    }

    public function getUrl(): ?string
    {
        return $this->model->getUrl();
    }

    public function getTarget(): ?string
    {
        return $this->model->getTarget();
    }

    public function getStickToNext(): bool
    {
        return $this->model->getStickToNext();
    }
}
