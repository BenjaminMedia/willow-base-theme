<?php

namespace Bonnier\Willow\Base\Transformers\Api\Composites;

use Bonnier\Willow\Base\Adapters\Wp\Composites\CompositeAdapter;
use Bonnier\Willow\Base\Models\Base\Composites\Composite;
use Bonnier\Willow\Base\Models\Base\Composites\Contents\Types\AssociatedContent;
use Bonnier\Willow\Base\Models\Contracts\Composites\CompositeContract;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;
use Bonnier\Willow\Base\Traits\UrlTrait;
use Bonnier\Willow\Base\Transformers\Api\Root\CommercialTransformer;
use Bonnier\Willow\Base\Transformers\Api\Root\ImageTransformer;
use Bonnier\Willow\Base\Transformers\Api\Terms\Vocabulary\VocabularyTransformer;
use function DeliciousBrains\WP_Offload_S3\Aws3\Aws\map;
use League\Fractal\TransformerAbstract;

class CompositeTeaserTransformer extends TransformerAbstract
{
    use UrlTrait;

    protected $originalResponseData;

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'vocabularies',
        'associated'
    ];

    /**
     * CategoryTransformer constructor.
     *
     * @param $originalResponseData
     */
    public function __construct(array $originalResponseData = [])
    {
        $this->originalResponseData = $originalResponseData;
    }

    public function transform(CompositeContract $composite)
    {
        return [
            'id'            => $composite->getId(),
            'title'         => $this->getTitle($composite),
            'kind'          => $composite->getKind(),
            'status'        => $composite->getStatus(),
            'image'         => $this->getImage($composite),
            'description'   => $this->getDescription($composite),
            'link'          => $this->getPath($composite->getLink()),
            'published_at'  => $composite->getPublishedAt(),
            'commercial'    => $this->getCommercial($composite),
            'label'         => [
                'title' => $composite->getLabel(),
                'url'   => $this->getPath($composite->getLabelLink()),
            ],
            'estimated_reading_time'  => $composite->getEstimatedReadingTime(),
        ];
    }

    private function getTitle(CompositeContract $composite)
    {
        return $composite->getTeaser('default')->getTitle();
    }

    private function getImage(CompositeContract $composite)
    {
        return $this->transformTeaserImage($composite->getTeaser('default')->getImage());
    }

    private function getDescription(CompositeContract $composite)
    {
        return $composite->getTeaser('default')->getDescription();
    }

    private function transformTeaserImage(?ImageContract $image)
    {
        if (!$image) {
            return null;
        }
        return with(new ImageTransformer())->transform($image);
    }

    private function getCommercial(CompositeContract $composite)
    {
        $commercial = $composite->getCommercial();
        return $commercial ? with(new CommercialTransformer())->transform($commercial) : null;
    }

    public function includeVocabularies(CompositeContract $composite)
    {
        return $this->collection($composite->getVocabularies(), new VocabularyTransformer());
    }

    public function includeAssociated(CompositeContract $composite){
        if($composite->getKind() !== 'Story'){
            return [];
        }
        return $this->collection($composite->getAssociatedComposites(), new CompositeTeaserTransformer());
    }
}
