<?php
/**
 * Plugin Name: Custom Post Expiration
 * Plugin URI: http://example.com/custom-post-expiration
 * Description: Set expiration dates for posts and pages with email notifications.
 * Version: 1.0.0
 * Author: Toyin Ogunseinde
 * Author URI: http://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: custom-post-expiration
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'CUSTOM_POST_EXPIRATION_VERSION', '1.0.0' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-custom-post-expiration.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_custom_post_expiration() {
    $plugin = new Custom_Post_Expiration();
    $plugin->run();
}
run_custom_post_expiration();

// Activation hook
register_activation_hook(__FILE__, 'activate_custom_post_expiration');

function activate_custom_post_expiration() {
    if (!wp_next_scheduled('cpen_daily_expiration_check')) {
        wp_schedule_event(time(), 'daily', 'cpen_daily_expiration_check');
    }
    if (!wp_next_scheduled('cpen_expiration_check')) {
        wp_schedule_event(time(), 'hourly', 'cpen_expiration_check');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'deactivate_custom_post_expiration');

function deactivate_custom_post_expiration() {
    wp_clear_scheduled_hook('cpen_daily_expiration_check');
    wp_clear_scheduled_hook('cpen_expiration_check');
}