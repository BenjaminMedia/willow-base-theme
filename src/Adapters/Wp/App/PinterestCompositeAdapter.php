<?php

namespace Bonnier\Willow\Base\Adapters\Wp\App;

use Bonnier\Willow\Base\Adapters\Wp\App\Partials\SocialFeedImageAdapter;
use Bonnier\Willow\Base\Adapters\Wp\App\Partials\SocialFeedTeaserAdapter;
use Bonnier\Willow\Base\Models\Base\Composites\Contents\Types\ContentImage;
use Bonnier\Willow\Base\Models\Base\Root\Teaser;
use Bonnier\Willow\Base\Models\Contracts\Composites\CompositeContract;
use Bonnier\Willow\Base\Models\Contracts\Composites\Contents\Types\ContentImageContract;
use Bonnier\Willow\Base\Models\Contracts\Root\AuthorContract;
use Bonnier\Willow\Base\Models\Contracts\Root\CommercialContract;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;
use Bonnier\Willow\Base\Models\Contracts\Root\TeaserContract;
use Bonnier\Willow\Base\Models\Contracts\Terms\CategoryContract;
use DateTime;
use Illuminate\Support\Collection;

class PinterestCompositeAdapter implements CompositeContract
{
    protected $pinterestContent;
    protected $image;

    public function __construct($pinterestContent)
    {
        $this->pinterestContent = $pinterestContent;
        if ($pinterestContent) {
            $this->image = new ContentImage(new SocialFeedImageAdapter($this->pinterestContent->image->original->url));
        }
    }

    public function getId(): int
    {
        return 0;
    }

    public function getTitle(): string
    {
        return 'Pinterest';
    }

    public function getDescription(): string
    {
        return $this->pinterestContent->note ?? '';
    }

    public function getLink(): string
    {
        return $this->pinterestContent->url ?? '';
    }

    public function getStatus(): ?string
    {
        return null;
    }

    public function getAuthor(): ?AuthorContract
    {
        return null;
    }

    public function getAuthorDescription(): ?string
    {
        return null;
    }

    public function getContents(): ?Collection
    {
        return null;
    }

    public function getCategory(): ?CategoryContract
    {
        return null;
    }

    public function getLeadImage(): ?ContentImageContract
    {
        return $this->image;
    }

    public function getFirstInlineImage(): ?ContentImageContract
    {
        return $this->image;
    }

    public function getFirstFileImage(): ?ContentImageContract
    {
        return $this->image;
    }

    public function getCommercialLabel(): ?string
    {
        return null;
    }

    public function getCommercialType(): ?string
    {
        return null;
    }

    public function getCommercialLogo(): ?ImageContract
    {
        return null;
    }

    public function getLabel(): ?string
    {
        return null;
    }

    public function getLabelLink(): ?string
    {
        return null;
    }

    public function getPublishedAt(): ?DateTime
    {
        return null;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return null;
    }

    public function getCommercial(): ?CommercialContract
    {
        return null;
    }

    public function getTeaser(string $type): ?TeaserContract
    {
        return new Teaser(new SocialFeedTeaserAdapter($this, $type));
    }

    public function getTeasers(): ?Collection
    {
        return collect($this->getTeaser('default'));
    }

    public function getLocale(): ?string
    {
        return null;
    }

    public function getTags(): Collection
    {
        return collect([]);
    }

    public function getCanonicalUrl(): ?string
    {
        return null;
    }

    public function getTemplate(): ?string
    {
        return null;
    }

    public function getVocabularies(): ?Collection
    {
        return collect([]);
    }

    public function getEstimatedReadingTime(): ?string
    {
        return null;
    }
}
