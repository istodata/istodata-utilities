<?php
/** Read-only callback source/order inventory; does not export callback state. */
function iu_fragment_audit_callback($fn, $depth = 0) {
    if ($depth > 12) return array('callback' => 'depth-limit');
    if (is_array($fn)) {
        $name = (is_object($fn[0]) ? get_class($fn[0]) : $fn[0]) . '::' . $fn[1];
        $ref = new ReflectionMethod($fn[0], $fn[1]);
    } else {
        $ref = new ReflectionFunction($fn);
        $name = is_string($fn) ? $fn : 'Closure';
    }
    $result = array('callback' => $name, 'source' => $ref->getFileName(),
        'start' => $ref->getStartLine(), 'end' => $ref->getEndLine());
    if ($fn instanceof Closure) {
        foreach ($ref->getStaticVariables() as $key => $value) {
            if (is_callable($value)) $result['wrapped'][$key] = iu_fragment_audit_callback($value, $depth + 1);
            elseif (is_object($value) && get_class($value) === 'WPML\\FP\\Promise') {
                foreach (array('onResolved', 'onReject', 'next') as $property) {
                    $prop = new ReflectionProperty($value, $property);
                    $inner = $prop->getValue($value);
                    if (is_callable($inner)) $result['promise'][$property] = iu_fragment_audit_callback($inner, $depth + 1);
                }
            }
        }
    }
    return $result;
}
foreach (array('elementor/frontend/builder_content_data', 'get_post_metadata',
    'post_type_link', 'get_the_excerpt', 'posts_where', 'posts_join', 'posts_fields', 'posts_orderby',
    'posts_groupby', 'posts_distinct', 'post_limits', 'posts_clauses_request', 'posts_request',
    'posts_where_request', 'posts_join_request', 'posts_fields_request', 'posts_orderby_request',
    'posts_groupby_request', 'posts_distinct_request', 'post_limits_request',
    'the_title', 'wp_trim_excerpt', 'excerpt_length', 'excerpt_more', 'post_thumbnail_html',
    'wp_get_attachment_url', 'wp_get_attachment_image_src', 'wp_get_attachment_image_attributes',
    'get_the_post_thumbnail_url', 'get_the_terms', 'the_content', 'excerpt_allowed_blocks',
    'excerpt_allowed_wrapper_blocks', 'image_downsize', 'get_attached_file', 'wp_get_attachment_metadata',
    'wp_calculate_image_srcset', 'wp_calculate_image_sizes', 'get_the_date', 'get_the_time') as $hook) {
    $order = 0;
    foreach (($GLOBALS['wp_filter'][$hook]->callbacks ?? array()) as $priority => $group) {
        foreach ($group as $entry) {
            $item = iu_fragment_audit_callback($entry['function']);
            echo wp_json_encode(array('hook' => $hook, 'order' => ++$order,
                'priority' => $priority, 'accepted_args' => $entry['accepted_args']) + $item) . "\n";
            if (in_array($item['callback'], array('RankMath\\Common::post_type_link',
                'remove_cpt_base::remove_slug', 'ElementorPro\\Modules\\LoopBuilder\\Module::filter_content_data'), true) ||
                $item['callback'] === 'Closure') {
                $lines = file($item['source']);
                echo implode('', array_slice($lines, $item['start'] - 1, $item['end'] - $item['start'] + 1)) . "\n";
            }
            if (strpos($hook, '_request') !== false) {
                echo implode('', array_slice(file($item['source']), $item['start'] - 1, $item['end'] - $item['start'] + 1)) . "\n";
            }
        }
    }
}
foreach (array('RANK_MATH_VERSION', 'ICL_SITEPRESS_VERSION', 'ELEMENTOR_VERSION', 'ELEMENTOR_PRO_VERSION', 'ACF_VERSION') as $key) {
    echo $key . '=' . (defined($key) ? constant($key) : 'absent') . "\n";
}
foreach (get_plugins() as $file => $plugin) {
    if (strpos($file, 'remove') !== false) echo 'PLUGIN ' . $file . ' ' . $plugin['Version'] . "\n";
}
foreach (array('mega-menu', 'nested-tabs', 'template', 'loop-grid', 'posts', 'nav-menu', 'heading', 'image', 'button', 'icon', 'icon-list', 'divider', 'spacer') as $type) {
    $prototype = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type);
    if ($prototype) echo 'WIDGET ' . $type . ' ' . get_class($prototype) . ' ' . (new ReflectionClass($prototype))->getFileName() . "\n";
}
