<?php
// Register the Scroll To Top widget only if Elementor is present; enablement controlled by plugin settings
if (!defined('ABSPATH')) { exit; }

add_action('elementor/widgets/register', function($widgets_manager){
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    if (empty($additional['scroll_to_top'])) { return; }
    if (!class_exists('Elementor\\Widget_Base')) { return; }
    require_once __DIR__ . '/elementor-scroll-to-top-widget/class-iu-scroll-to-top-widget.php';
    $widget = new \IU_Scroll_To_Top_Widget();
    if (method_exists($widgets_manager, 'register')) { $widgets_manager->register($widget); }
    else if (method_exists($widgets_manager, 'register_widget_type')) { $widgets_manager->register_widget_type($widget); }
});

