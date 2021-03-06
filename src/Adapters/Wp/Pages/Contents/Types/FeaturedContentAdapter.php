<?php

namespace Bonnier\Willow\Base\Adapters\Wp\Pages\Contents\Types;

use Bonnier\Willow\Base\Adapters\Wp\Composites\CompositeAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Pages\Contents\AbstractContentAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Root\ImageAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Root\NativeVideoAdapter;
use Bonnier\Willow\Base\Helpers\SortBy;
use Bonnier\Willow\Base\Repositories\WpModelRepository;
use Bonnier\Willow\Base\Models\Base\Composites\Composite;
use Bonnier\Willow\Base\Models\Base\Root\Image;
use Bonnier\Willow\Base\Models\Base\Root\NativeVideo;
use Bonnier\Willow\Base\Models\Contracts\Composites\CompositeContract;
use Bonnier\Willow\Base\Models\Contracts\Pages\Contents\Types\FeaturedContentContract;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;
use Bonnier\Willow\Base\Models\Contracts\Root\NativeVideoContract;

class FeaturedContentAdapter extends AbstractContentAdapter implements FeaturedContentContract
{
    public function getImage(): ?ImageContract
    {
        if ($imageArray = array_get($this->acfArray, 'image')) {
            $image = WpModelRepository::instance()->getPost($imageArray);
            return new Image(new ImageAdapter($image));
        }

        return null;
    }

    public function getVideo(): ?NativeVideoContract
    {
        if ($videoArray = array_get($this->acfArray, 'video')) {
            $video = WpModelRepository::instance()->getPost($videoArray);
            return new NativeVideo(new NativeVideoAdapter($video));
        }

        return null;
    }

    public function getDisplayHint(): ?string
    {
        return array_get($this->acfArray, 'display_hint') ?: null;
    }

    public function getComposite(): ?CompositeContract
    {
        if (($result = SortBy::getComposites($this->acfArray)) && $result['composites']->isNotEmpty()) {
            if ($composite = $result['composites']->first()) {
                return new Composite(new CompositeAdapter($composite));
            }
        }

        return null;
    }

    public function getLabel(): ?string
    {
        return array_get($this->acfArray, 'label') ?: null;
    }
}
