<?php

/** File: includes/render/render-user.php
 * Text Domain: bp-characters
 * version 2.0.0
 * @author greghacke
 * Function: activiation functionality for the plugin
 */

defined('ABSPATH') || exit;

function bpc_mobile_early_intercept($wp)
{
    // Check if URL contains /characters
    if (strpos($_SERVER['REQUEST_URI'], '/characters') === false) {
        return;
    }

    // Detect mobile using multiple methods
    $is_mobile = false;

    // Method 1: WordPress mobile detection
    if (function_exists('wp_is_mobile') && wp_is_mobile()) {
        $is_mobile = true;
    }

    // Method 2: User agent detection
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $mobile_agents = [
            'Mobile',
            'Android',
            'iPhone',
            'iPad',
            'iPod',
            'BlackBerry',
            'Windows Phone',
            'Opera Mini',
            'IEMobile'
        ];
        foreach ($mobile_agents as $agent) {
            if (stripos($_SERVER['HTTP_USER_AGENT'], $agent) !== false) {
                $is_mobile = true;
                break;
            }
        }
    }

    // If mobile, add force parameter internally
    if ($is_mobile && !isset($_GET['force_characters'])) {
        $_GET['force_characters'] = 1;
        $_REQUEST['force_characters'] = 1;
    }
}

function bpc_debug_mobile()
{
    if (strpos($_SERVER['REQUEST_URI'], '/characters') !== false) {
        error_log('BPC Debug - URI: ' . $_SERVER['REQUEST_URI']);
        error_log('BPC Debug - Is Mobile: ' . (wp_is_mobile() ? 'Yes' : 'No'));
        error_log('BPC Debug - User Agent: ' . $_SERVER['HTTP_USER_AGENT']);
        error_log('BPC Debug - BP Component: ' . bp_current_component());
        error_log('BPC Debug - BP Action: ' . bp_current_action());
    }
}

function bpc_mobile_character_fix()
{
    // Only run if we detect /characters in URL
    if (strpos($_SERVER['REQUEST_URI'], '/characters') === false) {
        return;
    }

    // Check if it's a member characters URL
    if (!preg_match('/\/members\/([^\/]+)\/characters/', $_SERVER['REQUEST_URI'], $matches)) {
        return;
    }

    // Get the username
    $username = $matches[1];

    // Find the user
    $user = get_user_by('slug', $username);
    if (!$user) {
        $user = get_user_by('login', $username);
    }

    if (!$user) {
        return;
    }

    // Check multiple mobile detection methods
    $is_mobile = wp_is_mobile() ||
        (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone|iPad/i', $_SERVER['HTTP_USER_AGENT'])) ||
        (isset($_SERVER['HTTP_X_WAP_PROFILE'])) ||
        (isset($_SERVER['HTTP_ACCEPT']) && preg_match('/wap/i', $_SERVER['HTTP_ACCEPT']));

    // Option 1: Auto-redirect mobile to force_characters URL
    if ($is_mobile && !isset($_GET['force_characters'])) {
        $redirect_url = add_query_arg('force_characters', '1', $_SERVER['REQUEST_URI']);
        wp_redirect($redirect_url);
        exit;
    }

    // Force BuddyPress to recognize this request
    global $bp, $wp_query;

    // Set up BuddyPress globals
    $bp->current_component = 'characters';
    $bp->current_action = bp_action_variable(0) ?: 'list';
    $bp->displayed_user = new stdClass();
    $bp->displayed_user->id = $user->ID;
    $bp->displayed_user->userdata = $user;
    $bp->displayed_user->domain = bp_core_get_user_domain($user->ID);

    // Prevent WordPress from thinking this is a 404
    $wp_query->is_404 = false;
    status_header(200);

    // ALWAYS fire BP screens on mobile or with force parameter
    if ($is_mobile || isset($_GET['force_characters'])) {
        // Remove any potential redirect actions
        remove_all_actions('template_redirect', 10);
        remove_all_actions('template_redirect', 99);

        // Fire BP screens
        do_action('bp_screens');

        // If we're still here, force the template
        if (!did_action('bp_screens')) {
            bpc_characters_screen();
        }
    }
}

/**
 * Handle search result redirects for characters
 */
function bpc_handle_character_search_redirect()
{
    // Check if we're on a character query var URL
    if (get_query_var('bp_character')) {
        $character_slug = get_query_var('bp_character');

        // Try to find the character post
        $args = [
            'name' => $character_slug,
            'post_type' => 'bp_character',
            'posts_per_page' => 1
        ];

        $character = get_posts($args);

        if (!empty($character)) {
            $author_id = $character[0]->post_author;
            $author_domain = bp_core_get_user_domain($author_id);

            if ($author_domain) {
                // Redirect to the author's characters page
                wp_redirect($author_domain . 'characters/');
                exit;
            }
        }
    }
}

/**
 * Fix character permalinks in search results
 */
function bpc_fix_search_permalink($permalink, $post)
{
    if ($post && get_post_type($post) === 'bp_character') {
        $author_id = $post->post_author;
        $author_domain = bp_core_get_user_domain($author_id);

        if ($author_domain) {
            return $author_domain . 'characters/';
        }
    }

    return $permalink;
}

/**
 * Include characters in WordPress search
 */
function bpc_include_in_search($query)
{
    if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        $post_types = $query->get('post_type');

        if (empty($post_types)) {
            $post_types = ['post', 'page'];
        } elseif (is_string($post_types)) {
            $post_types = [$post_types];
        }

        if (!in_array('bp_character', $post_types)) {
            $post_types[] = 'bp_character';
            $query->set('post_type', $post_types);
        }
    }
    return $query;
}

/**
 * Make character meta fields searchable
 */
function bpc_search_character_meta($search, $wp_query)
{
    if (!is_admin() && $wp_query->is_main_query() && $wp_query->is_search()) {
        global $wpdb;

        $search_term = $wp_query->get('s');
        if (empty($search_term)) return $search;

        // Add meta search for character fields
        $meta_search = $wpdb->prepare(
            " OR (
            {$wpdb->posts}.post_type = 'bp_character' AND (
                EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} 
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                    AND {$wpdb->postmeta}.meta_key IN ('character_type', 'character_chronicle')
                    AND {$wpdb->postmeta}.meta_value LIKE %s
                )
            )
        )",
            '%' . $wpdb->esc_like($search_term) . '%'
        );

        // Insert before the closing parenthesis
        $search = str_replace(')))', ')) ' . $meta_search . ')', $search);
    }

    return $search;
}

/** 
 * Setup BuddyPress navigation for characters
 */
function bpc_setup_nav()
{
    global $bp;

    // Skip if no BuddyPress
    if (!$bp) return;

    // Check if mobile and add force parameter to URLs
    $force_param = '';
    if (wp_is_mobile() || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']))) {
        $force_param = '?force_characters=1';
    }

    // Main nav item - always add it
    $nav_args = [
        'name' => __('Characters', 'bp-characters'),
        'slug' => 'characters',
        'position' => 30,
        'screen_function' => 'bpc_characters_screen',
        'default_subnav_slug' => 'list',
        'show_for_displayed_user' => true,
        'item_css_id' => 'characters'
    ];

    // Add force parameter for mobile
    if ($force_param && isset($bp->displayed_user->domain)) {
        $nav_args['link'] = bp_displayed_user_domain() . 'characters/' . $force_param;
    }

    bp_core_new_nav_item($nav_args);

    // Only add subnav if we have a displayed user
    if (isset($bp->displayed_user->id)) {
        $user_domain = bp_displayed_user_domain();

        // List subnav
        bp_core_new_subnav_item([
            'name' => __('My Characters', 'bp-characters'),
            'slug' => 'list',
            'parent_url' => trailingslashit($user_domain . 'characters'),
            'parent_slug' => 'characters',
            'screen_function' => 'bpc_characters_screen',
            'position' => 10,
            'user_has_access' => true,
            'link' => $user_domain . 'characters/' . $force_param
        ]);

        // Create subnav (only for profile owner)
        if (bp_is_my_profile()) {
            bp_core_new_subnav_item([
                'name' => __('Create Character', 'bp-characters'),
                'slug' => 'create',
                'parent_url' => trailingslashit($user_domain . 'characters'),
                'parent_slug' => 'characters',
                'screen_function' => 'bpc_create_screen',
                'position' => 20,
                'user_has_access' => bp_is_my_profile(),
                'link' => $user_domain . 'characters/create/' . $force_param
            ]);
        }
    }
}

/**
 * Handle all character screens
 */

function bpc_handle_screens()
{
    if (bp_current_component() !== 'characters') return;

    $action = bp_current_action();

    switch ($action) {
        case 'edit':
            if (bp_is_my_profile()) {
                bpc_edit_screen();
            } else {
                bp_core_redirect(bp_displayed_user_domain() . 'characters/');
            }
            break;

        case 'create':
            if (bp_is_my_profile()) {
                bpc_create_screen();
            } else {
                bp_core_redirect(bp_displayed_user_domain() . 'characters/');
            }
            break;

        default:
            bpc_characters_screen();
            break;
    }
}

function bpc_characters_screen()
{
    add_action('bp_template_title', 'bpc_characters_title');
    add_action('bp_template_content', 'bpc_list_characters');

    // Try multiple template paths for better compatibility
    $templates = apply_filters('bpc_template_hierarchy', [
        'members/single/plugins.php',
        'members/single/plugin.php',
        'members/single/home.php',
        'buddypress/members/single/plugins.php',
        'buddypress/members/single/home.php',
        'community/members/single/plugins.php',
        'community/members/single/home.php'
    ]);

    bp_core_load_template($templates);
}

function bpc_create_screen()
{
    if (!bp_is_my_profile()) {
        bp_core_redirect(bp_displayed_user_domain());
        return;
    }

    add_action('bp_template_title', 'bpc_characters_title');
    add_action('bp_template_content', 'bpc_create_form');

    $templates = apply_filters('bpc_template_hierarchy', [
        'members/single/plugins.php',
        'members/single/plugin.php',
        'members/single/home.php',
        'buddypress/members/single/plugins.php',
        'buddypress/members/single/home.php',
        'community/members/single/plugins.php',
        'community/members/single/home.php'
    ]);

    bp_core_load_template($templates);
}

function bpc_edit_screen()
{
    if (!bp_is_my_profile()) {
        bp_core_redirect(bp_displayed_user_domain() . 'characters/');
        return;
    }

    add_action('bp_template_title', 'bpc_characters_title');
    add_action('bp_template_content', 'bpc_edit_form');

    $templates = apply_filters('bpc_template_hierarchy', [
        'members/single/plugins.php',
        'members/single/plugin.php',
        'members/single/home.php',
        'buddypress/members/single/plugins.php',
        'buddypress/members/single/home.php',
        'community/members/single/plugins.php',
        'community/members/single/home.php'
    ]);

    bp_core_load_template($templates);
}

function bpc_characters_title()
{
    echo __('Characters', 'bp-characters');
}

function bpc_create_title()
{
    echo __('Create New Character', 'bp-characters');
}

function bpc_edit_title()
{
    echo __('Edit Character', 'bp-characters');
}

function bpc_list_characters()
{
    // Check if mobile and add force parameter
    $force_param = '';
    if (wp_is_mobile() || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']))) {
        $force_param = '?force_characters=1';
    }

    // Handle delete
    if (bp_is_my_profile() && isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        $character_id = intval($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_character_' . $character_id)) {
            $character = get_post($character_id);
            if ($character && $character->post_author == get_current_user_id()) {
                wp_delete_post($character_id, true);
                bp_core_add_message(__('Character deleted successfully!', 'bp-characters'));
                bp_core_redirect(bp_loggedin_user_domain() . 'characters/' . $force_param);
            }
        }
    }

    $user_id = bp_displayed_user_id();
    $args = [
        'post_type' => 'bp_character',
        'author' => $user_id,
        'posts_per_page' => 20,
        'orderby' => 'date',
        'order' => 'DESC'
    ];

    $characters = new WP_Query($args);
?>
    <div class="characters-wrapper">
        <?php if ($characters->have_posts()) : ?>
            <div id="characters-accordion" class="bpc-accordion">
                <?php while ($characters->have_posts()) : $characters->the_post();
                    $character_id = get_the_ID();
                    $name = get_post_meta($character_id, 'character_name', true) ?: get_the_title();
                    $type = get_post_meta($character_id, 'character_type', true);
                    $chronicle = get_post_meta($character_id, 'character_chronicle', true);
                    $content = get_the_content();
                ?>
                    <h3 class="character-accordion-header">
                        <div class="character-header-content">
                            <div class="character-name"><?php echo esc_html($name); ?></div>
                            <div class="character-type"><?php echo esc_html($type); ?></div>
                        </div>
                    </h3>
                    <div class="character-accordion-content">
                        <div class="character-field">
                            <strong><?php _e('Home Chronicle:', 'bp-characters'); ?></strong> <?php echo esc_html($chronicle); ?>
                        </div>

                        <div class="character-field">
                            <strong><?php _e('Description:', 'bp-characters'); ?></strong>
                            <div class="character-description">
                                <?php echo wpautop(wp_kses_post($content)); ?>
                            </div>
                        </div>

                        <?php if (bp_is_my_profile()) :
                            $delete_url = bp_loggedin_user_domain() . 'characters/';
                            if ($force_param) {
                                $delete_url .= '?force_characters=1&delete=' . $character_id;
                            } else {
                                $delete_url .= '?delete=' . $character_id;
                            }
                        ?>
                            <div class="character-actions">
                                <a href="<?php echo bp_loggedin_user_domain() . 'characters/edit/' . $character_id . $force_param; ?>"
                                    class="action-icon edit-icon"
                                    title="<?php esc_attr_e('Edit Character', 'bp-characters'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="<?php echo wp_nonce_url($delete_url, 'delete_character_' . $character_id); ?>"
                                    class="action-icon delete-icon"
                                    title="<?php esc_attr_e('Delete Character', 'bp-characters'); ?>"
                                    onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this character?', 'bp-characters'); ?>');">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    // Mobile-friendly accordion
                    var isMobile = window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

                    if (isMobile) {
                        // Simple toggle for mobile
                        $('.character-accordion-header').on('click', function(e) {
                            e.preventDefault();
                            $(this).toggleClass('active');
                            $(this).next('.character-accordion-content').slideToggle();
                        });
                    } else if ($.fn.accordion) {
                        // jQuery UI for desktop
                        $('#characters-accordion').accordion({
                            collapsible: true,
                            active: false,
                            heightStyle: 'content',
                            header: '> h3'
                        });
                    }
                });
            </script>
        <?php else : ?>
            <p><?php _e('No characters created yet.', 'bp-characters'); ?></p>
            <?php if (bp_is_my_profile()) : ?>
                <p><a href="<?php echo bp_loggedin_user_domain() . 'characters/create/' . $force_param; ?>" class="button"><?php _e('Create Your First Character', 'bp-characters'); ?></a></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
<?php
}

function bpc_create_form()
{
    // Check if mobile and add force parameter
    $force_param = '';
    if (wp_is_mobile() || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']))) {
        $force_param = '?force_characters=1';
    }

    if (isset($_POST['submit_character']) && wp_verify_nonce($_POST['_wpnonce'], 'create_character')) {
        $character_id = wp_insert_post([
            'post_title' => sanitize_text_field($_POST['character_name']),
            'post_content' => wp_kses_post($_POST['character_description']),
            'post_type' => 'bp_character',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ]);

        if ($character_id && !is_wp_error($character_id)) {
            update_post_meta($character_id, 'character_name', sanitize_text_field($_POST['character_name']));
            update_post_meta($character_id, 'character_type', sanitize_text_field($_POST['character_type']));
            update_post_meta($character_id, 'character_chronicle', sanitize_text_field($_POST['character_chronicle']));

            bp_core_add_message(__('Character created successfully!', 'bp-characters'));
            bp_core_redirect(bp_loggedin_user_domain() . 'characters/' . $force_param);
            exit;
        }
    }
?>
    <div class="character-form-wrapper">
        <form method="post" class="character-form">
            <?php wp_nonce_field('create_character'); ?>
            <?php if ($force_param): ?>
                <input type="hidden" name="force_characters" value="1">
            <?php endif; ?>
            <?php bpc_render_fields(); ?>
            <div class="form-actions">
                <button type="submit" name="submit_character" class="button button-primary"><?php _e('Create Character', 'bp-characters'); ?></button>
                <a href="<?php echo bp_loggedin_user_domain() . 'characters/' . $force_param; ?>" class="button"><?php _e('Cancel', 'bp-characters'); ?></a>
            </div>
        </form>
    </div>
<?php
}

function bpc_edit_form()
{
    // Check if mobile and add force parameter
    $force_param = '';
    if (wp_is_mobile() || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']))) {
        $force_param = '?force_characters=1';
    }

    $action_vars = bp_action_variables();
    $character_id = isset($action_vars[0]) ? intval($action_vars[0]) : 0;

    if (!$character_id) {
        echo '<p>' . __('No character ID provided.', 'bp-characters') . '</p>';
        echo '<p><a href="' . bp_loggedin_user_domain() . 'characters/' . $force_param . '">' . __('Back to Characters', 'bp-characters') . '</a></p>';
        return;
    }

    $character = get_post($character_id);

    if (!$character || $character->post_type !== 'bp_character' || $character->post_author != get_current_user_id()) {
        echo '<p>' . __('Character not found or access denied.', 'bp-characters') . '</p>';
        echo '<p><a href="' . bp_loggedin_user_domain() . 'characters/' . $force_param . '">' . __('Back to Characters', 'bp-characters') . '</a></p>';
        return;
    }

    if (isset($_POST['update_character']) && wp_verify_nonce($_POST['_wpnonce'], 'edit_character_' . $character_id)) {
        wp_update_post([
            'ID' => $character_id,
            'post_title' => sanitize_text_field($_POST['character_name']),
            'post_content' => wp_kses_post($_POST['character_description'])
        ]);

        update_post_meta($character_id, 'character_name', sanitize_text_field($_POST['character_name']));
        update_post_meta($character_id, 'character_type', sanitize_text_field($_POST['character_type']));
        update_post_meta($character_id, 'character_chronicle', sanitize_text_field($_POST['character_chronicle']));

        bp_core_add_message(__('Character updated successfully!', 'bp-characters'));
        bp_core_redirect(bp_loggedin_user_domain() . 'characters/' . $force_param);
        exit;
    }

    $values = [
        'character_name' => get_post_meta($character_id, 'character_name', true) ?: $character->post_title,
        'character_type' => get_post_meta($character_id, 'character_type', true),
        'character_chronicle' => get_post_meta($character_id, 'character_chronicle', true),
        'character_description' => $character->post_content
    ];
?>
    <div class="character-form-wrapper">
        <form method="post" class="character-form">
            <?php wp_nonce_field('edit_character_' . $character_id); ?>
            <?php if ($force_param): ?>
                <input type="hidden" name="force_characters" value="1">
            <?php endif; ?>
            <?php bpc_render_fields($values); ?>
            <div class="form-actions">
                <button type="submit" name="update_character" class="button button-primary"><?php _e('Update Character', 'bp-characters'); ?></button>
                <a href="<?php echo bp_loggedin_user_domain() . 'characters/' . $force_param; ?>" class="button"><?php _e('Cancel', 'bp-characters'); ?></a>
            </div>
        </form>
    </div>
<?php
}

function bpc_enqueue_assets()
{
    if (!function_exists('bp_is_user') || !bp_is_user() || bp_current_component() !== 'characters') return;

    wp_enqueue_script('jquery');

    // Only load jQuery UI accordion on desktop
    if (!wp_is_mobile()) {
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
    }

    wp_enqueue_style('dashicons');

    $version = defined('WP_DEBUG') && WP_DEBUG ? time() : '2.5.1';
    wp_enqueue_style('bpc-styles', BPC_URL . 'includes/assets/css/bp-characters.css', array('dashicons'), $version);
}
