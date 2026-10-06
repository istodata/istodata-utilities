<?php
// Safety check
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue editor-only assets to show a small badge in the element panel header
add_action('elementor/editor/after_enqueue_scripts', function () {
    wp_enqueue_script(
        'iu-elementor-device-visibility-editor',
        IU_PLUGIN_URL . 'assets/js/elementor-device-visibility-editor.js',
        array('jquery'),
        IU_PLUGIN_VERSION,
        true
    );
});

add_action('elementor/editor/after_enqueue_styles', function () {
    wp_enqueue_style(
        'iu-elementor-device-visibility-editor',
        IU_PLUGIN_URL . 'assets/css/elementor-device-visibility-editor.css',
        array(),
        IU_PLUGIN_VERSION
    );
});

// Register our control injections during Elementor init to ensure timing is correct
add_action('elementor/init', function () {
    // Add controls to Advanced tab for all element types at reliable anchors
add_action('elementor/element/common/section_effects/after_section_end', 'iu_elementor_add_device_visibility_controls', 20, 2);
add_action('elementor/element/common/section_advanced/after_section_end', 'iu_elementor_add_device_visibility_controls', 20, 2);
add_action('elementor/element/common/section_advanced/before_section_end', 'iu_elementor_add_device_visibility_controls', 20, 2);
    add_action('elementor/element/common/_section_responsive/after_section_end', 'iu_elementor_add_device_visibility_controls', 20, 2);
add_action('elementor/element/container/section_effects/after_section_end', 'iu_elementor_add_device_visibility_controls', 20, 2);
add_action('elementor/element/container/section_advanced/after_section_end', 'iu_elementor_add_device_visibility_controls', 20, 2);
add_action('elementor/element/container/section_advanced/before_section_end', 'iu_elementor_add_device_visibility_controls', 20, 2);
    add_action('elementor/element/container/_section_responsive/after_section_end', 'iu_elementor_add_device_visibility_controls', 20, 2);
    // Explicitly target Heading widget as requested (Elementor Heading)
    // Heading-specific hooks removed; common hooks above cover Heading too
    // Generic fallback in case section IDs differ
add_action('elementor/element/after_section_end', 'iu_elementor_add_device_visibility_controls_after_advanced', 30, 3);

// (Debug notice removed)
});

function iu_elementor_add_device_visibility_controls_after_advanced($element, $section_id, $args) {
    if ('section_advanced' !== $section_id) {
        return;
    }

    iu_elementor_add_device_visibility_controls($element);
}

function iu_elementor_add_device_visibility_controls($element, $args = null) {
    // Prevent duplicate injection if multiple hooks fire
    if (method_exists($element, 'get_controls')) {
        $controls = $element->get_controls();
        if (isset($controls['iu_hide_on_phone'])) {
            return;
        }
    }
    // Add our section under Advanced tab
    $element->start_controls_section(
        'iu_device_visibility_section',
        [
            'label' => __('Ορατότητα Συσκευών', 'istodata-utilities'),
            'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
        ]
    );

    $element->add_control(
        'iu_hide_on_desktop_tablet',
        [
            'label'        => __('Απόκρυψη σε Desktop/Tablet', 'istodata-utilities'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Ναι', 'istodata-utilities'),
            'label_off'    => __('Όχι', 'istodata-utilities'),
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Δεν θα αποδίδεται στον κώδικα σε desktop και tablets.', 'istodata-utilities'),
        ]
    );

    $element->add_control(
        'iu_hide_on_phone',
        [
            'label'        => __('Απόκρυψη σε Κινητά', 'istodata-utilities'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Ναι', 'istodata-utilities'),
            'label_off'    => __('Όχι', 'istodata-utilities'),
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Δεν θα αποδίδεται στον κώδικα σε κινητά (τηλέφωνα).', 'istodata-utilities'),
        ]
    );

    $element->end_controls_section();
}

// Remove hidden branches before Elementor creates their element objects or runs widgets.
add_filter('elementor/frontend/builder_content_data', 'iu_elementor_filter_builder_content_by_device', PHP_INT_MAX, 2);

/**
 * Filter only a frontend render copy. The same Elementor hook is also used when
 * loading template-library data, so never change editor, preview or admin data.
 */
function iu_elementor_filter_builder_content_by_device($data, $post_id) {
    if (iu_elementor_device_visibility_is_preview() ||
        (function_exists('is_admin') && is_admin()) ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        !is_array($data)) {
        return $data;
    }

    return iu_elementor_prune_hidden_elements($data, iu_request_is_phone());
}

/** Keep array order and drop an entire subtree when its widget/container is hidden. */
function iu_elementor_prune_hidden_elements($elements, $is_phone) {
    $visible = [];

    foreach ($elements as $element) {
        if (!is_array($element)) {
            $visible[] = $element;
            continue;
        }

        $type = isset($element['elType']) ? $element['elType'] : '';
        if (('widget' === $type || 'container' === $type ||
                (class_exists('IU_Elementor_Fragment_Atomic') && IU_Elementor_Fragment_Atomic::node($element))) &&
            iu_elementor_device_visibility_hides($element['settings'] ?? [], $is_phone)) {
            continue;
        }

        if (isset($element['elements']) && is_array($element['elements'])) {
            $element['elements'] = iu_elementor_prune_hidden_elements($element['elements'], $is_phone);
        }

        // Transient render-copy marker: should_render can skip elements already
        // examined here. It is never written back to Elementor's saved document.
        $element['_iu_device_visibility_checked'] = true;
        $visible[] = $element;
    }

    return $visible;
}

/** Keep should_render for direct element printing and render paths without builder_content_data. */
add_filter('elementor/frontend/widget/should_render', 'iu_elementor_should_render_by_device', 10, 2);
add_filter('elementor/frontend/container/should_render', 'iu_elementor_should_render_by_device', 10, 2);

function iu_elementor_device_visibility_is_preview() {
    if (function_exists('is_preview') && is_preview()) {
        return true;
    }

    if (!class_exists('Elementor\\Plugin') || !\Elementor\Plugin::$instance) {
        return false;
    }

    $plugin = \Elementor\Plugin::$instance;
    return ($plugin->editor && $plugin->editor->is_edit_mode()) ||
        ($plugin->preview && $plugin->preview->is_preview_mode());
}

function iu_elementor_device_visibility_hides($settings, $is_phone) {
    if (!is_array($settings)) {
        return false;
    }

    $value = $settings[$is_phone ? 'iu_hide_on_phone' : 'iu_hide_on_desktop_tablet'] ?? null;
    return $value === 'yes' || (is_array($value) && ($value['$$type'] ?? '') === 'boolean' &&
        empty($value['disabled']) && ($value['value'] ?? null) === true);
}

/**
 * Determine if element should render based on device visibility controls.
 * Leaves everything visible in the Elementor editor for clarity.
 */
function iu_elementor_should_render_by_device($should_render, $element) {
    // A filtered element has already passed the early check. Directly printed
    // elements without the marker still use the legacy late fallback.
    if (iu_elementor_device_visibility_is_preview() ||
        (method_exists($element, 'get_data') && true === $element->get_data('_iu_device_visibility_checked'))) {
        return $should_render;
    }

    return iu_elementor_device_visibility_hides([
        'iu_hide_on_phone' => $element->get_settings('iu_hide_on_phone'),
        'iu_hide_on_desktop_tablet' => $element->get_settings('iu_hide_on_desktop_tablet'),
    ], iu_request_is_phone()) ? false : $should_render;
}

/**
 * Phone-only detector: true for phones, false for tablets/desktop.
 */
function iu_request_is_phone(): bool {
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }

    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

    // Explicit tablet indicators
    $tablet_indicators = [ 'ipad', 'tablet', 'kindle', 'silk', 'playbook', 'nexus 7', 'nexus 9', 'tab' ];
    foreach ($tablet_indicators as $t) {
        if (strpos($ua, $t) !== false) {
            return false; // treat as non-phone
        }
    }

    // iPhone / iPod
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipod') !== false) {
        return true;
    }

    // Android: phones typically include 'mobile'; tablets usually don't
    if (strpos($ua, 'android') !== false) {
        return (strpos($ua, 'mobile') !== false);
    }

    // Windows Phone
    if (strpos($ua, 'windows phone') !== false) {
        return true;
    }

    // Generic mobile (fallback) - avoid tablets already checked above
    if (strpos($ua, 'mobile') !== false) {
        return true;
    }

    return false;
}

// Optional: visual indicator in editor structure panel (non-blocking)
// Note: Elementor’s Structure panel is powered by Backbone templates. A
// full badge injection there needs template filters. We keep elements visible
// in editor as requested; adding a badge can be explored later if desired.
