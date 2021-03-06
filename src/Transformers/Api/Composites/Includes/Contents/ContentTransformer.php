<?php

namespace Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents;

use Bonnier\Willow\Base\Models\Contracts\Composites\Contents\ContentContract;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\AssociatedCompositesTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\CalculatorTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\ChaptersSummaryTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\ContentAudioTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\ContentFileTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\GalleryTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\ContentImageTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\HotspotImageTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\InfoBoxTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\InsertedCodeTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\InventoryTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\LeadParagraphTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\LinkTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\MultimediaTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\NewsletterTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\ParagraphListTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\ProductTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\QuoteTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\RecipeTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\TextItemTransformer;
use Bonnier\Willow\Base\Transformers\Api\Composites\Includes\Contents\Types\VideoTransformer;
use Bonnier\Willow\Base\Transformers\NullTransformer;
use League\Fractal\TransformerAbstract;

class ContentTransformer extends TransformerAbstract
{

    protected $transformerMapping = [
        'image'                 => ContentImageTransformer::class,
        'text_item'             => TextItemTransformer::class,
        'file'                  => ContentFileTransformer::class,
        'gallery'               => GalleryTransformer::class,
        'link'                  => LinkTransformer::class,
        'inserted_code'         => InsertedCodeTransformer::class,
        'video'                 => VideoTransformer::class,
        'infobox'               => InfoBoxTransformer::class,
        'associated_composites' => AssociatedCompositesTransformer::class,
        'audio'                 => ContentAudioTransformer::class,
        'quote'                 => QuoteTransformer::class,
        'paragraph_list'        => ParagraphListTransformer::class,
        'hotspot_image'         => HotspotImageTransformer::class,
        'lead_paragraph'        => LeadParagraphTransformer::class,
        'newsletter'            => NewsletterTransformer::class,
        'chapters_summary'      => ChaptersSummaryTransformer::class,
        'multimedia'            => MultimediaTransformer::class,
        'inventory'             => InventoryTransformer::class,
        'product'               => ProductTransformer::class,
        'recipe'                => RecipeTransformer::class,
        'calculator'            => CalculatorTransformer::class,
    ];

    public function transform(ContentContract $content)
    {
        $transformerClass = collect($this->transformerMapping)->get($content->getType(), NullTransformer::class);
        $transformedData = with(new $transformerClass())->transform($content);
        return array_merge([
            'type'          => $content->getType(),
            'locked'        => $content->isLocked(),
            'stick_to_next' => $content->getStickToNext(),
            'uuid'          => wp_generate_uuid4()
        ], $transformedData);
    }
}
