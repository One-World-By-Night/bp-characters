<?php

/** File: includes/core/activiation.php
 * Text Domain: bp-characters
 * version 2.0.0
 * @author greghacke
 * Function: activiation functionality for the plugin
 */

defined('ABSPATH') || exit;

function bpc_activate()
{
    $this->register_post_type();
    flush_rewrite_rules();
}

public function bpc_deactivate()
{
    flush_rewrite_rules();
}

public function check_dependencies()
{
    if (!class_exists('BuddyPress')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>BuddyPress Characters requires BuddyPress to be active.</p></div>';
        });
        return false;
    }
    return true;
}

public function register_bp_component()
{
    if (!function_exists('bp_is_active')) return;

    global $bp;

    // Register as active component
    if (!isset($bp->active_components['characters'])) {
        $bp->active_components['characters'] = 1;
    }

    // Add to pages array for mobile compatibility
    if (!isset($bp->pages->characters)) {
        $bp->pages->characters = new stdClass();
        $bp->pages->characters->id = 999999; // Fake ID
        $bp->pages->characters->slug = 'characters';
    }
}

public function register_post_type()
{
    $args = [
        'labels' => [
            'name' => 'Characters',
            'singular_name' => 'Character',
            'search_items' => 'Search Characters',
            'all_items' => 'All Characters',
            'edit_item' => 'Edit Character',
            'update_item' => 'Update Character',
            'add_new_item' => 'Add New Character',
            'new_item_name' => 'New Character Name',
            'menu_name' => 'Characters'
        ],
        'public' => false,
        'publicly_queryable' => true, // Allow query vars for search redirect
        'show_ui' => false,
        'show_in_menu' => false,
        'exclude_from_search' => false, // Include in search
        'supports' => ['title', 'editor', 'author'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'rewrite' => false,
        'query_var' => 'bp_character'
    ];

    register_post_type('bp_character', $args);
}