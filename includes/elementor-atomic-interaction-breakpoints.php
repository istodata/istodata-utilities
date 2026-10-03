<?php
/** Temporary internal-API workaround for Elementor #35831. */
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/elementor-compatibility.php';

function iu_atomic_interaction_breakpoints_supported() {
    return iu_elementor_feature_supported('atomic');
}

function iu_init_atomic_interaction_breakpoints() {
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;
    add_action('wp_enqueue_scripts', 'iu_attach_atomic_interaction_breakpoints', 1000);
    add_action('wp_print_footer_scripts', 'iu_attach_atomic_interaction_breakpoints', 1);
    add_action('admin_notices', 'iu_atomic_interaction_breakpoints_notice');
}

function iu_attach_atomic_interaction_breakpoints() {
    static $attached = false;
    $settings = get_option('istodata_utilities_settings', array());
    if ($attached || empty($settings['optimizations']['elementor_atomic_interaction_breakpoints'])
        || is_admin() || !iu_atomic_interaction_breakpoints_supported()
        || !wp_script_is('elementor-interactions-pro', 'enqueued')
        || wp_script_is('elementor-interactions-pro', 'done')) {
        return;
    }
    // Inline 'before' runs after the shared dependency and before Pro captures its APIs.
    $script = file_get_contents(IU_PLUGIN_PATH . 'assets/js/elementor-atomic-interaction-breakpoints.js');
    if (is_string($script) && $script !== '') {
        $attached = wp_add_inline_script('elementor-interactions-pro', $script, 'before');
    }
}

function iu_atomic_interaction_breakpoints_notice() {
    $settings = get_option('istodata_utilities_settings', array());
    if (empty($settings['optimizations']['elementor_atomic_interaction_breakpoints'])
        || iu_atomic_interaction_breakpoints_supported() || !current_user_can('manage_options')) {
        return;
    }
    echo '<div class="notice notice-warning"><p>';
    echo esc_html('ISTODATA Kit: Η διόρθωση breakpoints στα Atomic Interactions είναι ενεργοποιημένη, αλλά δεν εφαρμόζεται στις εγκατεστημένες εκδόσεις. ' . iu_elementor_compatibility_description('atomic') . ' Ελέγξτε την επίσημη λύση του #35831 και απενεργοποιήστε την επιλογή όταν δεν χρειάζεται.');
    echo ' <a href="https://github.com/elementor/elementor/issues/35831" target="_blank" rel="noopener noreferrer">#35831</a></p></div>';
}
