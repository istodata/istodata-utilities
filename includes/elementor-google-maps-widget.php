<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

// Register the widget only if Elementor is present; actual enablement is controlled by plugin settings
add_action('elementor/widgets/register', function($widgets_manager){
    // Check plugin setting first
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    $enabled = !empty($additional['elementor_google_maps_widget']);
    if (!$enabled) {
        return;
    }

    if (!class_exists('Elementor\\Widget_Base')) {
        return;
    }

    require_once __DIR__ . '/elementor-google-maps-widget/class-iu-google-maps-widget.php';
    $widget = new \IU_Google_Maps_Widget();
    if (method_exists($widgets_manager, 'register')) {
        $widgets_manager->register($widget);
    } else if (method_exists($widgets_manager, 'register_widget_type')) {
        $widgets_manager->register_widget_type($widget);
    }
});

