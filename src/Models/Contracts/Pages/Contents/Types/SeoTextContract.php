<?php

namespace Bonnier\Willow\Base\Models\Contracts\Pages\Contents\Types;

use Bonnier\Willow\Base\Models\Contracts\Pages\Contents\ContentContract;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;

interface SeoTextContract extends ContentContract
{
    public function getTitle(): ?string;
    public function getDescription(): ?string;
    public function getImage(): ?ImageContract;
    public function getImagePosition(): ?string;
}
