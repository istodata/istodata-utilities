<?php
if (!defined('ABSPATH')) { exit; }

add_action('elementor/widgets/register', function($widgets_manager){
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    $enabled = !empty($additional['elementor_simple_repeater_widget']);
    if (!$enabled) {
        return;
    }

    if (!class_exists('Elementor\\Widget_Base')) {
        return;
    }

    require_once __DIR__ . '/elementor-simple-repeater-widget/class-iu-simple-repeater-widget.php';
    $widget = new \IU_Simple_Repeater_Widget();
    if (method_exists($widgets_manager, 'register')) {
        $widgets_manager->register($widget);
    } else if (method_exists($widgets_manager, 'register_widget_type')) {
        $widgets_manager->register_widget_type($widget);
    }
});
