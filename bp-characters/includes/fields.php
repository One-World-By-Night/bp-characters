<?php

/** File: includes/fields.php
 * Text Domain: bp-characters
 * version 2.0.0
 * @author greghacke
 * Function: fields functionality for the plugin
 */

defined('ABSPATH') || exit;

// render fields for character creation and editing
function bpc_render_fields($values = [])
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
