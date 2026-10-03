<?php
/** Supported Elementor structures and explicit private/replay exclusions. No site, document or element IDs. */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/elementor-compatibility.php';

final class IU_Elementor_Fragment_Graph {
    private $stack = array();
    private $seen = array();
    private $signature = array();
    private $styles = array();
    private $scripts = array();
    private $budget = 2500;
    private $failure = '';
    private static $last_rejection = array();

    public static function rejection() { return self::$last_rejection; }
    public static function clear_rejection() { self::$last_rejection = array(); }

    public static function prototype_supported($type, $prototype) {
        // Registration and native APIs are the contract, not vendor source paths.
        return iu_elementor_feature_supported('fragment') && is_string($type) && $type !== 'iu-fragment-proxy' &&
            $prototype instanceof \Elementor\Widget_Base && $prototype->get_name() === $type;
    }

    public static function inspect($node) {
        self::$last_rejection = array();
        if (($node['elType'] ?? '') !== 'widget' || empty($node['widgetType']) ||
            !iu_elementor_feature_supported('fragment') ||
            !class_exists('Elementor\\Plugin') || !\Elementor\Plugin::$instance) {
            self::$last_rejection = array('reason' => 'widget-root-or-elementor-context');
            return null;
        }
        $policy = new self();
        if (!$policy->walk(array($node), false)) {
            self::$last_rejection['reason'] = $policy->failure ?: 'unsupported-graph';
            do_action('iu_elementor_fragment_graph_rejected', $policy->failure ?: 'unsupported-graph', $node);
            return null;
        }
        return array('signature' => hash('sha256', wp_json_encode($policy->signature)),
            'dependencies' => array('styles' => array_keys($policy->styles), 'scripts' => array_keys($policy->scripts)),
            'independent_query' => true);
    }

    private function fail($reason) { $this->failure = $reason; return false; }

    private function walk($nodes, $loop) {
        foreach ($nodes as $node) {
            if (!is_array($node) || --$this->budget < 0) return $this->fail('graph-budget');
            $type = $node['widgetType'] ?? $node['elType'] ?? '';
            $settings = $node['settings'] ?? array();
            // A hit skips nested document collection. Interactions are outside
            // this fragment policy and must retain the native render pipeline.
            if (!empty($node['interactions'])) return $this->fail('atomic-interactions');
            if (!$this->settings_safe($settings, $loop, $type)) return false;
            if (in_array($type, array('loop-grid', 'posts'), true) && !$this->query_safe($settings, $type)) return false;
            if (!in_array($type, array('container', 'section', 'column'), true)) {
                $prototype = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type);
                if (!$prototype || !self::prototype_supported($type, $prototype)) return $this->fail('widget-registration:' . $type);
                try {
                    $styles = $prototype->get_style_depends();
                    $scripts = $prototype->get_script_depends();
                } catch (\Throwable $error) { return $this->fail('dependency-manifest:' . $type); }
                if (!is_array($styles) || !is_array($scripts)) return $this->fail('dependency-manifest:' . $type);
                foreach (array_merge($styles, $scripts) as $handle) {
                    if (!is_string($handle) || !preg_match('/^[a-zA-Z0-9_-]+$/', $handle)) return $this->fail('dependency-manifest:' . $type);
                }
                foreach ($styles as $handle) $this->styles[$handle] = true;
                foreach ($scripts as $handle) $this->scripts[$handle] = true;
                $this->signature['widget:' . $type] = array(get_class($prototype), $styles, $scripts);
            }
            if (in_array($type, array('template', 'loop-grid'), true)) {
                $id = absint($settings['template_id'] ?? 0);
                if (!$id) return $this->fail('missing-template');
                $mode = $type === 'loop-grid';
                $identity = $id . ':' . (int) $mode;
                if (isset($this->stack[$identity])) return $this->fail('template-cycle');
                if (!isset($this->seen[$identity])) {
                    $raw = get_post_meta($id, '_elementor_data', true);
                    $data = is_string($raw) ? json_decode($raw, true) : null;
                    if (!is_array($data) || !$data || get_post_status($id) !== 'publish') return $this->fail('unresolved-template');
                    if (class_exists('Elementor\\Modules\\Interactions\\Cache\\Interactions_Postmeta')) {
                        $interactions = new \Elementor\Modules\Interactions\Cache\Interactions_Postmeta();
                        if ($interactions->load_content($id)) return $this->fail('cached-atomic-interactions');
                    }
                    $this->signature[$identity] = hash('sha256', $raw);
                    $this->stack[$identity] = true;
                    if (!$this->walk($data, $mode)) return false;
                    unset($this->stack[$identity]);
                    $this->seen[$identity] = true;
                }
                // Template widgets print external document CSS; loop-template CSS
                // is emitted inside the fragment. WPML-filtered render-copy nodes
                // determine the effective reference IDs for the current language.
                if ($type === 'template') $this->styles['elementor-post-' . $id] = true;
            }
            if (!empty($node['elements']) && !$this->walk($node['elements'], $loop)) return false;
        }
        return true;
    }

    private function settings_safe($settings, $loop, $type) {
        foreach ($settings as $key => $value) {
            if (preg_match('/display.condition|visibility.condition|shortcode|search_filter_query/', $key) && !empty($value)) {
                return $this->fail('conditional-setting:' . $key);
            }
            if ($key === '__dynamic__') {
                if (!array_filter((array) $value)) continue;
                if (!$loop || !in_array($type, array('image', 'heading', 'button', 'icon-list'), true)) return $this->fail('dynamic-context');
                foreach ((array) $value as $tag) {
                    if (!is_string($tag) || !preg_match('/^\[elementor-tag\s+id="[^"]+"\s+name="(post-url|post-featured-image|post-title)"\s+settings="([^"]*)"\]$/', $tag, $match) ||
                        json_decode(rawurldecode($match[2]), true) !== array()) return $this->fail('dynamic-tag');
                }
            } elseif (is_array($value) && !$this->settings_safe($value, $loop, $type)) return false;
        }
        return true;
    }

    private function query_safe($settings, $type) {
        $prefix = $type === 'posts' ? 'posts_' : 'post_query_';
        $post_type = $settings[$prefix . 'post_type'] ?? 'post';
        $object = is_string($post_type) ? get_post_type_object($post_type) : null;
        if (!$object || !$object->public || in_array($post_type, array('product', 'product_variation', 'attachment'), true)) return $this->fail('query-source');
        if (!empty($settings[$prefix . 'query_id']) || !empty($settings['pagination_type']) ||
            !empty($settings['pagination_individual']) || !empty($settings[$prefix . 'avoid_duplicates']) ||
            !empty($settings['nothing_found_message']) || !empty($settings['search_filter_query'])) return $this->fail('interactive-query');
        if (!in_array($settings[$prefix . 'orderby'] ?? 'date', array('date', 'title', 'menu_order', 'modified', 'ID'), true)) return $this->fail('query-order');
        foreach (array('include', 'exclude') as $key) {
            if (array_diff((array) ($settings[$prefix . $key] ?? array()), array('terms', 'authors', 'manual_selection'))) return $this->fail('relative-query');
        }
        foreach ($settings as $key => $value) {
            if (strpos($key, $prefix) === 0 && preg_match('/search|current|related|date|offset/', $key) && !empty($value)) return $this->fail('context-query');
        }
        foreach ((array) ($settings['alternate_templates'] ?? array()) as $alternate) {
            if (!empty($alternate['template_id'])) return $this->fail('alternate-template');
        }
        if ($type === 'posts' && !in_array($settings['_skin'] ?? 'classic', array('classic'), true)) return $this->fail('posts-skin');
        // This adapter replays Classic skin state for the hidden-excerpt path.
        // Visible excerpts require additional content/media replay support.
        if ($type === 'posts' && ($settings['classic_show_excerpt'] ?? 'yes') !== '') return $this->fail('posts-excerpt');
        if ($type === 'loop-grid' && !in_array($settings['_skin'] ?? 'post', array('post'), true)) return $this->fail('loop-skin');
        return true;
    }

}
