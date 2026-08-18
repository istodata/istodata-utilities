<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('iu_acf_simple_repeater_register_field')) {
    function iu_acf_simple_repeater_register_field() {
        if (!class_exists('acf_field') || class_exists('IU_ACF_Simple_Repeater_Field')) {
            return;
        }

        class IU_ACF_Simple_Repeater_Field extends acf_field {
            public function __construct() {
                $this->name = 'iu_simple_repeater';
                $this->label = __('ISTODATA Simple Repeater', 'istodata-utilities');
                $this->category = 'content';
                $this->defaults = array(
                    'enabled_fields' => array('title', 'text', 'image', 'link'),
                    'button_label' => __('Add Item', 'istodata-utilities'),
                    'title_label' => __('Title', 'istodata-utilities'),
                    'text_label' => __('Description', 'istodata-utilities'),
                    'image_label' => __('Image', 'istodata-utilities'),
                    'link_label' => __('Link', 'istodata-utilities'),
                    'max_items' => 0,
                );

                parent::__construct();
            }

            public function render_field_settings($field) {
                acf_render_field_setting($field, array(
                    'label' => __('Sub Fields', 'istodata-utilities'),
                    'instructions' => __('Choose which controls appear for each item.', 'istodata-utilities'),
                    'type' => 'checkbox',
                    'name' => 'enabled_fields',
                    'layout' => 'vertical',
                    'choices' => array(
                        'title' => __('Title', 'istodata-utilities'),
                        'text' => __('Description', 'istodata-utilities'),
                        'image' => __('Image', 'istodata-utilities'),
                        'link' => __('Link', 'istodata-utilities'),
                    ),
                    'default_value' => $this->defaults['enabled_fields'],
                ));

                acf_render_field_setting($field, array(
                    'label' => __('Button Label', 'istodata-utilities'),
                    'type' => 'text',
                    'name' => 'button_label',
                    'default_value' => $this->defaults['button_label'],
                ));

                acf_render_field_setting($field, array(
                    'label' => __('Max Items', 'istodata-utilities'),
                    'instructions' => __('Leave empty or set to 0 for unlimited items.', 'istodata-utilities'),
                    'type' => 'number',
                    'name' => 'max_items',
                    'min' => 0,
                    'step' => 1,
                    'default_value' => $this->defaults['max_items'],
                ));

                acf_render_field_setting($field, array(
                    'label' => __('Title Label', 'istodata-utilities'),
                    'type' => 'text',
                    'name' => 'title_label',
                    'default_value' => $this->defaults['title_label'],
                ));

                acf_render_field_setting($field, array(
                    'label' => __('Description Label', 'istodata-utilities'),
                    'type' => 'text',
                    'name' => 'text_label',
                    'default_value' => $this->defaults['text_label'],
                ));

                acf_render_field_setting($field, array(
                    'label' => __('Image Label', 'istodata-utilities'),
                    'type' => 'text',
                    'name' => 'image_label',
                    'default_value' => $this->defaults['image_label'],
                ));

                acf_render_field_setting($field, array(
                    'label' => __('Link Label', 'istodata-utilities'),
                    'type' => 'text',
                    'name' => 'link_label',
                    'default_value' => $this->defaults['link_label'],
                ));
            }

            public function input_admin_enqueue_scripts() {
                wp_enqueue_media();
                wp_enqueue_script(
                    'iu-acf-simple-repeater',
                    IU_PLUGIN_URL . 'assets/js/iu-acf-simple-repeater.js',
                    array('jquery', 'jquery-ui-sortable', 'acf-input'),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null,
                    true
                );
                wp_enqueue_style(
                    'iu-acf-simple-repeater',
                    IU_PLUGIN_URL . 'assets/css/iu-acf-simple-repeater.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }

            public function render_field($field) {
                $enabled = $this->get_enabled_fields($field);
                $value = $this->normalize_value(isset($field['value']) ? $field['value'] : array(), $enabled);
                $labels = $this->get_field_labels($field);
                $max_items = $this->get_max_items($field);
                $button_label = !empty($field['button_label']) ? $field['button_label'] : $this->defaults['button_label'];

                echo '<div class="iu-acf-simple-repeater" data-name="' . esc_attr($field['name']) . '" data-enabled="' . esc_attr(wp_json_encode($enabled)) . '" data-max-items="' . esc_attr($max_items) . '">';
                echo '<div class="iu-acf-simple-repeater__rows">';
                echo '<input type="hidden" name="' . esc_attr($field['name']) . '[__empty]" value="" />';

                foreach ($value as $index => $row) {
                    $this->render_row($field['name'], $index, $row, $enabled, $labels);
                }

                echo '</div>';
                echo '<button type="button" class="button iu-acf-simple-repeater__add">' . esc_html($button_label) . '</button>';
                if ($max_items > 0) {
                    echo '<p class="description iu-acf-simple-repeater__limit" data-limit-text="' . esc_attr(sprintf(__('Μέγιστο όριο: %d στοιχεία.', 'istodata-utilities'), $max_items)) . '">' . esc_html(sprintf(__('Μέγιστο όριο: %d στοιχεία.', 'istodata-utilities'), $max_items)) . '</p>';
                }
                echo '<script type="text/html" class="iu-acf-simple-repeater__template">';
                $this->render_row($field['name'], '__i__', array(), $enabled, $labels);
                echo '</script>';
                echo '</div>';
            }

            public function update_value($value, $post_id, $field) {
                $enabled = $this->get_enabled_fields($field);
                $value = $this->normalize_value($value, $enabled);
                $max_items = $this->get_max_items($field);
                if ($max_items > 0) {
                    $value = array_slice($value, 0, $max_items);
                }

                foreach ($value as $i => $row) {
                    if (in_array('title', $enabled, true)) {
                        $value[$i]['title'] = sanitize_text_field($row['title']);
                    }
                    if (in_array('text', $enabled, true)) {
                        $value[$i]['text'] = wp_kses_post($row['text']);
                    }
                    if (in_array('image', $enabled, true)) {
                        $value[$i]['image'] = absint($row['image']);
                    }
                    if (in_array('link', $enabled, true)) {
                        $value[$i]['link'] = esc_url_raw($row['link']);
                    }
                }

                return $value;
            }

            public function format_value($value, $post_id, $field) {
                return $this->normalize_value($value, $this->get_enabled_fields($field));
            }

            private function get_enabled_fields($field) {
                $enabled = isset($field['enabled_fields']) ? $field['enabled_fields'] : $this->defaults['enabled_fields'];
                if (!is_array($enabled)) {
                    $enabled = array_filter(array_map('trim', explode(',', (string) $enabled)));
                }

                $allowed = array('title', 'text', 'image', 'link');
                $enabled = array_values(array_intersect($allowed, $enabled));

                return !empty($enabled) ? $enabled : $this->defaults['enabled_fields'];
            }

            private function get_max_items($field) {
                return isset($field['max_items']) ? max(0, absint($field['max_items'])) : 0;
            }

            private function get_field_labels($field) {
                return array(
                    'title' => !empty($field['title_label']) ? (string) $field['title_label'] : $this->defaults['title_label'],
                    'text' => !empty($field['text_label']) ? (string) $field['text_label'] : $this->defaults['text_label'],
                    'image' => !empty($field['image_label']) ? (string) $field['image_label'] : $this->defaults['image_label'],
                    'link' => !empty($field['link_label']) ? (string) $field['link_label'] : $this->defaults['link_label'],
                );
            }

            private function normalize_value($value, $enabled) {
                if (empty($value) || !is_array($value)) {
                    return array();
                }

                $rows = array();
                foreach ($value as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $clean = array();
                    $has_content = false;
                    if (in_array('title', $enabled, true)) {
                        $clean['title'] = isset($row['title']) ? (string) $row['title'] : '';
                        $has_content = $has_content || trim($clean['title']) !== '';
                    }
                    if (in_array('text', $enabled, true)) {
                        $clean['text'] = isset($row['text']) ? (string) $row['text'] : '';
                        $has_content = $has_content || trim(wp_strip_all_tags($clean['text'])) !== '';
                    }
                    if (in_array('image', $enabled, true)) {
                        $clean['image'] = isset($row['image']) ? absint($row['image']) : 0;
                        $has_content = $has_content || $clean['image'] > 0;
                    }
                    if (in_array('link', $enabled, true)) {
                        $clean['link'] = isset($row['link']) ? (string) $row['link'] : '';
                        $has_content = $has_content || trim($clean['link']) !== '';
                    }

                    if ($has_content) {
                        $rows[] = $clean;
                    }
                }

                return $rows;
            }

            private function render_row($name, $index, $row, $enabled, $labels) {
                $title = isset($row['title']) ? $row['title'] : '';
                $text = isset($row['text']) ? $row['text'] : '';
                $image = isset($row['image']) ? absint($row['image']) : 0;
                $link = isset($row['link']) ? $row['link'] : '';
                $thumb = $image ? wp_get_attachment_image_url($image, 'thumbnail') : '';

                echo '<div class="iu-acf-simple-repeater__row">';
                echo '<div class="iu-acf-simple-repeater__bar">';
                echo '<span class="iu-acf-simple-repeater__handle dashicons dashicons-menu" aria-hidden="true"></span>';
                echo '<strong class="iu-acf-simple-repeater__summary">' . esc_html($title ? $title : __('Item', 'istodata-utilities')) . '</strong>';
                echo '<button type="button" class="button-link-delete iu-acf-simple-repeater__remove">' . esc_html__('Αφαίρεση', 'istodata-utilities') . '</button>';
                echo '</div>';
                echo '<div class="iu-acf-simple-repeater__body">';

                if (in_array('title', $enabled, true)) {
                    echo '<label class="iu-acf-simple-repeater__field">';
                    echo '<span>' . esc_html($labels['title']) . '</span>';
                    echo '<input type="text" class="iu-acf-simple-repeater__title" name="' . esc_attr($name) . '[' . esc_attr($index) . '][title]" value="' . esc_attr($title) . '" />';
                    echo '</label>';
                }

                if (in_array('text', $enabled, true)) {
                    echo '<label class="iu-acf-simple-repeater__field">';
                    echo '<span>' . esc_html($labels['text']) . '</span>';
                    echo '<textarea rows="4" name="' . esc_attr($name) . '[' . esc_attr($index) . '][text]">' . esc_textarea($text) . '</textarea>';
                    echo '</label>';
                }

                if (in_array('image', $enabled, true)) {
                    echo '<div class="iu-acf-simple-repeater__field iu-acf-simple-repeater__media">';
                    echo '<span>' . esc_html($labels['image']) . '</span>';
                    echo '<input type="hidden" class="iu-acf-simple-repeater__image-id" name="' . esc_attr($name) . '[' . esc_attr($index) . '][image]" value="' . esc_attr($image) . '" />';
                    echo '<div class="iu-acf-simple-repeater__preview"' . ($thumb ? '' : ' hidden') . '>';
                    echo $thumb ? '<img src="' . esc_url($thumb) . '" alt="" />' : '<img src="" alt="" />';
                    echo '</div>';
                    echo '<button type="button" class="button iu-acf-simple-repeater__select-image">' . esc_html__('Select Image', 'istodata-utilities') . '</button> ';
                    echo '<button type="button" class="button-link-delete iu-acf-simple-repeater__clear-image"' . ($image ? '' : ' hidden') . '>' . esc_html__('Clear', 'istodata-utilities') . '</button>';
                    echo '</div>';
                }

                if (in_array('link', $enabled, true)) {
                    echo '<label class="iu-acf-simple-repeater__field">';
                    echo '<span>' . esc_html($labels['link']) . '</span>';
                    echo '<input type="url" name="' . esc_attr($name) . '[' . esc_attr($index) . '][link]" value="' . esc_attr($link) . '" />';
                    echo '</label>';
                }

                echo '</div>';
                echo '</div>';
            }
        }

        acf_register_field_type('IU_ACF_Simple_Repeater_Field');
    }
}

add_action('acf/include_field_types', 'iu_acf_simple_repeater_register_field');
