<?php

namespace Bonnier\Willow\Base\Repositories;

use Illuminate\Support\Collection;

class WpModelRepository
{
    private static $instance;
    /** @var Collection */
    private $posts;
    /** @var Collection */
    private $terms;
    /** @var Collection */
    private $users;
    /** @var Collection */
    private $acfData;
    /** @var Collection */
    private $postMeta;
    /** @var Collection */
    private $termMeta;
    /** @var Collection */
    private $attachmentMeta;
    /** @var Collection */
    private $userMeta;
    /** @var Collection */
    private $permalinks;
    /** @var Collection */
    private $termlinks;
    /** @var Collection */
    private $taglinks;

    private function __construct()
    {
        $this->posts = new Collection();
        $this->terms = new Collection();
        $this->users = new Collection();
        $this->acfData = new Collection();
        $this->postMeta = new Collection();
        $this->termMeta = new Collection();
        $this->attachmentMeta = new Collection();
        $this->userMeta = new Collection();
        $this->permalinks = new Collection();
        $this->termlinks = new Collection();
        $this->taglinks = new Collection();
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new WpModelRepository();
        }

        return self::$instance;
    }

    /**
     * @param int|array|\WP_Post $wpPost
     *
     * @return \WP_Post|null
     */
    public function getPost($wpPost)
    {
        $postId = is_int($wpPost) ? $wpPost : array_get($wpPost, 'ID', data_get($wpPost, 'ID'));
        if (!$postId) {
            return null;
        }

        if ($wpPost instanceof \WP_Post) {
            $this->posts->put($postId, $wpPost);
            return $wpPost;
        } elseif (is_array($wpPost) && array_key_exists('ID', $wpPost)) {
            $post = new \WP_Post((object)$wpPost);
            $this->posts->put($postId, $post);
            return $post;
        }

        if (($post = $this->posts->get($postId, false)) && $post !== false) {
            return $post;
        }

        $post = get_post($postId);
        $this->posts->put($postId, $post);
        if ($post instanceof \WP_Post) {
            return $post;
        }

        return null;
    }

    public function getTerm($wpTerm): ?\WP_Term
    {
        $termId = is_int($wpTerm) ? $wpTerm : array_get($wpTerm, 'term_id', data_get($wpTerm, 'term_id'));
        if (!$termId) {
            return null;
        }

        if ($wpTerm instanceof \WP_Term) {
            $this->terms->put($termId, $wpTerm);
            return $wpTerm;
        }

        if (($term = $this->terms->get($termId, false)) && $term !== false) {
            return $term;
        }


        $term = get_term($termId);
        $this->terms->put($termId, $term);
        if ($term instanceof \WP_Term) {
            return $term;
        }

        return null;
    }

    public function getUser($wpUser): ?\WP_User
    {
        $userId = is_int($wpUser) ? $wpUser : array_get($wpUser, 'ID', data_get($wpUser, 'ID'));

        if (!$userId) {
            return null;
        }

        if ($wpUser instanceof \WP_User) {
            $this->users->put($userId, $wpUser);
            return $wpUser;
        }

        if (($user = $this->users->get($userId, false)) && $user !== false) {
            if ($user instanceof \WP_User) {
                return $user;
            }
            return null;
        }

        $user = get_user_by('ID', $userId);
        $this->users->put($userId, $user);
        if ($user instanceof \WP_User) {
            return $user;
        }
        return null;
    }

    /**
     * @param int|string|array|\WP_Post|\WP_Term $wpPost
     *
     * @return array|null
     */
    public function getAcfData($wpPost): ?array
    {
        if (is_int($wpPost) || is_string($wpPost)) {
            $wpId = $wpPost;
        } elseif (is_array($wpPost)) {
            $wpId = array_get($wpPost, 'ID') ?: array_get($wpPost, 'term_id');
        } else {
            $wpId = data_get($wpPost, 'ID') ?: data_get($wpPost, 'term_id');
        }

        if (!$wpId) {
            return null;
        }

        if (($data = $this->acfData->get($wpId, false)) && $data !== false) {
            $acfData = $data;
        } else {
            $acfData = get_fields($wpId);
            $this->acfData->put($wpId, $acfData);
        }

        return is_array($acfData) ? $acfData : null;
    }

    /**
     * @param int|string|array $wpPost
     * @param string $field
     *
     * @return mixed|null
     */
    public function getAcfField($wpPost, string $field)
    {
        if ($data = $this->getAcfData($wpPost)) {
            return array_get($data, $field);
        }

        return null;
    }

    /**
     * @param int|array|\WP_Post $wpPost
     *
     * @return array|null
     */
    public function getPostMeta($wpPost): ?array
    {
        $wpPostId = is_int($wpPost) ? $wpPost : array_get($wpPost, 'ID', data_get($wpPost, 'ID'));
        if (!$wpPostId) {
            return null;
        }

        if (($postMeta = $this->postMeta->get($wpPostId, false)) && $postMeta !== false) {
            return $postMeta;
        }

        $postMeta = get_post_meta($wpPostId);
        if(isset($postMeta['description'][0]) && empty($postMeta['description'][0])){
            $pageID = get_option('page_on_front');
            update_post_meta($wpPostId, 'description', get_post_meta( $pageID, 'teaser_description' )[0]);
        }        
        $this->postMeta->put($wpPostId, $postMeta);

        return $postMeta;
    }

    /**
     * @param int|array|\WP_Term $wpTerm
     *
     * @return array|null
     */
    public function getTermMeta($wpTerm): ?array
    {
        $wpTermId = is_int($wpTerm) ? $wpTerm : array_get($wpTerm, 'term_id', data_get($wpTerm, 'term_id'));
        if (!$wpTermId) {
            return null;
        }

        if (($termMeta = $this->termMeta->get($wpTermId, false)) && $termMeta !== false) {
            return $termMeta;
        }

        $termMeta = get_term_meta($wpTermId);
        $this->termMeta->put($wpTermId, $termMeta);

        return $termMeta;
    }

    /**
     * @param int|array|\WP_Post $wpPost
     *
     * @return array|null
     */
    public function getAttachmentMeta($wpPost): ?array
    {
        $wpId = is_int($wpPost) ? $wpPost : array_get($wpPost, 'ID', data_get($wpPost, 'ID'));
        if (!$wpId) {
            return null;
        }

        if (($attachmentMeta = $this->attachmentMeta->get($wpId, false)) && $attachmentMeta !== false) {
            if (is_array($attachmentMeta)) {
                return $attachmentMeta;
            }
            return null;
        }

        $attachmentMeta = wp_get_attachment_metadata($wpId);
        $this->attachmentMeta->put($wpId, $attachmentMeta);

        if (is_array($attachmentMeta)) {
            return $attachmentMeta;
        }
        return null;
    }

    /**
     * @param int|array|\WP_User $wpUser
     *
     * @return array|null
     */
    public function getUserMeta($wpUser): ?array
    {
        $wpUserId = is_int($wpUser) ? $wpUser : array_get($wpUser, 'ID', data_get($wpUser, 'ID'));
        if (!$wpUserId) {
            return null;
        }

        if (($userMeta = $this->userMeta->get($wpUserId, false)) !== false) {
            if (is_array($userMeta)) {
                return $userMeta;
            }
            return null;
        }
        $userMeta = get_user_meta($wpUserId);
        $this->userMeta->put($wpUserId, $userMeta);

        if (is_array($userMeta)) {
            return $userMeta;
        }

        return null;
    }

    public function getPermalink(int $postId): ?string
    {
        if (($permalink = $this->permalinks->get($postId)) && $permalink !== false) {
            return $permalink;
        }

        $permalink = get_permalink($postId);
        $this->permalinks->put($postId, $permalink);
        return $permalink;
    }

    public function getTermlink(int $termId): ?string
    {
        if (($termlink = $this->termlinks->get($termId, false)) && $termlink !== false) {
            return $termlink;
        }

        $termlink = get_term_link($termId);
        $this->termlinks->put($termId, $termlink);
        return $termlink;
    }

    public function getTagLink(int $tagId): ?string
    {
        if (($taglink = $this->taglinks->get($tagId, false)) && $taglink !== false) {
            return $taglink;
        }

        $taglink = get_term_link($tagId);
        $this->taglinks->put($tagId, $taglink);
        return $taglink;
    }
}
