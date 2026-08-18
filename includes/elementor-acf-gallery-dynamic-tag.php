<?php
// Elementor Pro Dynamic Tag: ACF Post Gallery (Isto)

if (!defined('ABSPATH')) { exit; }

if (!function_exists('iu_get_acf_post_gallery_field_choices')) {
    function iu_get_acf_post_gallery_field_choices() {
        $choices = array(
            '' => __('Select an ISTODATA Post Gallery field', 'istodata-utilities'),
        );

        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            return $choices;
        }

        foreach (acf_get_field_groups() as $group) {
            $fields = acf_get_fields($group);
            if (empty($fields) || !is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (empty($field['key']) || empty($field['name']) || empty($field['type']) || $field['type'] !== 'iu_post_gallery') {
                    continue;
                }

                $label = !empty($field['label']) ? $field['label'] : $field['name'];
                $choices[$field['key']] = sprintf('%s (%s)', $label, $field['name']);
            }
        }

        return $choices;
    }
}

if (!function_exists('iu_get_acf_post_gallery_ids')) {
    function iu_get_acf_post_gallery_ids($field_name, $post_id) {
        if (!function_exists('get_field')) {
            return array();
        }

        $field_name = sanitize_key($field_name);
        $post_id = absint($post_id);
        if (!$field_name || !$post_id) {
            return array();
        }

        $ids = iu_normalize_isto_gallery_ids(get_field($field_name, $post_id, false));
        if (!empty($ids)) {
            return iu_translate_isto_gallery_attachment_ids($ids);
        }

        if (!iu_isto_gallery_wpml_available()) {
            return array();
        }

        $post_type = get_post_type($post_id);
        $default_language = apply_filters('wpml_default_language', null);
        if (!$post_type || !$default_language) {
            return array();
        }

        $default_post_id = absint(apply_filters('wpml_object_id', $post_id, $post_type, true, $default_language));
        if (!$default_post_id || $default_post_id === $post_id) {
            return array();
        }

        $ids = iu_normalize_isto_gallery_ids(get_field($field_name, $default_post_id, false));
        return iu_translate_isto_gallery_attachment_ids($ids);
    }
}

add_action('elementor/dynamic_tags/register_tags', function($dynamic_tags) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    if (!is_plugin_active('elementor-pro/elementor-pro.php')) {
        return;
    }

    $settings = get_option('istodata_utilities_settings', array());
    if (empty($settings['additional']['acf_post_gallery']) || !function_exists('get_field')) {
        return;
    }

    if (!class_exists('Elementor\\Core\\DynamicTags\\Data_Tag')) {
        return;
    }

    class IU_ACF_Post_Gallery_Tag extends \Elementor\Core\DynamicTags\Data_Tag {
        public function get_name() {
            return 'isto-acf-post-gallery';
        }

        public function get_title() {
            return __('ISTODATA ACF Post Gallery', 'istodata-utilities');
        }

        public function get_group() {
            return 'post';
        }

        public function get_categories() {
            return array(\Elementor\Modules\DynamicTags\Module::GALLERY_CATEGORY);
        }

        protected function register_controls() {
            $this->add_control('field_name', array(
                'label' => __('Key', 'istodata-utilities'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => iu_get_acf_post_gallery_field_choices(),
            ));
        }

        public function get_value(array $options = array()) {
            $field_name = $this->get_settings('field_name');
            $post_id = get_the_ID();
            $ids = iu_get_acf_post_gallery_ids($field_name, $post_id);

            return array_map(function($id) {
                return array('id' => absint($id));
            }, $ids);
        }
    }

    $dynamic_tags->register_tag('IU_ACF_Post_Gallery_Tag');
});
