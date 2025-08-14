<?php

/** File: includes/classes/class-bpc-characters-plugin.php
 * Text Domain: bp-characters
 * version 2.0.0
 * @author greghacke
 * Function: class functionality for the plugin
 */

defined('ABSPATH') || exit;

class BPC_Characters_Plugin
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Check dependencies
        add_action('plugins_loaded', [$this, 'check_dependencies']);

        // Core hooks - init early for mobile
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_bp_component'], 20);

        // Mobile fix - intercept VERY early with multiple hooks
        add_action('parse_request', [$this, 'mobile_early_intercept'], 1);
        add_action('template_redirect', [$this, 'mobile_character_fix'], 1);
        add_action('wp', [$this, 'mobile_character_fix'], 1);

        // Setup navigation
        add_action('bp_setup_nav', [$this, 'setup_nav'], 100);
        add_action('bp_setup_admin_bar', [$this, 'setup_admin_bar'], 100);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Handle screens
        add_action('bp_screens', [$this, 'handle_screens']);

        // Search integration
        add_filter('pre_get_posts', [$this, 'include_in_search']);
        add_filter('posts_search', [$this, 'search_character_meta'], 10, 2);
        add_filter('the_permalink', [$this, 'fix_search_permalink'], 10, 2);
        add_action('template_redirect', [$this, 'handle_character_search_redirect']);

        // Activation/Deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Debug mobile issues
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('init', [$this, 'debug_mobile']);
        }
    }

    // Then include all your methods here...
}
