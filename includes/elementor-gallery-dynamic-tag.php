<?php
// Elementor Pro Dynamic Tag: Post Gallery (Isto)

if (!defined('ABSPATH')) { exit; }

if (!function_exists('iu_normalize_isto_gallery_ids')) {
    function iu_normalize_isto_gallery_ids($ids) {
        if (is_array($ids)) {
            return array_values(array_filter(array_map('absint', $ids)));
        }

        if (is_string($ids)) {
            return array_values(array_filter(array_map('absint', explode(',', $ids))));
        }

        return array();
    }
}

if (!function_exists('iu_isto_gallery_wpml_available')) {
    function iu_isto_gallery_wpml_available() {
        return defined('ICL_SITEPRESS_VERSION') || has_filter('wpml_object_id') || function_exists('icl_object_id');
    }
}

if (!function_exists('iu_translate_isto_gallery_attachment_ids')) {
    function iu_translate_isto_gallery_attachment_ids($ids, $language = null) {
        $ids = iu_normalize_isto_gallery_ids($ids);

        if (empty($ids) || !iu_isto_gallery_wpml_available()) {
            return $ids;
        }

        if (!$language) {
            $language = apply_filters('wpml_current_language', null);
        }

        if (!$language) {
            return $ids;
        }

        $translated_ids = array();

        foreach ($ids as $id) {
            $translated_id = apply_filters('wpml_object_id', $id, 'attachment', true, $language);
            $translated_id = absint($translated_id ? $translated_id : $id);

            if ($translated_id > 0) {
                $translated_ids[] = $translated_id;
            }
        }

        return $translated_ids;
    }
}

if (!function_exists('iu_get_isto_gallery_ids_for_post')) {
    function iu_get_isto_gallery_ids_for_post($post_id) {
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return array();
        }

        $ids = iu_normalize_isto_gallery_ids(get_post_meta($post_id, '_isto_gallery_ids', true));

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

        $default_post_id = apply_filters('wpml_object_id', $post_id, $post_type, true, $default_language);
        $default_post_id = absint($default_post_id);

        if ($default_post_id <= 0 || $default_post_id === $post_id) {
            return array();
        }

        $fallback_ids = iu_normalize_isto_gallery_ids(get_post_meta($default_post_id, '_isto_gallery_ids', true));

        if (empty($fallback_ids)) {
            return array();
        }

        return iu_translate_isto_gallery_attachment_ids($fallback_ids);
    }
}

// Register tag only when Elementor Pro is active and feature enabled
add_action('elementor/dynamic_tags/register_tags', function($dynamic_tags){
    // Ensure Elementor Pro (Dynamic Tags) availability
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    if (!is_plugin_active('elementor-pro/elementor-pro.php')) {
        return;
    }

    // Check plugin setting
    $settings = get_option('istodata_utilities_settings', array());
    $enabled = !empty($settings['additional']['elementor_image_gallery']);
    if (!$enabled) { return; }

    if (class_exists('Elementor\\Core\\DynamicTags\\Data_Tag')) {
        class IU_Gallery_Tag extends \Elementor\Core\DynamicTags\Data_Tag {

            public function get_name() {
                return 'isto-post-gallery';
            }

            public function get_title() {
                return __('Post Gallery', 'istodata-utilities');
            }

            public function get_group() {
                // Keep it under the Post group for familiarity
                return 'post';
            }

            public function get_categories() {
                return [ \Elementor\Modules\DynamicTags\Module::GALLERY_CATEGORY ];
            }

            protected function register_controls() {
                // No controls for now; reads current post meta
            }

            public function get_value( array $options = [] ) {
                $post_id = get_the_ID();
                if (!$post_id) { return []; }
                $ids = iu_get_isto_gallery_ids_for_post($post_id);
                $items = array();
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $id = absint($id);
                        if ($id > 0) {
                            $items[] = array('id' => $id);
                        }
                    }
                }
                return $items;
            }
        }
        // Register our tag
        $dynamic_tags->register_tag('IU_Gallery_Tag');
    }
});
