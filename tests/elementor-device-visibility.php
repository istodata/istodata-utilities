<?php
namespace Elementor {
    class Plugin {
        public static $instance;
        public $editor;
        public $preview;
    }
}

namespace {
    define('ABSPATH', __DIR__);
    define('IU_PLUGIN_URL', 'https://example.test/');
    define('IU_PLUGIN_VERSION', 'test');
    $GLOBALS['hooks'] = [];
    $GLOBALS['admin'] = false;

    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['hooks'][$hook][] = [$callback, $accepted_args];
    }
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        add_action($hook, $callback, $priority, $accepted_args);
    }
    function is_admin() { return $GLOBALS['admin']; }

    class Mode {
        public $active = false;
        public function is_edit_mode() { return $this->active; }
        public function is_preview_mode() { return $this->active; }
    }
    class FakeElement {
        public $data;
        public $settings_reads = 0;
        public function __construct($data) { $this->data = $data; }
        public function get_data($key) { return $this->data[$key] ?? null; }
        public function get_settings($key) {
            ++$this->settings_reads;
            return $this->data['settings'][$key] ?? null;
        }
    }

    require __DIR__ . '/../includes/elementor-device-visibility.php';
    \Elementor\Plugin::$instance = new \Elementor\Plugin();
    \Elementor\Plugin::$instance->editor = new Mode();
    \Elementor\Plugin::$instance->preview = new Mode();

    $checks = 0;
    function check($actual, $expected, $label) {
        global $checks;
        ++$checks;
        if ($actual !== $expected) {
            throw new \RuntimeException($label . ': got ' . var_export($actual, true) . ', expected ' . var_export($expected, true));
        }
    }
    function node($id, $type, $settings = [], $children = []) {
        return ['id' => $id, 'elType' => $type, 'settings' => $settings, 'elements' => $children];
    }
    function ids($nodes) {
        return array_map(function ($node) { return $node['id']; }, $nodes);
    }
    function render_ids($nodes, &$created) {
        foreach ($nodes as $node) {
            $created[] = $node['id'];
            render_ids($node['elements'] ?? [], $created);
        }
    }

    $source = [
        node('parent-phone', 'container', ['iu_hide_on_phone' => 'yes'], [
            node('expensive-loop', 'widget'),
        ]),
        node('section', 'section', [], [
            node('visible-container', 'container', [], [
                node('phone-widget', 'widget', ['iu_hide_on_phone' => 'yes']),
                node('desktop-widget', 'widget', ['iu_hide_on_desktop_tablet' => 'yes']),
                node('literal-one', 'widget', ['iu_hide_on_phone' => '1']),
            ]),
        ]),
        node('parent-desktop', 'container', ['iu_hide_on_desktop_tablet' => 'yes'], [
            node('nested-loop', 'widget'),
        ]),
    ];
    $original = $source;

    $uas = [
        'phone' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        'android-phone' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Mobile',
        'tablet' => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)',
        'android-tablet' => 'Mozilla/5.0 (Linux; Android 14; Pixel Tablet)',
        'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ];
    foreach ($uas as $device => $ua) {
        $_SERVER['HTTP_USER_AGENT'] = $ua;
        $filtered = iu_elementor_filter_builder_content_by_device($source, 30);
        $created = [];
        render_ids($filtered, $created);
        $is_phone = in_array($device, ['phone', 'android-phone'], true);
        check(in_array('expensive-loop', $created, true), !$is_phone, "$device parent child");
        check(in_array('nested-loop', $created, true), $is_phone, "$device nested child");
        check(in_array('phone-widget', $created, true), !$is_phone, "$device phone widget");
        check(in_array('desktop-widget', $created, true), $is_phone, "$device desktop widget");
        check(in_array('literal-one', $created, true), true, "$device exact yes");
        check($source, $original, "$device source unchanged");
        check($filtered[0]['_iu_device_visibility_checked'], true, "$device marker");
    }

    $_SERVER['HTTP_USER_AGENT'] = $uas['phone'];
    $rendered = iu_elementor_filter_builder_content_by_device($source, 30);
    $checked = new FakeElement($rendered[0]);
    check(iu_elementor_should_render_by_device(false, $checked), false, 'respects other should_render filters');
    check($checked->settings_reads, 0, 'filtered element does not repeat check');
    $direct = new FakeElement(node('direct', 'widget', ['iu_hide_on_phone' => 'yes']));
    check(iu_elementor_should_render_by_device(true, $direct), false, 'direct print fallback');
    check($direct->settings_reads, 2, 'fallback reads controls');
    $_SERVER['HTTP_USER_AGENT'] = $uas['tablet'];
    check(iu_elementor_should_render_by_device(true, $direct), true, 'phone-only control leaves tablet visible');
    $direct_desktop = new FakeElement(node('direct-desktop', 'container', ['iu_hide_on_desktop_tablet' => 'yes']));
    check(iu_elementor_should_render_by_device(true, $direct_desktop), false, 'direct container tablet fallback');
    $_SERVER['HTTP_USER_AGENT'] = $uas['phone'];
    check(iu_elementor_should_render_by_device(true, $direct_desktop), true, 'desktop/tablet control leaves phone visible');
    $both = [node('both', 'widget', ['iu_hide_on_phone' => 'yes', 'iu_hide_on_desktop_tablet' => 'yes'])];
    check(iu_elementor_filter_builder_content_by_device($both, 30), [], 'both controls hide on phone');
    $_SERVER['HTTP_USER_AGENT'] = $uas['desktop'];
    check(iu_elementor_filter_builder_content_by_device($both, 30), [], 'both controls hide on desktop');
    $_SERVER['HTTP_USER_AGENT'] = $uas['phone'];

    \Elementor\Plugin::$instance->editor->active = true;
    check(iu_elementor_filter_builder_content_by_device($source, 30), $source, 'editor data intact');
    check(iu_elementor_should_render_by_device(false, $direct), false, 'editor respects other filters');
    \Elementor\Plugin::$instance->editor->active = false;
    \Elementor\Plugin::$instance->preview->active = true;
    check(iu_elementor_filter_builder_content_by_device($source, 30), $source, 'preview data intact');
    check(iu_elementor_should_render_by_device(true, $direct), true, 'preview visible');
    \Elementor\Plugin::$instance->preview->active = false;
    $GLOBALS['admin'] = true;
    check(iu_elementor_filter_builder_content_by_device($source, 30), $source, 'admin template data intact');
    $GLOBALS['admin'] = false;

    // A nested template is a separate builder call and must be filtered anew.
    $outer = [node('template-widget', 'widget')];
    $inner = [node('nested-hidden', 'container', ['iu_hide_on_phone' => 'yes'], [node('nested-query', 'widget')]),
        node('nested-visible', 'widget')];
    check(ids(iu_elementor_filter_builder_content_by_device($outer, 30)), ['template-widget'], 'outer template survives');
    check(ids(iu_elementor_filter_builder_content_by_device($inner, 31)), ['nested-visible'], 'inner template filtered');
    check($inner[0]['elements'][0]['id'], 'nested-query', 'nested saved source intact');

    echo "PASS: $checks device visibility assertions\n";
}
