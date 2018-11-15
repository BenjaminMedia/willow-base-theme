<?php

namespace Bonnier\Willow\Base\Adapters\Wp\Root;

use Bonnier\Willow\Base\Models\Base\Root\Image;
use Bonnier\Willow\Base\Models\Contracts\Root\AuthorContract;
use Bonnier\Willow\Base\Models\Contracts\Root\ImageContract;
use Bonnier\WP\ContentHub\Editor\Models\WpUserProfile;
use WP_User;

/**
 * Class AuthorAdapter
 *
 * @package \Bonnier\Willow\Base\Adapters\Wp
 */
class AuthorAdapter implements AuthorContract
{
    protected $user;

    /**
     * AuthorAdapter constructor.
     *
     * @param $user
     */
    public function __construct(WP_User $user = null)
    {
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return data_get($this->user, 'ID') ?: null;
    }

    public function getName(): ?string
    {
        return data_get($this->user, 'display_name') ?: null;
    }

    public function getBiography(): ?string
    {
        return data_get($this->user, 'description') ?: null;
    }

    public function getAvatar(): ?ImageContract
    {
        if ($avatar = WpUserProfile::getAvatarFromUser($this->getId())) {
            $postMeta = get_post_meta(data_get($avatar, 'ID'));
            $attachmentMeta = wp_get_attachment_metadata(data_get($avatar, 'ID'));
            return new Image(new ImageAdapter($avatar, $postMeta, $attachmentMeta));
        }
        return null;
    }

    public function getUrl(): ?string
    {
        return get_author_posts_url($this->getId()) ?: null;
    }

    public function getTitle(): ?string
    {
        return WpUserProfile::getTitle($this->getId()) ?: null;
    }
}
