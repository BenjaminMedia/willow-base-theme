<?php

namespace Bonnier\Willow\Base\Transformers\Api\Pages\Includes\Contents;

use Bonnier\Willow\Base\Models\Contracts\Pages\Contents\ContentContract;
use Bonnier\Willow\Base\Transformers\Api\Pages\Includes\Contents\Types\TeaserListTransformer;
use Bonnier\Willow\Base\Transformers\NullTransformer;
use League\Fractal\TransformerAbstract;

class ContentTransformer extends TransformerAbstract
{
    protected $transformerMapping = [
        'teaser_list' => TeaserListTransformer::class
    ];
    public function transform(ContentContract $content)
    {
        $transformerClass = collect($this->transformerMapping)->get($content->getType(), NullTransformer::class);
        $transformedData = with(new $transformerClass())->transform($content);
        return array_merge([
            'type'   => $content->getType(),
        ], $transformedData);
    }
}