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
if (!defined('FILE')) {
    define('FILE', __FILE__);
}
if (!defined('DIR')) {
    define('DIR', plugin_dir_path(__FILE__));
}
if (!defined('URL')) {
    define('URL', plugin_dir_url(__FILE__));
}
if (!defined('VERSION')) {
    define('VERSION', '1.0.0');
}
if (!defined('TEXTDOMAIN')) {
    define('TEXTDOMAIN', 'bp-characters');
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', constant('URL') . 'includes/assets/');
}
if (!defined('CSS_URL')) {
    define('CSS_URL', constant('ASSETS_URL') . 'css/');
}
if (!defined('JS_URL')) {
    define('JS_URL', constant('ASSETS_URL') . 'js/');
}

// Bootstrap the plugin/module
require_once constant('DIR') . 'includes/init.php';

// Initialize plugin
BPC_Characters_Plugin::get_instance();
