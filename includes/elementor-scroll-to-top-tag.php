<?php
// Elementor Dynamic Tag: Scroll To Top URL
if (!defined('ABSPATH')) { exit; }

add_action('elementor/dynamic_tags/register_tags', function($dynamic_tags){
    // Ensure Elementor is available and feature enabled
    if (!class_exists('Elementor\Plugin')) { return; }
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    if (empty($additional['scroll_to_top'])) { return; }

    if (class_exists('Elementor\Core\DynamicTags\Tag')) {
        if (!class_exists('IU_Scroll_To_Top_Url_Tag')) {
            class IU_Scroll_To_Top_Url_Tag extends \Elementor\Core\DynamicTags\Tag {
                public function get_name() { return 'isto-scroll-to-top-url'; }
                public function get_title() { return __('Scroll To Top URL', 'istodata-utilities'); }
                public function get_group() { return 'site'; }
                public function get_categories() { return [ \Elementor\Modules\DynamicTags\Module::URL_CATEGORY ]; }
                public function render() { echo esc_url('#iu-scroll-top'); }
            }
        }
        if (is_object($dynamic_tags) && method_exists($dynamic_tags, 'register_tag')) {
            $dynamic_tags->register_tag('IU_Scroll_To_Top_Url_Tag');
        }
    }
});

