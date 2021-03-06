<?php

namespace Bonnier\Willow\Base\Adapters\Wp\Pages;

use Bonnier\Willow\Base\Adapters\Wp\Root\AbstractTeaserAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Root\ImageAdapter;
use Bonnier\Willow\Base\Repositories\WpModelRepository;
use Bonnier\Willow\Base\Models\Base\Root\Image;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;

class PageTeaserAdapter extends AbstractTeaserAdapter
{
    protected $page;

    public function __construct(PageAdapter $page, $type)
    {
        $this->page = $page;
        parent::__construct($type);
    }

    public function getTitle(): ?string
    {
        if ($title = array_get($this->page->getAcfFields(), $this->type . 'teaser_title')) {
            return $title;
        }

        if ($title = array_get($this->page->getAcfFields(), 'teaser_title')) {
            return $title;
        }

        return optional($this->page)->getTitle() ?: null;
    }

    public function getImage(): ?ImageContract
    {
        if ($imageArray = array_get($this->page->getAcfFields(), $this->type . 'teaser_image')) {
            $image = WpModelRepository::instance()->getPost($imageArray);
            return new Image(new ImageAdapter($image));
        }

        if ($imageArray = array_get($this->page->getAcfFields(), 'teaser_image')) {
            $image = WpModelRepository::instance()->getPost($imageArray);
            return new Image(new ImageAdapter($image));
        }

        return null;
    }

    public function getVideoUrl(): ?string
    {
        return $this->acfArray['video_url'] ?? null;
    }


    public function getDescription(): ?string
    {
        if ($description = array_get($this->page->getAcfFields(), $this->type . 'teaser_description')) {
            return $description;
        }

        if ($description = array_get($this->page->getAcfFields(), 'teaser_description')) {
            return $description;
        }

        return null;
    }
}
