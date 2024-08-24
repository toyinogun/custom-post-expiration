<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove all expiration dates
$args = array(
    'post_type' => ['post', 'page'],
    'meta_query' => array(
        array(
            'key' => '_cpen_expiration_datetime',
            'compare' => 'EXISTS',
        )
    ),
    'posts_per_page' => -1
);
$posts = get_posts($args);

foreach ($posts as $post) {
    delete_post_meta($post->ID, '_cpen_expiration_datetime');
}

// Remove scheduled event
wp_clear_scheduled_hook('cpen_daily_expiration_check');

// Remove plugin options
delete_option('cpen_notification_email');