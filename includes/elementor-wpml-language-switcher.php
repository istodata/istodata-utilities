<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

// Register the widget only if Elementor and WPML are present; actual enablement is controlled by plugin settings
add_action('elementor/widgets/register', function($widgets_manager) {
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    $enabled = !empty($additional['elementor_wpml_language_switcher']);

    if (!$enabled) {
        return;
    }

    if (!class_exists('Elementor\\Widget_Base')) {
        return;
    }

    if (!defined('ICL_SITEPRESS_VERSION') && !has_filter('wpml_active_languages') && !function_exists('icl_object_id')) {
        return;
    }

    require_once __DIR__ . '/elementor-wpml-language-switcher/class-iu-wpml-language-switcher-widget.php';
    $widget = new \IU_WPML_Language_Switcher_Widget();

    if (method_exists($widgets_manager, 'register')) {
        $widgets_manager->register($widget);
    } else if (method_exists($widgets_manager, 'register_widget_type')) {
        $widgets_manager->register_widget_type($widget);
    }
});
