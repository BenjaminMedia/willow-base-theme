<?php

namespace Bonnier\Willow\Base\Models\Contracts\Composites\Contents\Types;

use Bonnier\Willow\Base\Models\Contracts\Composites\Contents\ContentContract;
use Bonnier\Willow\Base\Models\Contracts\Root\AudioContract;

interface ContentAudioContract extends ContentContract, AudioContract
{
    public function getAudioTitle() : ?string;
}