<?php
/** Replay the reviewed Pro Classic skin's leaked excerpt filters, without render. */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/elementor-compatibility.php';

final class IU_Elementor_Fragment_Excerpt {
    public static function profile() {
        $result = array();
        foreach (array('excerpt_length', 'excerpt_more') as $hook) {
            foreach (($GLOBALS['wp_filter'][$hook]->callbacks ?? array()) as $priority => $group) {
                foreach ($group as $entry) {
                    $fn = $entry['function'];
                    $item = array('hook' => $hook, 'priority' => (int) $priority, 'args' => (int) $entry['accepted_args']);
                    if ($hook === 'excerpt_more' && $fn === 'wp_embed_excerpt_more') {
                        if ($item['args'] !== 1) return null;
                        $item['kind'] = 'native';
                    } elseif (iu_elementor_feature_supported('fragment', null, true) &&
                        is_array($fn) && is_object($fn[0]) && get_class($fn[0]) === 'ElementorPro\\Modules\\Posts\\Skins\\Skin_Classic' &&
                        $fn[1] === ($hook === 'excerpt_length' ? 'filter_excerpt_length' : 'filter_excerpt_more')) {
                        if ($item['args'] !== 1) return null;
                        $item['kind'] = 'classic';
                        if ($hook === 'excerpt_length') {
                            $item['length'] = $fn[0]->get_instance_value('excerpt_length');
                            if (!self::length_ok($item['length'])) return null;
                            $parent_property = new ReflectionProperty('Elementor\\Skin_Base', 'parent');
                            if (PHP_VERSION_ID < 80100) $parent_property->setAccessible(true);
                            $parent = $parent_property->getValue($fn[0]);
                            if (!$parent || !method_exists($parent, 'get_data') || $parent->get_name() !== 'posts') return null;
                            $item['parent'] = $parent->get_data();
                            unset($item['parent']['_iu_fragment_build'], $item['parent']['_iu_fragment_payload']);
                        }
                    } else {
                        // Keep arbitrary existing filters live. Identity describes
                        // replay order, not approval of their implementation/output.
                        $identity = self::identity($fn);
                        if ($identity === null || $item['args'] < 0) return null;
                        $item['kind'] = 'external';
                        $item['identity'] = $identity;
                    }
                    $result[] = $item;
                }
            }
        }
        return $result;
    }

    private static function identity($fn) {
        if (!is_callable($fn)) return null;
        if ($fn instanceof Closure) {
            $ref = new ReflectionFunction($fn);
            $name = $ref->getFileName() . ':' . $ref->getStartLine() . ':' . $ref->getEndLine();
        } elseif (is_array($fn)) {
            $name = (is_object($fn[0]) ? get_class($fn[0]) : $fn[0]) . '::' . $fn[1];
        } elseif (is_object($fn)) $name = get_class($fn) . '::__invoke';
        else $name = $fn;
        return hash('sha256', $name);
    }

    public static function externals($profile) {
        return array_values(array_filter((array) $profile, function ($item) { return ($item['kind'] ?? '') === 'external'; }));
    }

    private static function length_ok($value) {
        return (is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value >= 0 && (int) $value <= 1000;
    }

    public static function replay($wanted) {
        $current = self::profile();
        if (!is_array($wanted) || $current === null || self::externals($current) !== self::externals($wanted)) return false;
        $live = array();
        foreach (array('excerpt_length', 'excerpt_more') as $hook) {
            foreach (($GLOBALS['wp_filter'][$hook]->callbacks ?? array()) as $priority => $group) {
                foreach ($group as $entry) {
                    $id = self::identity($entry['function']);
                    $slot = $hook . ':' . $priority . ':' . $entry['accepted_args'] . ':' . $id;
                    $live[$slot][] = $entry['function'];
                }
            }
        }
        $skin = null;
        $parent = null;
        foreach ($wanted as $item) {
            if (!is_array($item) || !in_array($item['hook'] ?? '', array('excerpt_length', 'excerpt_more'), true) ||
                !is_int($item['priority'] ?? null) || !is_int($item['args'] ?? null) || $item['args'] < 0) return false;
            if (($item['kind'] ?? '') === 'external') {
                $slot = $item['hook'] . ':' . $item['priority'] . ':' . $item['args'] . ':' . ($item['identity'] ?? '');
                if (empty($live[$slot])) return false;
            } elseif (($item['kind'] ?? '') === 'native') {
                if ($item['args'] !== 1) return false;
                if ($item['hook'] !== 'excerpt_more' || !function_exists('wp_embed_excerpt_more')) return false;
            } elseif (($item['kind'] ?? '') === 'classic') {
                if ($item['args'] !== 1) return false;
                if (!iu_elementor_feature_supported('fragment', null, true)) return false;
                if ($item['hook'] === 'excerpt_length') {
                    if (!self::length_ok($item['length'] ?? null)) return false;
                    if (!is_array($item['parent'] ?? null) || ($item['parent']['widgetType'] ?? '') !== 'posts') return false;
                    // Construct one lightweight control parent, never render it
                    // or call get_query(). Preserve the real Widget_Base API
                    // for subsequent skin/control work and exact callback IDs.
                    $parent = \Elementor\Plugin::$instance->elements_manager->create_element_instance($item['parent']);
                    if (!$parent || $parent->get_name() !== 'posts' ||
                        $parent->get_settings('classic_excerpt_length') !== $item['length']) return false;
                }
                $widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types('posts');
                $skin = $widget ? $widget->get_skin('classic') : null;
                if (!$skin || get_class($skin) !== 'ElementorPro\\Modules\\Posts\\Skins\\Skin_Classic') return false;
            } else return false;
        }
        if (self::profile() === $wanted) return true;
        if ($skin && $parent) {
            // Reuse the actual globally registered Skin_Classic object. A new
            // constant callback would survive the next Posts widget's removal
            // of its own filters and change subsequent excerpts. set_parent()
            // uses the preserved control parent until a later real Posts
            // widget binds its own parent before render.
            $skin->set_parent($parent);
        }
        foreach (array('excerpt_length', 'excerpt_more') as $hook) remove_all_filters($hook);
        foreach ($wanted as $item) {
            if ($item['kind'] === 'external') {
                $slot = $item['hook'] . ':' . $item['priority'] . ':' . $item['args'] . ':' . $item['identity'];
                $fn = array_shift($live[$slot]);
            } else $fn = $item['kind'] === 'native' ? 'wp_embed_excerpt_more' :
                array($skin, $item['hook'] === 'excerpt_length' ? 'filter_excerpt_length' : 'filter_excerpt_more');
            add_filter($item['hook'], $fn, $item['priority'], $item['args']);
        }
        return true;
    }
}
