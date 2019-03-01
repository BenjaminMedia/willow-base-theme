<?php

namespace Bonnier\Willow\Base\Models\Contracts\Root;

interface LinkContract
{
    public function getTitle(): ?string;

    public function getUrl(): ?string;

    public function getTarget(): ?string;
}
