<?php

namespace Bonnier\Willow\Base\Transformers\Api\Root\Contents\Types;

use Bonnier\Willow\Base\Models\Contracts\Pages\Contents\Types\BannerPlacementContract;
use League\Fractal\TransformerAbstract;

class BannerPlacementTransformer extends TransformerAbstract
{
    public function transform(BannerPlacementContract $bannerPlacement)
    {
        return [];
    }
}
