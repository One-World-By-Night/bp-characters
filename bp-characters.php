<?php

/**
 * Plugin Name: BuddyPress Characters
 * Description: Adds character post functionality to BuddyPress profiles
 * Version: 2.5.1
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

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

    public function activate()
    {
        $this->register_post_type();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Very early mobile intercept
     */
    public function mobile_early_intercept($wp)
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
                'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 
                'BlackBerry', 'Windows Phone', 'Opera Mini', 'IEMobile'
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
    
    /**
     * Debug mobile issues
     */
    public function debug_mobile()
    {
        if (strpos($_SERVER['REQUEST_URI'], '/characters') !== false) {
            error_log('BPC Debug - URI: ' . $_SERVER['REQUEST_URI']);
            error_log('BPC Debug - Is Mobile: ' . (wp_is_mobile() ? 'Yes' : 'No'));
            error_log('BPC Debug - User Agent: ' . $_SERVER['HTTP_USER_AGENT']);
            error_log('BPC Debug - BP Component: ' . bp_current_component());
            error_log('BPC Debug - BP Action: ' . bp_current_action());
        }
    }

    /**
     * Mobile character page fix
     */
    public function mobile_character_fix()
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
                $this->characters_screen();
            }
        }
    }

    /**
     * Handle search result redirects for characters
     */
    public function handle_character_search_redirect()
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
    public function fix_search_permalink($permalink, $post)
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

    /**
     * Register component with BuddyPress
     */
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

    /**
     * Include characters in WordPress search
     */
    public function include_in_search($query)
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

    public function setup_nav()
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
            'screen_function' => [$this, 'characters_screen'],
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
                'screen_function' => [$this, 'characters_screen'],
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
                    'screen_function' => [$this, 'create_screen'],
                    'position' => 20,
                    'user_has_access' => bp_is_my_profile(),
                    'link' => $user_domain . 'characters/create/' . $force_param
                ]);
            }
        }
    }

    /**
     * Setup admin bar menu
     */
    public function setup_admin_bar($wp_admin_nav = [])
    {
        if (!is_user_logged_in()) return;
        
        global $wp_admin_bar;
        
        $user_domain = bp_loggedin_user_domain();
        
        // Add main characters menu
        $wp_admin_bar->add_menu([
            'parent' => 'my-account-buddypress',
            'id' => 'my-account-characters',
            'title' => __('Characters', 'bp-characters'),
            'href' => trailingslashit($user_domain . 'characters')
        ]);
        
        // Add submenu items
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

    /**
     * Handle all character screens
     */
    public function handle_screens()
    {
        if (bp_current_component() !== 'characters') return;
        
        $action = bp_current_action();
        
        switch ($action) {
            case 'edit':
                if (bp_is_my_profile()) {
                    $this->edit_screen();
                } else {
                    bp_core_redirect(bp_displayed_user_domain() . 'characters/');
                }
                break;
                
            case 'create':
                if (bp_is_my_profile()) {
                    $this->create_screen();
                } else {
                    bp_core_redirect(bp_displayed_user_domain() . 'characters/');
                }
                break;
                
            default:
                $this->characters_screen();
                break;
        }
    }

    public function characters_screen()
    {
        add_action('bp_template_title', [$this, 'characters_title']);
        add_action('bp_template_content', [$this, 'list_characters']);
        
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

    public function create_screen()
    {
        if (!bp_is_my_profile()) {
            bp_core_redirect(bp_displayed_user_domain());
            return;
        }
        
        add_action('bp_template_title', [$this, 'create_title']);
        add_action('bp_template_content', [$this, 'create_form']);
        
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

    public function edit_screen()
    {
        if (!bp_is_my_profile()) {
            bp_core_redirect(bp_displayed_user_domain() . 'characters/');
            return;
        }
        
        add_action('bp_template_title', [$this, 'edit_title']);
        add_action('bp_template_content', [$this, 'edit_form']);
        
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

    public function characters_title()
    {
        echo __('Characters', 'bp-characters');
    }

    public function create_title()
    {
        echo __('Create New Character', 'bp-characters');
    }

    public function edit_title()
    {
        echo __('Edit Character', 'bp-characters');
    }

    public function list_characters()
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

    public function create_form()
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
                <?php $this->render_fields(); ?>
                <div class="form-actions">
                    <button type="submit" name="submit_character" class="button button-primary"><?php _e('Create Character', 'bp-characters'); ?></button>
                    <a href="<?php echo bp_loggedin_user_domain() . 'characters/' . $force_param; ?>" class="button"><?php _e('Cancel', 'bp-characters'); ?></a>
                </div>
            </form>
        </div>
    <?php
    }

    public function edit_form()
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
                <?php $this->render_fields($values); ?>
                <div class="form-actions">
                    <button type="submit" name="update_character" class="button button-primary"><?php _e('Update Character', 'bp-characters'); ?></button>
                    <a href="<?php echo bp_loggedin_user_domain() . 'characters/' . $force_param; ?>" class="button"><?php _e('Cancel', 'bp-characters'); ?></a>
                </div>
            </form>
        </div>
    <?php
    }

    private function render_fields($values = [])
    {
        $defaults = [
            'character_name' => '',
            'character_type' => '',
            'character_chronicle' => '',
            'character_description' => ''
        ];
        $values = wp_parse_args($values, $defaults);
    ?>
        <div class="form-field">
            <label for="character_name"><?php _e('Character Name:', 'bp-characters'); ?> <span class="required">*</span></label>
            <input type="text" id="character_name" name="character_name" value="<?php echo esc_attr($values['character_name']); ?>" required>
        </div>

        <div class="form-field">
            <label for="character_type"><?php _e('Creature Type:', 'bp-characters'); ?> <span class="required">*</span></label>
            <input type="text" id="character_type" name="character_type" value="<?php echo esc_attr($values['character_type']); ?>" required>
        </div>

        <div class="form-field">
            <label for="character_chronicle"><?php _e('Home Chronicle:', 'bp-characters'); ?> <span class="required">*</span></label>
            <input type="text" id="character_chronicle" name="character_chronicle" value="<?php echo esc_attr($values['character_chronicle']); ?>" required>
        </div>

        <div class="form-field">
            <label for="character_description"><?php _e('Description:', 'bp-characters'); ?></label>
            <?php
            wp_editor($values['character_description'], 'character_description', [
                'textarea_name' => 'character_description',
                'textarea_rows' => 10,
                'media_buttons' => true,
                'teeny' => false,
                'quicktags' => true
            ]);
            ?>
        </div>
<?php
    }

    public function enqueue_assets()
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
        wp_enqueue_style('bpc-styles', plugin_dir_url(__FILE__) . 'bp-characters.css', array('dashicons'), $version);
    }
}

// Initialize plugin
BPC_Characters_Plugin::get_instance();