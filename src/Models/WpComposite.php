<?php

namespace Bonnier\Willow\Base\Models;

use Bonnier\Willow\Base\Controllers\Root\CompositeRestController;
use Bonnier\Willow\Base\Helpers\EstimatedReadingTime;
use Bonnier\Willow\Base\Helpers\PermalinkHelper;
use Bonnier\Willow\Base\Helpers\Utils;
use Bonnier\Willow\Base\Models\ACF\Composite\CompositeFieldGroup;
use Bonnier\Willow\Base\Models\ACF\Composite\MetaFieldGroup;
use Bonnier\Willow\Base\Models\ACF\Composite\TaxonomyFieldGroup;
use Bonnier\Willow\Base\Models\ACF\Composite\TeaserFieldGroup;
use Bonnier\Willow\Base\Models\ACF\Composite\TranslationStateFieldGroup;
use Bonnier\Willow\Base\Repositories\SiteManager\SiteRepository;
use Bonnier\Willow\Base\Services\SiteManagerService;
use Bonnier\Willow\MuPlugins\Helpers\LanguageProvider;
use Bonnier\Willow\Base\Models\ACF\Composite\GuideMetaFieldGroup;

class WpComposite
{
    public const POST_TYPE = 'contenthub_composite';
    public const POST_TYPE_NAME = 'Content';
    public const POST_TYPE_NAME_SINGULAR = 'Composite';
    public const POST_SLUG = '%category%';
    public const POST_PERMALINK_STRUCTURE = '/%category%/%postname%';
    public const CATEGORY_BASE = '';
    public const POST_META_CONTENTHUB_ID = 'contenthub_id';
    public const POST_META_WHITE_ALBUM_ID = 'white_album_id';
    public const POST_META_WHITE_ALBUM_SOURCE = 'white_album_source';
    public const POST_META_CUSTOM_PERMALINK = 'custom_permalink';
    public const POST_TEASER_TITLE = 'teaser_title';
    public const POST_TEASER_DESCRIPTION = 'teaser_description';
    public const POST_TEASER_IMAGE = 'teaser_image';
    public const POST_META_TITLE = 'seo_teaser_title';
    public const POST_META_DESCRIPTION = 'seo_teaser_description';
    public const POST_CANONICAL_URL = 'canonical_url';
    public const POST_FACEBOOK_TITLE = 'fb_teaser_title';
    public const POST_FACEBOOK_DESCRIPTION = 'fb_teaser_description';
    public const POST_FACEBOOK_IMAGE = 'fb_teaser_image';
    public const POST_TWITTER_TITLE = 'tw_teaser_title';
    public const POST_TWITTER_DESCRIPTION = 'tw_teaser_description';
    public const POST_TWITTER_IMAGE = 'tw_teaser_image';
    public const POST_AUTHOR_DESCRIPTION = 'author_description';
    public const POST_OTHER_AUTHORS = 'other_authors';
    public const SLUG_CHANGE_HOOK = 'contenthub_composite_slug_change';

    /**
     * Register the composite as a custom wp post type
     */
    public static function register()
    {
        if (! class_exists('willow_permalinks')) {
            add_action('init', array(PermalinkHelper::class, 'instance'), 0);
        }
        add_filter('pre_option_permalink_structure', function ($currentSetting) {
            return static::POST_PERMALINK_STRUCTURE;
        });

        add_filter('pre_option_category_base', function ($currentSetting) {
            return static::CATEGORY_BASE;
        });

        add_action('init', function () {
            register_post_type(
                static::POST_TYPE,
                [
                    'labels' => [
                        'name' => __(static::POST_TYPE_NAME),
                        'singular_name' => __(static::POST_TYPE_NAME_SINGULAR)
                    ],
                    'public' => true,
                    'rest_base' => 'composites',
                    'rest_controller_class' => CompositeRestController::class,
                    'show_in_rest' => true, // enable rest api
                    'rewrite' => [
                        'willow_custom_permalink' => static::POST_PERMALINK_STRUCTURE,
                    ],
                    'has_archive' => false,
                    'supports' => [
                        'title',
                        'author'
                    ],
                    'taxonomies' => [
                        'category',
                        'post_tag'
                    ],
                ]
            );
            static::registerACFFields();
            static::loadTeaserCharacterCounter();
        });

        add_action('save_post', [__CLASS__, 'onSave'], 10, 2);
        add_action('save_post', [__CLASS__, 'onSaveRequiredTitle'], 10, 2);
        add_action('save_post', [__CLASS__, 'onSaveSlugChange'], 5, 2);
        add_action('added_term_relationship', [__CLASS__, 'addedTermRelationship'], 10, 3);
        add_action('acf/save_post', [EstimatedReadingTime::class, 'addEstimatedReadingTime'], 20);
        add_filter('pll_copy_post_metas', [__CLASS__, 'checkIfTermIsTranslated'], 10, 3);
        add_filter('pll_copy_post_metas', [__CLASS__, 'checkIfTermIsTranslated'], 10, 3);
        add_action('admin_notices', [__CLASS__, 'compositePostErrorAdminMessage']);
    }

    /**
     * @param $contenthubID
     *
     * @return null|string
     */
    public static function postIDFromContenthubID($contenthubID)
    {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM wp_postmeta WHERE meta_key=%s AND meta_value=%s",
                static::POST_META_CONTENTHUB_ID,
                $contenthubID
            )
        );
    }

    /**
     * @param $whiteAlbumID
     *
     * @return null|string
     */
    public static function postIDFromWhiteAlbumID($whiteAlbumID)
    {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM wp_postmeta WHERE meta_key=%s AND meta_value=%s",
                static::POST_META_WHITE_ALBUM_ID,
                $whiteAlbumID
            )
        );
    }

    /**
     * @param $postID
     *
     * @return null|string
     */
    public static function whiteAlbumIDFromPostID($postID)
    {
        return get_post_meta($postID, static::POST_META_WHITE_ALBUM_ID, true);
    }

    private static function registerACFFields()
    {
        TeaserFieldGroup::register();
        MetaFieldGroup::register();
        CompositeFieldGroup::register();
        GuideMetaFieldGroup::register();
        TranslationStateFieldGroup::register();
        TaxonomyFieldGroup::register(WpTaxonomy::get_custom_taxonomies());
    }

    public static function onSave($postId, \WP_Post $post)
    {
        $domain = Utils::removeApiSubdomain(LanguageProvider::getHomeUrl());
        if (
            static::postTypeMatchAndNotAutoDraft($post) &&
            !get_post_meta($postId, static::POST_META_CONTENTHUB_ID, true) &&
            $site = SiteRepository::find_by_domain(parse_url($domain, PHP_URL_HOST))
        ) {
            $contentHubId = base64_encode(sprintf('COMPOSITES-%s-%s', $site->brand->brand_code, $postId));
            update_post_meta($postId, WpComposite::POST_META_CONTENTHUB_ID, $contentHubId);
        }
    }


    /**
     * Triggers the slug change hook on post save
     *
     * @param          $postId
     * @param \WP_Post $post
     */
    public static function onSaveSlugChange($postId, \WP_Post $post)
    {
        remove_action('save_post', [__CLASS__, 'onSaveSlugChange'], 5);
        if (static::postTypeMatchAndNotAutoDraft($post)) {
            $oldLink = get_permalink();
            if ($oldLink && acf_validate_save_post()) {  // Validate acf input and get old link
                acf_save_post($postId);
                $newLink = get_permalink($postId);
                if ($newLink && $newLink !== $oldLink) { // Check if old link differ from new
                    do_action(static::SLUG_CHANGE_HOOK, $postId, $oldLink, $newLink); // Trigger the hook
                }
            }
        }
        add_action('save_post', [__CLASS__, 'onSaveSlugChange'], 5, 2);
    }

    public static function onSaveRequiredTitle($postId, \WP_Post $post)
    {
        $errors = [];
        $title = $post->post_title;
        if (!$title) {
            $errors['title'] = "The title is required";
        }
        if (!empty($errors)) {
            remove_action('save_post', [__CLASS__, 'onSaveRequiredTitle']);
            update_option('composite_errors', $errors);
            $post->post_status = 'draft';
            wp_update_post($post);
            add_action('save_post', [__CLASS__, 'onSaveRequiredTitle']);
            add_filter('redirect_post_location', [__CLASS__, 'compositeRedirectFilter'], 10, 2);
        }
    }

    public static function compositeRedirectFilter($location)
    {
        $location = remove_query_arg('message', $location);
        $location = add_query_arg('composite', 'error', $location);
        return $location;
    }

    public static function compositeErrorAdminMessage()
    {
        if (isset($_GET['composite']) && $_GET['composite'] == 'error'){
            $errors = get_option('composite_errors');
            delete_option('composite_errors');
            $display = '<div id="notice" class="error"><ul>';
            foreach ($errors as $error){
                $display .= '<li>' . $error . '</li>';
            }
            $display .= '</ul></div>';
            echo $display;
            ?>
            <script>
                jQuery(function($){
                    $("#title").css({"border": "1px solid red"})
                });
            </script>
            <?php
        }
    }

    public static function addedTermRelationship(int $postID, int $termID, string $taxonomy)
    {
        if (
            ($taxonomy === 'category' && $post = get_post($postID)) &&
            $post instanceof \WP_Post &&
            $post->post_type === self::POST_TYPE
        ) {
            // Needed by the redirect plugin, so categories updated through wp_set_post_categories() are synced to acf
            update_field('category', $termID, $post->ID);
        }
    }

    public static function mapAll($callback)
    {
        $args = [
            'post_type' => static::POST_TYPE,
            'posts_per_page' => 100,
            'paged' => 1,
            'order' => 'ASC',
            'orderby' => 'ID'
        ];

        $posts = query_posts($args);

        while ($posts) {
            collect($posts)->each(function (\WP_Post $post) use ($callback) {
                $callback($post);
            });

            $args['paged']++;
            $posts = query_posts($args);
        }
    }

    public static function checkIfTermIsTranslated($metas, $sync, $fromPostId)
    {
        if (isset($_GET['new_lang'])) {
            $fromTerms = array(get_field('category', $fromPostId));
            $fromTags = get_field('tags', $fromPostId);
            if (is_array($fromTags)) {
                foreach ($fromTags as $tag) {
                    array_push($fromTerms, $tag);
                }
            }

            $newLanguage = $_GET['new_lang'];
            foreach ($fromTerms as $term) {
                if (is_null($term) || is_null($term->term_id)) {
                    continue;
                }
                if (!LanguageProvider::isTermTranslated($term->term_id, $newLanguage)) {
                    if ($term->taxonomy === 'category') {
                        unset($metas[array_search('category', $metas)]);
                    }

                    if ($term->taxonomy === 'post_tag') {
                        unset($metas[array_search('tags', $metas)]);
                    }
                };
            }
        }

        return $metas;
    }

    private static function postTypeMatchAndNotAutoDraft($post)
    {
        return is_object($post) && $post->post_type === static::POST_TYPE && $post->post_status !== 'auto-draft';
    }

    public static function loadTeaserCharacterCounter()
    {
        wp_register_script(
            'teaser-character-counter',
            get_theme_file_uri('/assets/js/teaser-character-counter.js'),
            ['acf-input', 'acf-input-markdown-editor'],
            get_theme_file_path('/assets/js/teaser-character-counter.js')
        );
        wp_enqueue_script(
            'teaser-character-counter',
            '',
            ['acf-input', 'acf-input-markdown-editor'],
            get_theme_file_path('/assets/js/teaser-character-counter.js')
        );
    }
}
