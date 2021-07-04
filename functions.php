<?php

use Bonnier\Willow\Base\Bootstrap;

add_action('after_setup_theme', [Bootstrap::class, 'setup']);

add_action('init', function () {
    new Bootstrap();
});
add_action('admin_menu', [Bootstrap::class, 'loadAdminMenu']);

add_filter('register_post_type_args', [Bootstrap::class, 'registerPageRestController'], 10, 2);

remove_action('template_redirect', 'redirect_canonical');

// Redirect all requests to index.php so the Vue app is loaded and 404s aren't thrown
add_action('init', function () {
    add_rewrite_rule('^/(.+)/?', 'index.php', 'top');
});

function get_queried_object_json()
{
    if ($object = get_queried_object()) {
        $object->wp_type = strtolower(class_basename($object));
        $object->template = get_page_template_slug($object->ID);
        return json_encode($object);
    }
    return json_encode(null);
}

function ddHtml(...$args)
{
    header('Content-Type: text/html');
    foreach ($args as $x) {
        var_dump($x);
    }

    die(1);
}

function try_redirect_url() {
    require($_SERVER['DOCUMENT_ROOT'].'/wp/wp-load.php');
    global $wpdb;
    $from = $_SERVER['REQUEST_URI'];
    $get_to = $wpdb->get_results("SELECT `to`, `code` FROM `wp_bonnier_redirects` WHERE `from` = '". $from ."'");
    if(!empty($get_to[0])){
      header($get_to[0]->to,TRUE,$get_to[0]->code);
    }
}
add_action( 'admin_enqueue_scripts', 'try_redirect_url' );