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

function my_admin_scripts() {
    wp_enqueue_style( 'wpdocs-datatables-bootstrap-style', get_template_directory_uri() . '/assets/css/custom.css' );
    wp_enqueue_style( 'wpdocs-datatables-bootstrap-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' );
    wp_enqueue_script( 'wpdocs-jquery-datatables-script', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array(), null, true );
    wp_enqueue_script( 'script-id', get_template_directory_uri() . '/js/script.js', array ( 'jquery' ), 1.1, true);
}
add_action( 'admin_enqueue_scripts', 'my_admin_scripts' );