<?php

namespace Bonnier\Willow\Base\Models;

use Bonnier\Willow\Base\ACF\CustomRelationship;
use Bonnier\Willow\Base\Models\ACF\Page\PageFieldGroup;

class WpPage
{
    public static function register()
    {
        add_action('init', function () {
            CustomRelationship::register();
            PageFieldGroup::register();
        });
    }
}
