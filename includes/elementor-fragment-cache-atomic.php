<?php
/** Atomic settings/policy adapter. Rendering and locking remain in the shared engine. */
if (!defined('ABSPATH')) exit;

final class IU_Elementor_Fragment_Atomic {
    const EPOCH = 'iu_elementor_atomic_fragment_epoch';
    private static $rejection = array();

    public static function node($node) {
        $type = $node['widgetType'] ?? $node['elType'] ?? '';
        if (!is_string($type) || !class_exists('Elementor\\Plugin') || !\Elementor\Plugin::$instance ||
            !class_exists('Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Atomic_Widget_Base')) return false;
        $plugin = \Elementor\Plugin::$instance;
        if (($node['elType'] ?? '') === 'widget' && isset($plugin->widgets_manager) && method_exists($plugin->widgets_manager,'get_widget_types')) {
            return $plugin->widgets_manager->get_widget_types($type) instanceof \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
        }
        return isset($plugin->elements_manager) && method_exists($plugin->elements_manager,'get_element_types') &&
            ($plugin->elements_manager->get_element_types()[$type] ?? null) instanceof \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
    }

    public static function value($settings, $key, $default = null) {
        $value = $settings[$key] ?? $default;
        if (!is_array($value)) return $value;
        if (!empty($value['disabled']) || !isset($value['$$type']) || !array_key_exists('value', $value)) return $default;
        return $value['value'];
    }

    public static function opted_in($node) {
        $settings = $node['settings'] ?? array();
        if (!self::node($node)) return ($settings['iu_fragment_cache'] ?? '') === 'yes';
        $value = $settings['iu_fragment_cache'] ?? null;
        return is_array($value) && ($value['$$type'] ?? '') === 'boolean' &&
            empty($value['disabled']) && ($value['value'] ?? null) === true;
    }

    public static function schema($schema) {
        if (!iu_elementor_feature_supported('fragment_atomic')) return $schema;
        $boolean = '\Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type';
        $string = '\Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type';
        if (!class_exists($boolean) || !class_exists($string)) return $schema;
        // Schema stays present with the global switch OFF, preserving saved values.
        $schema['iu_fragment_cache'] = $boolean::make()->default(false)->meta(array('dynamic',false));
        $schema['iu_fragment_cache_ttl'] = $string::make()->enum(array('3600','21600','86400','604800'))->default('604800')->meta(array('dynamic',false));
        if (function_exists('iu_elementor_should_render_by_device')) {
            $schema['iu_hide_on_phone'] = $boolean::make()->default(false);
            $schema['iu_hide_on_desktop_tablet'] = $boolean::make()->default(false);
        }
        return $schema;
    }

    public static function controls($controls, $element) {
        if (!iu_elementor_feature_supported('fragment_atomic')) return $controls;
        $section = '\Elementor\Modules\AtomicWidgets\Controls\Section';
        $switch = '\Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control';
        $select = '\Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control';
        if (!class_exists($section) || !class_exists($switch) || !class_exists($select)) return $controls;
        if (iu_elementor_fragment_enabled()) {
            $options = array();
            foreach (array('3600'=>'1 ώρα','21600'=>'6 ώρες','86400'=>'24 ώρες','604800'=>'7 ημέρες') as $value=>$label) {
                $options[] = array('value'=>(string) $value,'label'=>$label);
            }
            $controls[] = $section::make()->set_id('iu_fragment_cache_section')->set_label('Advanced Element Cache')
                ->set_description('Εφαρμόζει την ορατότητα συσκευών. Μην ενεργοποιείτε για στοιχεία που αλλάζουν ανά σελίδα, επισκέπτη ή URL. Pagination, custom queries και αβέβαιο context παρακάμπτουν την cache.')
                ->set_items(array($switch::bind_to('iu_fragment_cache')->set_label('Cache this element'),
                    $select::bind_to('iu_fragment_cache_ttl')->set_label('Διάρκεια')->set_options($options)));
        }
        if (function_exists('iu_elementor_should_render_by_device')) {
            $controls[] = $section::make()->set_id('iu_device_visibility_section')->set_label('Ορατότητα Συσκευών')
                ->set_items(array($switch::bind_to('iu_hide_on_phone')->set_label('Απόκρυψη σε Κινητά'),
                    $switch::bind_to('iu_hide_on_desktop_tablet')->set_label('Απόκρυψη σε Desktop/Tablet')));
        }
        return $controls;
    }

    public static function rejection() { return self::$rejection; }
    private static function fail($reason) { self::$rejection = array('reason'=>$reason); return null; }

    public static function inspect($node) {
        self::$rejection = array();
        if (!iu_elementor_feature_supported('fragment_atomic')) return self::fail('incompatible-atomic-fragment');
        // Opt-in declares shared output, just as for Classic widgets. Login and
        // unrelated query strings do not change this fragment's identity. The
        // shared request gate still validates auth cookies and rejects private
        // sessions/editor modes; resolver/query/markup checks reject concrete
        // user or URL dependencies before a fragment can be published/reused.
        $dependencies = array('styles'=>array(), 'scripts'=>array());
        $signature = array(); $budget = 2500;
        if (!self::walk($node, false, $dependencies, $signature, $budget)) return null;
        add_option(self::EPOCH, '0', '', false); // Lazy: only a real, eligible opt-in request.
        $signature['epoch'] = (string) get_option(self::EPOCH, '0');
        // Start with page-specific variants for Atomic elements. Unknown native
        // or vendor implementations must not inherit Classic cross-page reuse.
        $signature['page'] = array(get_queried_object_id(),get_the_ID(),
            parse_url($_SERVER['REQUEST_URI'] ?? '',PHP_URL_PATH),is_404(),is_search());
        return array('atomic'=>true,'independent_query'=>true,
            'signature'=>hash('sha256',wp_json_encode($signature)),
            'dependencies'=>array_map(function($items) { return array_values(array_unique($items)); },$dependencies));
    }

    private static function walk($node, $loop, &$dependencies, &$signature, &$budget) {
        if (!is_array($node) || --$budget < 0) return self::fail('atomic-graph-budget');
        if (!empty($node['interactions'])) return self::fail('atomic-interactions');
        $type = $node['widgetType'] ?? $node['elType'] ?? '';
        $settings = $node['settings'] ?? array();
        if (!is_array($settings)) return self::fail('atomic-settings');
        if (!self::safe_values($settings, $loop) || !self::safe_values($node['styles'] ?? array(),false)) return null;
        if ($type === 'e-collection-loop') {
            if (!self::query_safe($settings)) return null;
            $loop = true;
        }
        if (self::node($node)) {
            $plugin = \Elementor\Plugin::$instance;
            $prototype = ($node['elType'] ?? '') === 'widget' ? $plugin->widgets_manager->get_widget_types($type)
                : ($plugin->elements_manager->get_element_types()[$type] ?? null);
            if (!$prototype || !($prototype instanceof \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base ||
                $prototype instanceof \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base)) return self::fail('atomic-registration');
            // Unbound Atomic prototypes deliberately advertise conditional
            // handlers (e.g. has_action_link() returns true without an ID).
            // Capture actual instance dependencies during the miss instead.
            $signature[$type] = get_class($prototype);
        } elseif (($node['elType'] ?? '') === 'widget') {
            // Embedded documents need Atomic style-collection replay acceptance.
            if ($type === 'template') return self::fail('atomic-embedded-document');
            $policy = IU_Elementor_Fragment_Graph::inspect($node);
            if (!$policy) return self::fail('atomic-classic-descendant');
            foreach ($dependencies as $kind=>$items) $dependencies[$kind] = array_merge($items,$policy['dependencies'][$kind]);
            $signature[$node['id'] ?? $type] = $policy['signature'];
        } elseif (!in_array($type,array('container','section','column'),true)) return self::fail('atomic-registration');
        foreach ($node['elements'] ?? array() as $child) if (!self::walk($child,$loop,$dependencies,$signature,$budget)) return null;
        return true;
    }

    private static function safe_values($values, $loop) {
        foreach ($values as $key=>$value) {
            if (preg_match('/condition|shortcode|search_filter|template_id|__dynamic__/i',(string) $key) && !empty($value)) return self::fail('atomic-context-setting');
            if (is_array($value) && ($value['$$type'] ?? '') === 'dynamic') {
                $tag = $value['value'] ?? array();
                if (!is_array($tag) || !$loop || !in_array($tag['name'] ?? '',array('post-title','post-url','post-featured-image'),true) ||
                    ($tag['group'] ?? '') !== 'post' || !empty($tag['settings']) || !empty($value['disabled'])) return self::fail('atomic-dynamic-context');
            } elseif (is_array($value)) {
                // Components, action links, variables and custom resolvers are
                // not equivalent to literal HTML, even if their raw shape is small.
                if (isset($value['$$type']) && (!is_string($value['$$type']) || !in_array($value['$$type'],
                    array('string','boolean','number','url','link','escaped-html','html','classes','attributes','key-value',
                        'loop-query','query-array','query-filter-array','color','size'),true))) return self::fail('atomic-unreviewed-resolver');
                if (!self::safe_values($value,$loop)) return null;
            }
        }
        return true;
    }

    private static function query_safe($settings) {
        if (self::value($settings,'pagination',false) !== false || self::value($settings,'empty_state',false) !== false) return self::fail('atomic-interactive-query');
        $query = $settings['query'] ?? null;
        if (!is_array($query) || ($query['$$type'] ?? '') !== 'loop-query' || !is_array($query['value'] ?? null) || !empty($query['disabled'])) return self::fail('atomic-query-source');
        $query = $query['value'];
        if (self::value($query,'template_type','post') !== 'post') return self::fail('atomic-query-source');
        $source = self::value($query,'source','post');
        $object = is_string($source) ? get_post_type_object($source) : null;
        if (!$object || !$object->public || in_array($source,array('product','product_variation','attachment'),true)) return self::fail('atomic-query-source');
        if (self::value($query,'query_id','') !== '' || self::value($query,'select_date','anytime') !== 'anytime') return self::fail('atomic-context-query');
        if (!in_array(self::value($query,'orderby','post_date'),array('post_date','post_title','menu_order','modified'),true)) return self::fail('atomic-query-order');
        foreach ($query as $key=>$value) {
            // Native Save Draft materializes absent, inactive query fields as
            // null (dates, selection and taxonomy options). No resolver runs.
            if ($value === null) continue;
            if (in_array($key,array('template_type','source','posts_per_page','query_id','orderby','order','ignore_sticky_posts','select_date'),true)) continue;
            if (in_array($key,array('selection','include_filters','exclude_filters'),true) && self::value($query,$key) === array()) continue;
            return self::fail('atomic-context-query');
        }
        return true;
    }

    public static function invalidate() {
        if (get_option(self::EPOCH,false) !== false) update_option(self::EPOCH,wp_generate_uuid4(),false);
    }
}

add_filter('elementor/atomic-widgets/props-schema',array('IU_Elementor_Fragment_Atomic','schema'),5);
add_filter('elementor/atomic-widgets/controls',array('IU_Elementor_Fragment_Atomic','controls'),40,2);
foreach (array('save_post','deleted_post','created_term','edited_term','delete_term','set_object_terms') as $hook) {
    add_action($hook,array('IU_Elementor_Fragment_Atomic','invalidate'),10,0);
}
foreach (array('added_post_meta','updated_post_meta','deleted_post_meta') as $hook) {
    add_action($hook,static function($meta_id,$object_id,$key) {
        if ($key === '_thumbnail_id') IU_Elementor_Fragment_Atomic::invalidate();
    },10,3);
}
