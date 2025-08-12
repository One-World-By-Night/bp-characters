<?php

/**
 * Plugin Name: BuddyPress Characters
 * Description: Adds character post functionality to BuddyPress profiles
 * Version: 2.1.0
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

        // Core hooks
        add_action('init', [$this, 'register_post_type']);
        add_action('bp_setup_nav', [$this, 'setup_nav'], 100);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Handle edit screen
        add_action('bp_screens', [$this, 'handle_edit_screen']);

        // Activation/Deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
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

    public function register_post_type()
    {
        register_post_type('bp_character', [
            'labels' => [
                'name' => 'Characters',
                'singular_name' => 'Character'
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title', 'editor', 'author'],
            'capability_type' => 'post',
            'map_meta_cap' => true
        ]);
    }

    public function setup_nav()
    {
        if (!bp_is_user()) return;

        // Main nav
        bp_core_new_nav_item([
            'name' => 'Characters',
            'slug' => 'characters',
            'screen_function' => [$this, 'characters_screen'],
            'position' => 30,
            'default_subnav_slug' => 'list'
        ]);

        // List subnav
        bp_core_new_subnav_item([
            'name' => 'My Characters',
            'slug' => 'list',
            'parent_url' => trailingslashit(bp_displayed_user_domain() . 'characters'),
            'parent_slug' => 'characters',
            'screen_function' => [$this, 'characters_screen'],
            'position' => 10
        ]);

        // Create subnav (only for profile owner)
        if (bp_is_my_profile()) {
            bp_core_new_subnav_item([
                'name' => 'Create Character',
                'slug' => 'create',
                'parent_url' => trailingslashit(bp_displayed_user_domain() . 'characters'),
                'parent_slug' => 'characters',
                'screen_function' => [$this, 'create_screen'],
                'position' => 20
            ]);
        }
    }

    public function handle_edit_screen()
    {
        if (!bp_is_user()) return;
        if (bp_current_component() !== 'characters') return;
        if (bp_current_action() !== 'edit') return;
        if (!bp_is_my_profile()) {
            bp_core_redirect(bp_displayed_user_domain() . 'characters/');
            return;
        }

        add_action('bp_template_content', [$this, 'edit_form']);
        bp_core_load_template('members/single/plugins');
    }

    public function characters_screen()
    {
        add_action('bp_template_content', [$this, 'list_characters']);
        bp_core_load_template('members/single/plugins');
    }

    public function create_screen()
    {
        if (!bp_is_my_profile()) {
            bp_core_redirect(bp_displayed_user_domain());
            return;
        }
        add_action('bp_template_content', [$this, 'create_form']);
        bp_core_load_template('members/single/plugins');
    }

    public function list_characters()
    {
        // Handle delete
        if (bp_is_my_profile() && isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
            $character_id = intval($_GET['delete']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_character_' . $character_id)) {
                $character = get_post($character_id);
                if ($character && $character->post_author == get_current_user_id()) {
                    wp_delete_post($character_id, true);
                    bp_core_add_message('Character deleted successfully!');
                    bp_core_redirect(bp_loggedin_user_domain() . 'characters/');
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
                <div id="characters-accordion">
                    <?php while ($characters->have_posts()) : $characters->the_post();
                        $character_id = get_the_ID();
                        $name = get_post_meta($character_id, 'character_name', true) ?: get_the_title();
                        $type = get_post_meta($character_id, 'character_type', true);
                        $chronicle = get_post_meta($character_id, 'character_chronicle', true);
                        $content = get_the_content();
                    ?>
                        <h3>
                            <div class="character-header-content">
                                <div class="character-name"><?php echo esc_html($name); ?></div>
                                <div class="character-type"><?php echo esc_html($type); ?></div>
                            </div>
                        </h3>
                        <div>
                            <div class="character-field">
                                <strong>Home Chronicle:</strong> <?php echo esc_html($chronicle); ?>
                            </div>

                            <div class="character-field">
                                <strong>Description:</strong>
                                <div class="character-description">
                                    <?php echo wpautop(wp_kses_post($content)); ?>
                                </div>
                            </div>

                            <?php if (bp_is_my_profile()) : ?>
                                <div class="character-actions">
                                    <a href="<?php echo bp_loggedin_user_domain() . 'characters/edit/' . $character_id; ?>"
                                        class="action-icon edit-icon"
                                        title="Edit Character">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(bp_loggedin_user_domain() . 'characters/?delete=' . $character_id, 'delete_character_' . $character_id); ?>"
                                        class="action-icon delete-icon"
                                        title="Delete Character"
                                        onclick="return confirm('Are you sure you want to delete this character?');">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <script>
                    jQuery(document).ready(function($) {
                        $('#characters-accordion').accordion({
                            collapsible: true,
                            active: false,
                            heightStyle: 'content'
                        });
                    });
                </script>
            <?php else : ?>
                <p>No characters created yet.</p>
                <?php if (bp_is_my_profile()) : ?>
                    <p><a href="<?php echo bp_loggedin_user_domain() . 'characters/create/'; ?>" class="button">Create Your First Character</a></p>
                <?php endif; ?>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    <?php
    }

    public function create_form()
    {
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

                bp_core_add_message('Character created successfully!');
                bp_core_redirect(bp_loggedin_user_domain() . 'characters/');
                exit;
            }
        }
    ?>
        <div class="character-form-wrapper">
            <h2>Create New Character</h2>
            <form method="post" class="character-form">
                <?php wp_nonce_field('create_character'); ?>
                <?php $this->render_fields(); ?>
                <div class="form-actions">
                    <button type="submit" name="submit_character" class="button button-primary">Create Character</button>
                    <a href="<?php echo bp_loggedin_user_domain() . 'characters/'; ?>" class="button">Cancel</a>
                </div>
            </form>
        </div>
    <?php
    }

    public function edit_form()
    {
        $action_vars = bp_action_variables();
        $character_id = isset($action_vars[0]) ? intval($action_vars[0]) : 0;

        if (!$character_id) {
            echo '<p>No character ID provided.</p>';
            echo '<p><a href="' . bp_loggedin_user_domain() . 'characters/">Back to Characters</a></p>';
            return;
        }

        $character = get_post($character_id);

        if (!$character || $character->post_type !== 'bp_character' || $character->post_author != get_current_user_id()) {
            echo '<p>Character not found or access denied.</p>';
            echo '<p><a href="' . bp_loggedin_user_domain() . 'characters/">Back to Characters</a></p>';
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

            bp_core_add_message('Character updated successfully!');
            bp_core_redirect(bp_loggedin_user_domain() . 'characters/');
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
            <h2>Edit Character: <?php echo esc_html($values['character_name']); ?></h2>
            <form method="post" class="character-form">
                <?php wp_nonce_field('edit_character_' . $character_id); ?>
                <?php $this->render_fields($values); ?>
                <div class="form-actions">
                    <button type="submit" name="update_character" class="button button-primary">Update Character</button>
                    <a href="<?php echo bp_loggedin_user_domain() . 'characters/'; ?>" class="button">Cancel</a>
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
            <label for="character_name">Character Name: <span class="required">*</span></label>
            <input type="text" id="character_name" name="character_name" value="<?php echo esc_attr($values['character_name']); ?>" required>
        </div>

        <div class="form-field">
            <label for="character_type">Creature Type: <span class="required">*</span></label>
            <input type="text" id="character_type" name="character_type" value="<?php echo esc_attr($values['character_type']); ?>" required>
        </div>

        <div class="form-field">
            <label for="character_chronicle">Home Chronicle: <span class="required">*</span></label>
            <input type="text" id="character_chronicle" name="character_chronicle" value="<?php echo esc_attr($values['character_chronicle']); ?>" required>
        </div>

        <div class="form-field">
            <label for="character_description">Description:</label>
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
        if (!bp_is_user() || bp_current_component() !== 'characters') return;

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
        wp_enqueue_style('dashicons'); // Load WordPress icons

        // Add timestamp for development (remove or change to version number for production)
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : '2.0.4';
        wp_enqueue_style('bpc-styles', plugin_dir_url(__FILE__) . 'bp-characters.css', array('jquery-ui', 'dashicons'), $version);
    }
}

// Initialize plugin
BPC_Characters_Plugin::get_instance();
