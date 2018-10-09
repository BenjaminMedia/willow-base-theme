<?php

namespace Bonnier\Willow\Base\Adapters\Wp\Pages\Contents\Types;

use Bonnier\Willow\Base\Adapters\Wp\Composites\CompositeAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Pages\Contents\AbstractContentAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Root\ImageAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Root\NativeVideoAdapter;
use Bonnier\Willow\Base\Models\Base\Composites\Composite;
use Bonnier\Willow\Base\Models\Base\Root\Image;
use Bonnier\Willow\Base\Models\Base\Root\NativeVideo;
use Bonnier\Willow\Base\Models\Contracts\Composites\CompositeContract;
use Bonnier\Willow\Base\Models\Contracts\Pages\Contents\Types\FeaturedContentContract;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;
use Bonnier\Willow\Base\Models\Contracts\Root\NativeVideoContract;
use Bonnier\WP\ContentHub\Editor\Helpers\SortBy;

class FeaturedContentAdapter extends AbstractContentAdapter implements FeaturedContentContract
{
    public function getImage(): ?ImageContract
    {
        if (($imageId = array_get($this->acfArray, 'image')) && $image = get_post($imageId)) {
            return new Image(new ImageAdapter($image));
        }

        return null;
    }

    public function getVideo(): ?NativeVideoContract
    {
        if (($videoId = array_get($this->acfArray, 'video')) && $video = get_post($videoId)) {
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
        if (($composites = SortBy::getComposites($this->acfArray)) && $composites->isNotEmpty()) {
            return $composites->first();
        }

        return null;
    }
}