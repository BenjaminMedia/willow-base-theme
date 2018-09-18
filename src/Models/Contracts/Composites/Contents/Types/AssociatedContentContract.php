<?php

namespace Bonnier\Willow\Base\Models\Contracts\Composites\Contents\Types;

use Bonnier\Willow\Base\Models\Contracts\Composites\Contents\ContentContract;

interface AssociatedContentContract extends ContentContract
{
    public function getAssociatedComposite() : ?\WP_Post;
}