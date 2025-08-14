<?php

/**
 * Plugin Name: BuddyPress Characters
 * Text Domain: bp-characters
 * Plugin URI: https://www.github.com/One-World-By-Night/bp-characters
 * Description: A BuddyPress plugin to display characters for One World by Night.
 * Version: 2.0.0
 * Author: greghacke
 * Contributors: list, of, contributors, separated, by, commas
 * Author URI: https://www.github.com/One-World-By-Night
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /includes/languages
 * GitHub Branch: main
 */

defined('ABSPATH') || exit;

// Define constants
define('BPC_FILE', __FILE__);
define('BPC_DIR', plugin_dir_path(__FILE__));
define('BPC_URL', plugin_dir_url(__FILE__));
define('BPC_VERSION', '2.0.0');

// Load all includes
require_once BPC_DIR . 'includes/init.php';

// Hook everything up directly
add_action('plugins_loaded', 'bpc_check_dependencies');
add_action('init', 'bpc_register_post_type');
add_action('init', 'bpc_register_bp_component', 20);
add_action('parse_request', 'bpc_mobile_early_intercept', 1);
add_action('template_redirect', 'bpc_mobile_character_fix', 1);
add_action('wp', 'bpc_mobile_character_fix', 1);
add_action('bp_setup_nav', 'bpc_setup_nav', 100);
add_action('bp_setup_admin_bar', 'bpc_setup_admin_bar', 100);
add_action('wp_enqueue_scripts', 'bpc_enqueue_assets');
add_action('bp_screens', 'bpc_handle_screens');
add_filter('pre_get_posts', 'bpc_include_in_search');
add_filter('posts_search', 'bpc_search_character_meta', 10, 2);
add_filter('the_permalink', 'bpc_fix_search_permalink', 10, 2);
add_action('template_redirect', 'bpc_handle_character_search_redirect');

// Activation/Deactivation
register_activation_hook(__FILE__, 'bpc_activate');
register_deactivation_hook(__FILE__, 'bpc_deactivate');

// Debug
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', 'bpc_debug_mobile');
}
