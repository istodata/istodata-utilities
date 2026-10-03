<?php
/**
 * Plugin Name: IU Kit Fragment Cache Staging Harness
 * Version: 0.2.0
 * Description: Private-query test of the Kit fragment source; no saved element edits.
 */
if (!defined('ABSPATH')) {
    exit;
}

function iu_kit_fragment_harness_enabled() {
    return ($_SERVER['HTTP_HOST'] ?? '') === 'wordpress-218158-6702910.cloudwaysapps.com' &&
        ($_GET['iu_kit_fragment_test'] ?? '') === '26fa46c6-20260930-a7d4e2' &&
        !is_admin() && !is_user_logged_in();
}

add_action('plugins_loaded', function () {
    if (!iu_kit_fragment_harness_enabled()) {
        return;
    }
    $GLOBALS['iu_kit_fragment_test'] = array('target' => 0, 'nested' => 0, 'loop' => 0,
        'injected' => 0, 'inside' => false, 'result' => 'none', 'gate' => 'none', 'eligible' => 'none',
        'meta' => array(), 'key' => 'none', 'document' => 0, 'audit_failed' => 0, 'graph_reason' => 'none');
    foreach (array('updated_post_meta', 'added_post_meta', 'deleted_post_meta') as $hook) {
        add_action($hook, function ($meta_id, $post_id, $key) {
            $GLOBALS['iu_kit_fragment_test']['meta'][$key] = true;
        }, 0, 3);
    }
    add_action('iu_elementor_fragment_gate', function ($language, $ok, $post_id) {
        if ((int) $post_id === 30) {
            $GLOBALS['iu_kit_fragment_test']['gate'] = ($language ?: 'no-lang') . ':' . ($ok ? 'ok' : 'blocked');
        }
    }, 10, 3);
    add_action('iu_elementor_fragment_node', function ($id, $eligible, $post_id) {
        if ($id === '26fa46c6') {
            $GLOBALS['iu_kit_fragment_test']['eligible'] = $eligible ? 'yes' : 'no';
            $GLOBALS['iu_kit_fragment_test']['document'] = (int) $post_id;
        }
    }, 10, 3);
    // Only declare our observational callbacks harmless. No eligibility grant,
    // fingerprints or dependency manifest: the normal Kit graph adapter decides.
    add_filter('iu_elementor_fragment_graph_callback_allowed', function ($allowed, $callback) {
        return $allowed || ($callback instanceof Closure &&
            (new ReflectionFunction($callback))->getFileName() === __FILE__);
    }, 10, 2);
    add_action('iu_elementor_fragment_graph_rejected', function ($reason) {
        $GLOBALS['iu_kit_fragment_test']['graph_reason'] = $reason;
    });
    add_action('elementor/frontend/widget/before_render', function ($element) {
        if ($element->get_id() === '26fa46c6' && is_array($element->get_data('_iu_fragment_build'))) {
            $delay = min(60, absint($_GET['iu_kit_delay'] ?? 0));
            if ($delay) sleep($delay);
        }
    }, -10);
    add_filter('iu_elementor_fragment_allow_query', function ($allowed, $keys) {
        return count(array_diff($keys, array('iu_kit_fragment_test', 'iu_kit_request', 'iu_kit_generation', 'iu_kit_delay', 'iu_kit_wait'))) === 0;
    }, 10, 2);
    add_filter('iu_elementor_fragment_cold_wait_seconds', function ($wait) {
        return isset($_GET['iu_kit_wait']) ? max(0, min(45, (float) $_GET['iu_kit_wait'])) : $wait;
    });
    add_filter('iu_elementor_fragment_key_context', function ($context) {
        $context['staging_generation'] = sanitize_key($_GET['iu_kit_generation'] ?? 'default');
        return $context;
    });
    add_filter('elementor/frontend/builder_content_data', function ($data, $post_id) {
        if (!is_array($data)) {
            return $data;
        }
        $visit = function ($nodes) use (&$visit) {
            foreach ($nodes as &$node) {
                if (!is_array($node)) {
                    continue;
                }
                if (($node['id'] ?? '') === '26fa46c6' && ($node['widgetType'] ?? '') === 'mega-menu') {
                    $node['settings']['iu_fragment_cache'] = 'yes';
                    $node['settings']['iu_fragment_cache_ttl'] = '86400';
                    ++$GLOBALS['iu_kit_fragment_test']['injected'];
                } elseif (isset($node['elements']) && is_array($node['elements'])) {
                    $node['elements'] = $visit($node['elements']);
                }
            }
            unset($node);
            return $nodes;
        };
        return $visit($data);
    }, PHP_INT_MAX - 1, 2);
    add_action('wp', function () {
        $order = array();
        foreach (($GLOBALS['wp_filter']['elementor/frontend/builder_content_data']->callbacks ?? array()) as $priority => $group) {
            foreach ($group as $entry) {
                $fn = $entry['function'];
                $ref = is_array($fn) ? new ReflectionMethod($fn[0], $fn[1]) : new ReflectionFunction($fn);
                $name = is_array($fn) ? (is_object($fn[0]) ? get_class($fn[0]) : $fn[0]) . '::' . $fn[1] : (is_string($fn) ? $fn : 'Closure');
                $order[] = array('order' => count($order) + 1, 'priority' => $priority, 'callback' => $name,
                    'source' => $ref->getFileName(), 'line' => $ref->getStartLine());
            }
        }
        $GLOBALS['iu_kit_fragment_test']['builder_order'] = $order;
    }, -100);
    add_action('iu_elementor_fragment_gate', function ($language, $ok, $id, $data) {
        $find = function ($nodes) use (&$find) {
            foreach ((array) $nodes as $node) {
                if (($node['id'] ?? '') === '26fa46c6') return $node;
                if (!empty($node['elements'])) { $found = $find($node['elements']); if ($found) return $found; }
            }
            return null;
        };
        $node = $find($data);
        if ($node) {
            $GLOBALS['iu_kit_fragment_test']['filtered_node'] = hash('sha256', wp_json_encode($node));
            $GLOBALS['iu_kit_fragment_test']['gate'] = ($language ?: 'no-lang') . ':' . ($ok ? 'ok' : 'blocked');
        }
    }, 10, 4);
    add_action('iu_elementor_fragment_before_substitution', function ($node, $id, $key) {
        if (($node['id'] ?? '') === '26fa46c6') {
            $GLOBALS['iu_kit_fragment_test']['lookup_started'] = microtime(true);
            $GLOBALS['iu_kit_fragment_test']['key_node'] = hash('sha256', wp_json_encode($node));
            $GLOBALS['iu_kit_fragment_test']['key'] = $key;
        }
    }, 10, 3);
    add_action('iu_elementor_fragment_cache_selected', function ($id) {
        if ($id === '26fa46c6') $GLOBALS['iu_kit_fragment_test']['lookup_ms'] =
            (microtime(true) - $GLOBALS['iu_kit_fragment_test']['lookup_started']) * 1000;
    });
    add_action('elementor/frontend/widget/before_render', function ($element) {
        if ($element->get_id() === '26fa46c6' && $element->get_name() === 'mega-menu') {
            ++$GLOBALS['iu_kit_fragment_test']['target'];
            $GLOBALS['iu_kit_fragment_test']['inside'] = true;
        }
    }, 0);
    add_action('elementor/frontend/before_render', function () {
        if ($GLOBALS['iu_kit_fragment_test']['inside']) {
            ++$GLOBALS['iu_kit_fragment_test']['nested'];
        }
    }, 0);
    add_action('elementor/frontend/before_get_builder_content', function ($document) {
        if ($GLOBALS['iu_kit_fragment_test']['inside'] && method_exists($document, 'get_post') &&
            (int) $document->get_post()->ID === 1635) {
            ++$GLOBALS['iu_kit_fragment_test']['loop'];
        }
    }, 0);
    add_action('elementor/frontend/widget/after_render', function ($element) {
        if ($element->get_id() === '26fa46c6' && $element->get_name() === 'mega-menu') {
            $GLOBALS['iu_kit_fragment_test']['inside'] = false;
            $hooks = array();
            foreach (array('excerpt_length', 'excerpt_more') as $hook) {
                foreach (($GLOBALS['wp_filter'][$hook]->callbacks ?? array()) as $priority => $group) {
                    foreach ($group as $entry) {
                        $fn = $entry['function'];
                        $hooks[] = array('hook' => $hook, 'priority' => $priority,
                            'callback' => is_array($fn) ? get_class($fn[0]) . '::' . $fn[1] : (is_string($fn) ? $fn : 'Closure'));
                    }
                }
            }
            $GLOBALS['iu_kit_fragment_test']['excerpt_hooks'] = $hooks;
        }
    }, PHP_INT_MAX);
    add_action('iu_elementor_fragment_result', function ($reason, $build) {
        $GLOBALS['iu_kit_fragment_test']['result'] = $reason;
        $GLOBALS['iu_kit_fragment_test']['key'] = $build['key'] ?? $GLOBALS['iu_kit_fragment_test']['key'];
        $GLOBALS['iu_kit_fragment_test']['excerpt_profile'] = IU_Elementor_Fragment_Excerpt::profile();
    }, 10, 2);
    add_action('shutdown', function () {
        $s = $GLOBALS['iu_kit_fragment_test'];
        echo "\n<!-- IU_KIT_BUILDER_ORDER " . wp_json_encode($s['builder_order'] ?? array()) . " -->\n";
        echo '<!-- IU_KIT_FILTERED_NODE before=' . ($s['filtered_node'] ?? 'none') . ' keyed=' . ($s['key_node'] ?? 'none') . " -->\n";
        echo '<!-- IU_KIT_EXCERPT_HOOKS ' . wp_json_encode($s['excerpt_hooks'] ?? array()) . " -->\n";
        echo '<!-- IU_KIT_EXCERPT_PROFILE ' . wp_json_encode($s['excerpt_profile'] ?? null) . " -->\n";
        echo '<!-- IU_KIT_LOOKUP_MS ' . (int) ($s['lookup_ms'] ?? -1) . " -->\n";
        printf("\n<!-- IU_KIT_FRAGMENT_TEST target=%d nested=%d loop=%d injected=%d result=%s gate=%s eligible=%s cache_loaded=%d admin=%d logged=%d cookies=%s post=%d preview=%d feed=%d search=%d get=%s element_cache=%s key=%s meta=%s document=%d audit_failed=%d graph=%s -->\n",
            $s['target'], $s['nested'], $s['loop'], $s['injected'], esc_html($s['result']),
            esc_html($s['gate']), esc_html($s['eligible']),
            class_exists('IU_Elementor_Fragment_Cache') ? 1 : 0,
            is_admin() ? 1 : 0, is_user_logged_in() ? 1 : 0, esc_html(implode(',', array_keys($_COOKIE))), count($_POST),
            is_preview() ? 1 : 0, is_feed() ? 1 : 0, is_search() ? 1 : 0,
            esc_html(implode(',', array_keys($_GET))), esc_html((string) get_option('elementor_element_cache_ttl')),
            esc_html($s['key']), esc_html(implode(',', array_keys($s['meta']))), $s['document'], $s['audit_failed'], esc_html($s['graph_reason']));
    }, PHP_INT_MAX);
}, 0);

function iu_kit_fragment_harness_load() {
    if (($_SERVER['HTTP_HOST'] ?? '') !== 'wordpress-218158-6702910.cloudwaysapps.com') {
        return;
    }
    if (did_action('elementor/loaded') || class_exists('Elementor\\Plugin')) {
        require_once __DIR__ . '/elementor-fragment-cache.php';
    }
}
add_action('plugins_loaded', 'iu_kit_fragment_harness_load', 20);
add_action('elementor/loaded', 'iu_kit_fragment_harness_load');
