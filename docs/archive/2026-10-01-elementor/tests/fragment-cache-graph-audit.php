<?php
/** Read-only settings/hook inventory for designing a reusable graph policy. */
$seen = array();
$types = array();
$visit = function ($nodes, $doc, $loop = false) use (&$visit, &$seen, &$types) {
    foreach ((array) $nodes as $node) {
        if (!is_array($node)) continue;
        $type = $node['widgetType'] ?? $node['elType'] ?? '?';
        $types[$type] = ($types[$type] ?? 0) + 1;
        $settings = $node['settings'] ?? array();
        if (in_array($type, array('mega-menu', 'template', 'shortcode', 'loop-grid', 'posts', 'nav-menu'), true)) {
            $out = array_filter($settings, function ($key) use ($type) {
                return in_array($type, array('template', 'shortcode'), true) ||
                    preg_match('/query|template|pagination|load|skin|layout|menu|dynamic|excerpt/', $key);
            }, ARRAY_FILTER_USE_KEY);
            echo wp_json_encode(array('doc' => $doc, 'id' => $node['id'], 'type' => $type, 'settings' => $out)) . "\n";
        }
        if (!empty($settings['__dynamic__'])) {
            echo wp_json_encode(array('doc' => $doc, 'id' => $node['id'], 'type' => $type, 'loop' => $loop,
                'dynamic' => $settings['__dynamic__'])) . "\n";
        }
        foreach (array('template_id', 'loop_template_id') as $key) {
            $id = absint($settings[$key] ?? 0);
            if ($id && !isset($seen[$id])) {
                $seen[$id] = true;
                $visit(json_decode((string) get_post_meta($id, '_elementor_data', true), true), $id, $type === 'loop-grid');
            }
        }
        if (isset($node['elements'])) $visit($node['elements'], $doc, $loop);
    }
};
$root = json_decode((string) get_post_meta(30, '_elementor_data', true), true);
$find = function ($nodes) use (&$find) {
    foreach ((array) $nodes as $node) {
        if (($node['id'] ?? '') === '26fa46c6') return $node;
        if (!empty($node['elements'])) { $found = $find($node['elements']); if ($found) return $found; }
    }
    return null;
};
$visit(array($find($root)), 30);
echo 'TYPES ' . wp_json_encode($types) . "\n";
foreach (array('elementor/widget/render_content', 'elementor/frontend/builder_content_data',
    'elementor/frontend/the_content', 'get_post_metadata', 'post_link', 'post_type_link', 'get_the_excerpt', 'elementor/frontend/widget/before_render',
    'elementor/frontend/widget/after_render', 'elementor/frontend/before_render', 'elementor/frontend/after_render',
    'elementor/query/query_args', 'pre_get_posts', 'posts_pre_query', 'posts_clauses', 'posts_results',
    'the_posts', 'pre_wp_nav_menu', 'wp_nav_menu_args', 'wp_nav_menu_objects', 'wp_nav_menu_items',
    'nav_menu_css_class', 'nav_menu_link_attributes', 'walker_nav_menu_start_el') as $hook) {
    foreach (($GLOBALS['wp_filter'][$hook]->callbacks ?? array()) as $group) {
        foreach ($group as $callback) {
            $fn = $callback['function'];
            if (is_array($fn)) $name = (is_object($fn[0]) ? get_class($fn[0]) : $fn[0]) . '::' . $fn[1];
            elseif (is_string($fn)) $name = $fn;
            else {
                $ref = new ReflectionFunction($fn);
                $name = 'closure:' . $ref->getFileName() . ':' . $ref->getStartLine();
            }
            echo 'HOOK ' . $hook . ' ' . $name . "\n";
        }
    }
}
