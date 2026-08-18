<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('iu_acf_post_gallery_register_field')) {
    function iu_acf_post_gallery_register_field() {
        if (!class_exists('acf_field') || class_exists('IU_ACF_Post_Gallery_Field')) {
            return;
        }

        class IU_ACF_Post_Gallery_Field extends acf_field {
            public function __construct() {
                $this->name = 'iu_post_gallery';
                $this->label = __('ISTODATA Post Gallery', 'istodata-utilities');
                $this->category = 'content';
                $this->defaults = array(
                    'button_label' => __('Select Images', 'istodata-utilities'),
                    'max_items' => 0,
                    // WPML copies the field to translations by default.
                    'wpml_cf_preferences' => 1,
                );

                parent::__construct();
            }

            public function render_field_settings($field) {
                acf_render_field_setting($field, array(
                    'label' => __('Button Label', 'istodata-utilities'),
                    'type' => 'text',
                    'name' => 'button_label',
                    'default_value' => $this->defaults['button_label'],
                ));

                acf_render_field_setting($field, array(
                    'label' => __('Max Images', 'istodata-utilities'),
                    'instructions' => __('Leave empty or set to 0 for unlimited images.', 'istodata-utilities'),
                    'type' => 'number',
                    'name' => 'max_items',
                    'min' => 0,
                    'step' => 1,
                    'default_value' => $this->defaults['max_items'],
                ));
            }

            public function input_admin_enqueue_scripts() {
                wp_enqueue_media();
                wp_enqueue_script(
                    'iu-acf-post-gallery',
                    IU_PLUGIN_URL . 'assets/js/iu-acf-post-gallery.js',
                    array('jquery', 'jquery-ui-sortable', 'acf-input'),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null,
                    true
                );
                wp_enqueue_style(
                    'iu-acf-post-gallery',
                    IU_PLUGIN_URL . 'assets/css/iu-acf-post-gallery.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }

            public function render_field($field) {
                $ids = $this->normalize_ids(isset($field['value']) ? $field['value'] : array());
                $max_items = isset($field['max_items']) ? max(0, absint($field['max_items'])) : 0;
                $button_label = !empty($field['button_label']) ? $field['button_label'] : $this->defaults['button_label'];

                echo '<div class="iu-acf-post-gallery" data-max-items="' . esc_attr($max_items) . '">';
                echo '<input type="hidden" class="iu-acf-post-gallery__input" name="' . esc_attr($field['name']) . '" value="' . esc_attr(implode(',', $ids)) . '" />';
                echo '<button type="button" class="button iu-acf-post-gallery__select">' . esc_html($button_label) . '</button> ';
                echo '<button type="button" class="button-link-delete iu-acf-post-gallery__clear"' . (empty($ids) ? ' hidden' : '') . '>' . esc_html__('Clear', 'istodata-utilities') . '</button>';
                if ($max_items > 0) {
                    echo '<p class="description iu-acf-post-gallery__limit">' . esc_html(sprintf(__('Maximum: %d images.', 'istodata-utilities'), $max_items)) . '</p>';
                }
                echo '<ul class="iu-acf-post-gallery__list">';
                foreach ($ids as $id) {
                    $this->render_item($id);
                }
                echo '</ul>';
                echo '</div>';
            }

            public function update_value($value, $post_id, $field) {
                $ids = $this->normalize_ids($value);
                $max_items = isset($field['max_items']) ? max(0, absint($field['max_items'])) : 0;

                return $max_items > 0 ? array_slice($ids, 0, $max_items) : $ids;
            }

            public function format_value($value, $post_id, $field) {
                return $this->normalize_ids($value);
            }

            private function normalize_ids($value) {
                if (is_string($value)) {
                    $value = explode(',', $value);
                }
                if (!is_array($value)) {
                    return array();
                }

                return array_values(array_unique(array_filter(array_map('absint', $value))));
            }

            private function render_item($id) {
                $thumb = wp_get_attachment_image_url($id, 'thumbnail');
                if (!$thumb) {
                    return;
                }

                echo '<li class="iu-acf-post-gallery__item" data-id="' . esc_attr($id) . '">';
                echo '<img src="' . esc_url($thumb) . '" alt="" />';
                echo '<button type="button" class="iu-acf-post-gallery__remove" aria-label="' . esc_attr__('Remove image', 'istodata-utilities') . '">&times;</button>';
                echo '</li>';
            }
        }

        acf_register_field_type('IU_ACF_Post_Gallery_Field');
    }
}

add_action('acf/include_field_types', 'iu_acf_post_gallery_register_field');
