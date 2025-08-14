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

// Define path-related constants if not already defined
if (!defined($prefix . 'FILE')) {
    define($prefix . 'FILE', __FILE__);
}
if (!defined($prefix . 'DIR')) {
    define($prefix . 'DIR', plugin_dir_path(__FILE__));
}
if (!defined($prefix . 'URL')) {
    define($prefix . 'URL', plugin_dir_url(__FILE__));
}
if (!defined($prefix . 'VERSION')) {
    define($prefix . 'VERSION', '1.0.0');
}
if (!defined($prefix . 'TEXTDOMAIN')) {
    define($prefix . 'TEXTDOMAIN', 'bp-characters');
}
if (!defined($prefix . 'ASSETS_URL')) {
    define($prefix . 'ASSETS_URL', constant($prefix . 'URL') . 'includes/assets/');
}
if (!defined($prefix . 'CSS_URL')) {
    define($prefix . 'CSS_URL', constant($prefix . 'ASSETS_URL') . 'css/');
}
if (!defined($prefix . 'JS_URL')) {
    define($prefix . 'JS_URL', constant($prefix . 'ASSETS_URL') . 'js/');
}

// Bootstrap the plugin/module
require_once constant($prefix . 'DIR') . 'includes/init.php';

// Initialize plugin
BPC_Characters_Plugin::get_instance();
