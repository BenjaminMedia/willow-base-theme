<?php

namespace Bonnier\Willow\Base\Models\Contracts\Composites\Contents\Types;

use Bonnier\Willow\Base\Models\Contracts\Composites\Contents\ContentContract;

interface NewsletterContract extends ContentContract
{
    public function getTitle(): ?string;

    public function getDescription(): ?string;

    public function getSourceCode(): ?int;

    public function getPermissionText(): ?string;
}
