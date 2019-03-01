<?php

namespace Bonnier\Willow\Base\Adapters\Wp\Pages\Contents\Types;

use Bonnier\Willow\Base\Adapters\Wp\Root\LinkAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Pages\Contents\AbstractContentAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Root\HyperlinkAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Root\ImageAdapter;
use Bonnier\Willow\Base\Models\Contracts\Pages\Contents\Types\CommercialSpotContract;
use Bonnier\Willow\Base\Repositories\WpModelRepository;
use Bonnier\Willow\Base\Models\Base\Root\Hyperlink;
use Bonnier\Willow\Base\Models\Base\Root\Image;
use Bonnier\Willow\Base\Models\Contracts\Root\HyperlinkContract;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;

class CommercialSpotAdapter extends AbstractContentAdapter implements CommercialSpotContract
{

    public function getTitle(): ?string
    {
        return array_get($this->acfArray, 'title') ?: null;
    }

    public function getDescription(): ?string
    {
        return array_get($this->acfArray, 'description') ?: null;
    }

    public function getImage(): ?ImageContract
    {
        if ($imageArray = array_get($this->acfArray, 'image')) {
            $image = WpModelRepository::instance()->getPost($imageArray);
            return new Image(new ImageAdapter($image));
        }

        return null;
    }

    public function getLink(): ?HyperlinkContract
    {
        if (($link = array_get($this->acfArray, 'link')) && ($label = array_get($this->acfArray, 'link_label'))) {
            return new Hyperlink(
                new HyperlinkAdapter(new LinkAdapter($link, $label))
            );
        }

        return null;
    }

    public function getLabel(): ?string
    {
        return array_get($this->acfArray, 'label') ?: null;
    }
}
