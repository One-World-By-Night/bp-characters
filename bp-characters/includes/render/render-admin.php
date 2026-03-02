<?php
defined('ABSPATH') || exit;

function bpc_setup_admin_bar($wp_admin_nav = [])
{
    if (!is_user_logged_in()) return;

    global $wp_admin_bar;

    $user_domain = bp_loggedin_user_domain();

    $wp_admin_bar->add_menu([
        'parent' => 'my-account-buddypress',
        'id' => 'my-account-characters',
        'title' => __('Characters', 'bp-characters'),
        'href' => trailingslashit($user_domain . 'characters')
    ]);

    $wp_admin_bar->add_menu([
        'parent' => 'my-account-characters',
        'id' => 'my-account-characters-list',
        'title' => __('My Characters', 'bp-characters'),
        'href' => trailingslashit($user_domain . 'characters')
    ]);

    $wp_admin_bar->add_menu([
        'parent' => 'my-account-characters',
        'id' => 'my-account-characters-create',
        'title' => __('Create Character', 'bp-characters'),
        'href' => trailingslashit($user_domain . 'characters/create')
    ]);
}
