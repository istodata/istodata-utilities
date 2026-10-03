<?php
/**
 * Plugin Name: IU Fragment Cache Staging Probe
 * Description: Temporary, request-gated proof of an early Elementor mega-menu cache hit.
 * Version: 0.3.0
 *
 * Test-only plugin. This file is not loaded by ISTODATA Kit.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Active plugins load before WordPress defines pluggable user functions.
add_action('plugins_loaded', function () {
// The probe cannot run on production or on ordinary staging traffic.
if (($_SERVER['HTTP_HOST'] ?? '') !== 'wordpress-218158-6702910.cloudwaysapps.com' ||
    ($_GET['iu_fragment_probe'] ?? '') !== '26fa46c6-20260930-a7d4e2' ||
    is_admin() ||
    is_user_logged_in() ||
    (defined('REST_REQUEST') && REST_REQUEST) ||
    (function_exists('is_preview') && is_preview()) ||
    get_option('elementor_element_cache_ttl') !== 'disable' ||
    stripos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Windows NT') === false ||
    stripos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Mobile') !== false) {
    return;
}

$GLOBALS['iu_fragment_probe'] = array(
    'state' => 'no-target',
    'target_render' => 0,
    'nested_elements' => 0,
    'template_1635' => 0,
    'inside_target' => false,
    'cache_key' => null,
    'style_queue_before' => array(),
    'styles_replayed' => 0,
    'styles_captured' => 0,
    'asset_bypass_handle' => '',
    'displayed_before' => array(),
    'displayed_captured' => 0,
    'displayed_replayed' => 0,
);

add_action('elementor/widgets/register', function ($manager) {
    if (!class_exists('Elementor\\Widget_Base')) {
        return;
    }

    class IU_Fragment_Probe_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'iu-fragment-probe'; }
        public function get_title() { return 'IU Fragment Probe'; }
        public function get_icon() { return 'eicon-code'; }
        public function get_categories() { return array('general'); }

        public function print_element() {
            $html = $this->get_data('_iu_probe_html');
            if (is_string($html)) {
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
    }

    $manager->register(new IU_Fragment_Probe_Widget());
});

add_filter('elementor/frontend/builder_content_data', function ($data, $post_id) {
    if ((int) $post_id !== 30 || !is_array($data)) {
        return $data;
    }

    $replace = function ($nodes) use (&$replace) {
        foreach ($nodes as &$node) {
            if (!is_array($node)) {
                continue;
            }
            if (($node['id'] ?? '') === '26fa46c6' &&
                ($node['elType'] ?? '') === 'widget' &&
                ($node['widgetType'] ?? '') === 'mega-menu') {
                $language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'unknown';
                $fingerprint = hash('sha256', wp_json_encode($node));
                $key = 'iu_probe_' . substr(hash('sha256', 'v3|30|26fa46c6|desktop|' . $language . '|' . $fingerprint), 0, 32);
                $GLOBALS['iu_fragment_probe']['cache_key'] = $key;
                $entry = get_transient($key);
                if (is_array($entry) && isset($entry['html']) && is_string($entry['html'])) {
                    $styles = isset($entry['styles']) && is_array($entry['styles']) ? $entry['styles'] : array();
                    $query_module = 'ElementorPro\\Modules\\QueryControl\\Module';
                    $displayed_ids = isset($entry['displayed_ids']) && is_array($entry['displayed_ids']) ? $entry['displayed_ids'] : null;
                    $can_replay = !empty($styles) &&
                        class_exists('Elementor\\Core\\Files\\CSS\\Post') &&
                        class_exists($query_module) &&
                        is_array($displayed_ids);
                    foreach ($styles as $handle) {
                        if (!is_string($handle) || !preg_match('/^[a-zA-Z0-9_-]+$/', $handle)) {
                            $can_replay = false;
                            $GLOBALS['iu_fragment_probe']['asset_bypass_handle'] = 'invalid';
                            break;
                        }
                        if (!preg_match('/^elementor-post-([0-9]+)$/', $handle) && !wp_style_is($handle, 'registered')) {
                            $can_replay = false;
                            $GLOBALS['iu_fragment_probe']['asset_bypass_handle'] = $handle;
                            break;
                        }
                    }
                    if ($can_replay) {
                        foreach ($styles as $handle) {
                            if (preg_match('/^elementor-post-([0-9]+)$/', $handle, $match)) {
                                \Elementor\Core\Files\CSS\Post::create((int) $match[1])->enqueue();
                            } else {
                                wp_enqueue_style($handle);
                            }
                            ++$GLOBALS['iu_fragment_probe']['styles_replayed'];
                        }
                        $query_module::add_to_avoid_list($displayed_ids);
                        $GLOBALS['iu_fragment_probe']['displayed_replayed'] = count($displayed_ids);
                        $node['widgetType'] = 'iu-fragment-probe';
                        $node['_iu_probe_html'] = $entry['html'];
                        $GLOBALS['iu_fragment_probe']['state'] = 'hit';
                    } else {
                        $GLOBALS['iu_fragment_probe']['state'] = 'asset-bypass';
                    }
                } else {
                    $GLOBALS['iu_fragment_probe']['state'] = 'miss';
                }
                break;
            }
            if (isset($node['elements']) && is_array($node['elements'])) {
                $node['elements'] = $replace($node['elements']);
            }
        }
        unset($node);
        return $nodes;
    };

    return $replace($data);
}, PHP_INT_MAX, 2);

add_action('elementor/frontend/widget/before_render', function ($element) {
    if ($element->get_id() !== '26fa46c6' || $element->get_name() !== 'mega-menu') {
        return;
    }
    ++$GLOBALS['iu_fragment_probe']['target_render'];
    $GLOBALS['iu_fragment_probe']['inside_target'] = true;
    $GLOBALS['iu_fragment_probe']['style_queue_before'] = wp_styles()->queue;
    $query_module = 'ElementorPro\\Modules\\QueryControl\\Module';
    $GLOBALS['iu_fragment_probe']['displayed_before'] = class_exists($query_module)
        ? $query_module::get_avoid_list_ids()
        : array();
    ob_start();
}, 0);

add_action('elementor/frontend/before_render', function ($element) {
    if (!empty($GLOBALS['iu_fragment_probe']['inside_target'])) {
        ++$GLOBALS['iu_fragment_probe']['nested_elements'];
    }
}, 0);

add_action('elementor/frontend/before_get_builder_content', function ($document) {
    if (!empty($GLOBALS['iu_fragment_probe']['inside_target']) &&
        method_exists($document, 'get_post') &&
        (int) $document->get_post()->ID === 1635) {
        ++$GLOBALS['iu_fragment_probe']['template_1635'];
    }
}, 0);

add_action('elementor/frontend/widget/after_render', function ($element) {
    if ($element->get_id() !== '26fa46c6' || $element->get_name() !== 'mega-menu' ||
        empty($GLOBALS['iu_fragment_probe']['inside_target'])) {
        return;
    }
    $html = ob_get_clean();
    $GLOBALS['iu_fragment_probe']['inside_target'] = false;
    if (is_string($html) && $html !== '' && $GLOBALS['iu_fragment_probe']['cache_key']) {
        $styles = array_values(array_diff(wp_styles()->queue, $GLOBALS['iu_fragment_probe']['style_queue_before']));
        $query_module = 'ElementorPro\\Modules\\QueryControl\\Module';
        $displayed_ids = class_exists($query_module)
            ? array_values(array_diff($query_module::get_avoid_list_ids(), $GLOBALS['iu_fragment_probe']['displayed_before']))
            : array();
        $GLOBALS['iu_fragment_probe']['styles_captured'] = count($styles);
        $GLOBALS['iu_fragment_probe']['displayed_captured'] = count($displayed_ids);
        set_transient($GLOBALS['iu_fragment_probe']['cache_key'], array(
            'html' => $html,
            'styles' => $styles,
            'displayed_ids' => $displayed_ids,
        ), 300);
    }
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, PHP_INT_MAX);

add_action('shutdown', function () {
    $probe = $GLOBALS['iu_fragment_probe'];
    printf(
        "\n<!-- IU_FRAGMENT_PROBE state=%s target_render=%d nested_elements=%d template_1635=%d styles_captured=%d styles_replayed=%d displayed_captured=%d displayed_replayed=%d asset_bypass_handle=%s -->\n",
        esc_html($probe['state']),
        (int) $probe['target_render'],
        (int) $probe['nested_elements'],
        (int) $probe['template_1635'],
        (int) $probe['styles_captured'],
        (int) $probe['styles_replayed'],
        (int) $probe['displayed_captured'],
        (int) $probe['displayed_replayed'],
        esc_html($probe['asset_bypass_handle'])
    );
}, PHP_INT_MAX);
}, 0);
