<?php
// Elementor: Per-element mobile animation disable toggle
// Loads only when WP Rocket active and option enabled (gated by caller)

use Elementor\Controls_Manager;

if (!defined('ABSPATH')) { exit; }

// Add a switcher control to elements/containers under Advanced tab
if (!function_exists('iu_add_mobile_anim_control')) {
    function iu_add_mobile_anim_control($element, $args = null) {
        if (!is_object($element) || !method_exists($element, 'add_control')) return;
        $element->start_controls_section(
            'iu_mobile_anim_controls',
            [
                'label' => __('Mobile Animations', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_ADVANCED,
            ]
        );
        $element->add_control(
            'iu_disable_mobile_animation',
            [
                'label' => __('Απενεργοποίηση animation στο κινητό', 'istodata-utilities'),
                'type'  => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );
        $element->end_controls_section();
    }
}
add_action('elementor/element/common/section_effects/after_section_end', 'iu_add_mobile_anim_control', 20, 2);
add_action('elementor/element/container/section_effects/after_section_end', 'iu_add_mobile_anim_control', 20, 2);

// Helper: add class to wrapper when toggle is enabled
$__iu_add_no_mobile_anim = function($element){
    $settings = $element->get_settings_for_display();
    if (!empty($settings['iu_disable_mobile_animation']) && $settings['iu_disable_mobile_animation'] === 'yes') {
        if (method_exists($element, 'add_render_attribute')) {
            $element->add_render_attribute('_wrapper', 'class', 'iu-no-mobile-anim');
        }
    }
};

// Cover all element types
add_action('elementor/frontend/element/before_render', $__iu_add_no_mobile_anim, 10, 1);
add_action('elementor/frontend/widget/before_render', $__iu_add_no_mobile_anim, 10, 1);
add_action('elementor/frontend/container/before_render', $__iu_add_no_mobile_anim, 10, 1);

// Print lightweight CSS that disables entrance animations on mobile for elements with the class
add_action('wp_head', function(){
    if (is_admin()) return;
    $breakpoint = (int) apply_filters('iu_elementor_mobile_breakpoint', 767);
    echo '<style id="iu-per-element-mobile-anim" media="(max-width: ' . (int) $breakpoint . 'px)">';
    echo '.iu-no-mobile-anim.elementor-invisible{visibility:visible!important;opacity:1!important;transform:none!important}';
    echo '.iu-no-mobile-anim.animated,.iu-no-mobile-anim.elementor-animated,.iu-no-mobile-anim.e-animated{animation:none!important;transition:none!important}';
    echo '</style>';
});
