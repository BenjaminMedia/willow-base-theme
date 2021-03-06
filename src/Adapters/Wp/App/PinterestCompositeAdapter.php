<?php

namespace Bonnier\Willow\Base\Adapters\Wp\App;

use Bonnier\Willow\Base\Adapters\Wp\App\Partials\SocialFeedImageAdapter;
use Bonnier\Willow\Base\Adapters\Wp\App\Partials\SocialFeedTeaserAdapter;
use Bonnier\Willow\Base\Models\Base\Composites\Contents\Types\ContentImage;
use Bonnier\Willow\Base\Models\Base\Root\Teaser;
use Bonnier\Willow\Base\Models\Contracts\Composites\CompositeContract;
use Bonnier\Willow\Base\Models\Contracts\Composites\Contents\StoryContract;
use Bonnier\Willow\Base\Models\Contracts\Composites\Contents\Types\ContentImageContract;
use Bonnier\Willow\Base\Models\Contracts\Root\AudioContract;
use Bonnier\Willow\Base\Models\Contracts\Root\AuthorContract;
use Bonnier\Willow\Base\Models\Contracts\Root\CommercialContract;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;
use Bonnier\Willow\Base\Models\Contracts\Root\TeaserContract;
use Bonnier\Willow\Base\Models\Contracts\Terms\CategoryContract;
use Bonnier\WP\Cxense\Services\WidgetDocumentQuery;
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
            $this->image = new ContentImage(
                new SocialFeedImageAdapter($this->pinterestContent->image->original->url)
            );
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

    public function getDescription(): ?string
    {
        return optional($this->pinterestContent)->note ?: null;
    }

    public function getLink(): ?string
    {
        return optional($this->pinterestContent)->url ?: null;
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

    public function getOtherAuthors(): ?Collection
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

    public function getHideInSitemaps(): ?bool
    {
        return null;
    }

    public function getTemplate(): ?string
    {
        return null;
    }

    public function getVocabularies(): ?Collection
    {
        return null;
    }

    public function getEstimatedReadingTime(): ?int
    {
        return null;
    }

    public function getKind(): ?string
    {
        return null;
    }

    public function getParent(): ?int
    {
        return null;
    }

    public function getStory(): ?StoryContract
    {
        return null;
    }

    public function getAudio(): ?AudioContract
    {
        return null;
    }

    public function getWordCount(): ?int
    {
        return null;
    }

    public function getTranslations(): ?Collection
    {
        return null;
    }

    public function getExcludePlatforms(): ?Collection
    {
        return null;
    }

    public function getCtmDisabled(): bool
    {
        return false;
    }

    public function getShellLink(): ?string
    {
        return null;
    }

    public function getRelatedByCategory(WidgetDocumentQuery $manualQuery = null): ?Collection
    {
        return null;
    }

    public function getRelatedByCategoryQuery(): ?WidgetDocumentQuery
    {
        return null;
    }

    public function getContenthubId(): ?string
    {
        return null;
    }

    public function getEditorialType(): ?string
    {
        return null;
    }
}
