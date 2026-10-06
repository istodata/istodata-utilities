<?php
/** Private, disposable staging prototype. Never distribute or load on production. */
if (!defined('ABSPATH')) return;
$iu_probe_config = json_decode(file_get_contents(__DIR__ . '/private.json'), true);
$iu_probe_preview = is_array($iu_probe_config) && (string)($_GET['elementor-preview']??'')===(string)$iu_probe_config['document'];
if (!is_array($iu_probe_config) ||
    !(hash_equals($iu_probe_config['key'], $_SERVER['HTTP_X_IU_ATOMIC_PROBE'] ?? '') ||
      (!empty($iu_probe_config['browser']) && hash_equals($iu_probe_config['browser'], (string) ($_GET['iu_atomic_view'] ?? ''))) || $iu_probe_preview) ||
    ($_SERVER['HTTP_HOST'] ?? '') !== 'wordpress-218158-6702910.cloudwaysapps.com') return;
if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
$GLOBALS['iu_atomic_probe'] = array('builder_filter' => 0, 'originals' => array(), 'queries' => 0, 'selected' => 'native');
$GLOBALS['iu_atomic_mode'] = $_SERVER['HTTP_X_IU_ATOMIC_MODE'] ?? $_GET['iu_atomic_mode'] ?? '';
if (!empty($iu_probe_config['engine'])) {
    // Private transport fields are not application query parameters. Actual URL
    // context test parameters remain present and exercise the production gate.
    unset($_GET['iu_atomic_view'],$_GET['iu_atomic_private'],$_GET['iu_atomic_mode']);
    if ($GLOBALS['iu_atomic_mode'] === 'baseline') add_filter('iu_elementor_fragment_eligible', '__return_false');
    add_action('iu_elementor_fragment_before_substitution',static function($node,$document,$key) {
        global $iu_probe_config;
        if ($document===$iu_probe_config['document'] && ($node['id']??'')==='iuaploop' && is_string($key)) {
            $GLOBALS['iu_atomic_probe']['fragment_key'] = $key;
            file_put_contents(__DIR__.'/keys.txt',$key."\n",FILE_APPEND|LOCK_EX);
        }
    },10,3);
    add_action('iu_elementor_fragment_result',static function($state,$build) {
        if (($build['element_id'] ?? '') === 'iuaploop') {
            $GLOBALS['iu_atomic_probe']['engine_result'] = $state;
            $GLOBALS['iu_atomic_probe']['selected'] = $state === 'stored' ? 'miss' : $state;
        }
    },10,2);
}

add_action('elementor/widgets/register', function($manager) {
    $manager->register(new class extends \Elementor\Widget_Base {
        public function get_name() { return 'iu-atomic-test-proxy'; }
        public function get_title() { return 'Private atomic probe'; }
        public function get_icon() { return 'eicon-code'; }
        public function get_categories() { return array(); }
        public function print_element() { echo $this->get_data('_iu_probe_html'); }
    });
}, PHP_INT_MAX);

if (empty($iu_probe_config['engine'])) add_filter('elementor/frontend/builder_content_data', function($data, $id) {
    global $iu_probe_config;
    if ((int) $id !== (int) $iu_probe_config['document']) return $data;
    $GLOBALS['iu_atomic_probe']['builder_filter']++;
    $GLOBALS['iu_atomic_probe']['fixture'] = hash('sha256', wp_json_encode(array($data,
        defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : get_locale(), wp_is_mobile())));
    if (($_SERVER['HTTP_X_IU_ATOMIC_MODE'] ?? $_GET['iu_atomic_mode'] ?? '') === 'baseline') return $data;
    $path = __DIR__ . '/entry.json';
    $entry = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
    if (!is_array($entry) || ($entry['fixture'] ?? '') !== $GLOBALS['iu_atomic_probe']['fixture']) {
        $GLOBALS['iu_atomic_probe']['selected'] = 'miss';
        return $data;
    }
    $GLOBALS['iu_atomic_probe']['selected'] = 'hit';
    foreach (array('styles', 'scripts') as $kind) {
        foreach ($entry[$kind] as $handle => $item) {
            $registry = $kind === 'styles' ? wp_styles() : wp_scripts();
            if (!isset($registry->registered[$handle])) {
                if ($kind === 'styles') wp_register_style($handle, $item['src'], $item['deps'], $item['ver'], $item['args']);
                else wp_register_script($handle, $item['src'], $item['deps'], $item['ver'], $item['args']);
            }
            foreach ($item['extra'] as $key => $value) $registry->add_data($handle, $key, $value);
            if ($kind === 'styles') wp_enqueue_style($handle); else wp_enqueue_script($handle);
        }
    }
    return array(array('id' => 'iuaploop', 'elType' => 'widget', 'widgetType' => 'iu-atomic-test-proxy',
        'settings' => array(), 'elements' => array(), '_iu_probe_html' => $entry['html']));
}, PHP_INT_MAX, 2);

add_action('elementor/frontend/before_render', function($element) {
    if (strpos((string) $element->get_id(), 'iuap') === 0) {
        $GLOBALS['iu_atomic_probe']['originals'][] = array('id' => $element->get_id(), 'type' => $element->get_name());
        if ($element->get_id() === 'iuaploop') {
            $GLOBALS['iu_atomic_root_rendering'] = true;
            if (!empty($GLOBALS['iu_probe_config']['engine']) && ($_SERVER['HTTP_X_IU_ATOMIC_HOLD']??'')==='test') usleep(400000);
            ob_start();
        }
    }
});
add_action('elementor/frontend/after_render', function($element) {
    if ($element->get_id() === 'iuaploop') {
        $GLOBALS['iu_atomic_root_rendering'] = false;
        $GLOBALS['iu_atomic_root_html'] = ob_get_clean();
        echo $GLOBALS['iu_atomic_root_html'];
    }
});
if (empty($iu_probe_config['engine'])) add_action('elementor/query/iu_atomic_probe_query', function() { $GLOBALS['iu_atomic_probe']['queries']++; });
else add_action('pre_get_posts',static function() {
    if (!empty($GLOBALS['iu_atomic_root_rendering'])) $GLOBALS['iu_atomic_probe']['queries']++;
},PHP_INT_MAX);

add_action('template_redirect', function() {
    global $iu_probe_config,$iu_probe_preview;
    if ($iu_probe_preview) {
        add_action('wp_footer',static function() {
            echo '<!-- IU_ATOMIC_PREVIEW '.wp_json_encode($GLOBALS['iu_atomic_probe']).' -->';
        },PHP_INT_MAX);
        return;
    }
    if ((is_user_logged_in() && empty($iu_probe_config['engine'])) || is_admin() || is_preview() || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        status_header(403); exit;
    }
    if (realpath(ABSPATH) !== '/home/218158.cloudwaysapps.com/manqbfzxjy/public_html' ||
        !iu_elementor_feature_supported('fragment') || get_option('elementor_element_cache_ttl') !== 'disable') {
        status_header(409); exit;
    }
    status_header(200); nocache_headers();
    $document = (int) $iu_probe_config['document'];
    // This isolated endpoint embeds a disposable document outside the theme's
    // singular-page enqueue path. Declare it to Elementor's native Atomic
    // style manager, just as the normal page enqueue path does before wp_head.
    do_action('elementor/post/render', $document);
    ob_start(); wp_head(); $head = ob_get_clean();
    $GLOBALS['iu_atomic_probe']['avoid_before'] = \ElementorPro\Modules\QueryControl\Module::get_avoid_list_ids();
    // Native document/frontend pipeline, including the real builder-data filter.
    $html = \Elementor\Plugin::$instance->frontend->get_builder_content($document);
    $GLOBALS['iu_atomic_probe']['avoid_after'] = \ElementorPro\Modules\QueryControl\Module::get_avoid_list_ids();
    $GLOBALS['iu_atomic_probe']['versions'] = array('core' => ELEMENTOR_VERSION, 'pro' => ELEMENTOR_PRO_VERSION);
    $GLOBALS['iu_atomic_probe']['language'] = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : get_locale();
    $GLOBALS['iu_atomic_probe']['mobile'] = wp_is_mobile();
    $GLOBALS['iu_atomic_probe']['authenticated'] = is_user_logged_in();
    $GLOBALS['iu_atomic_probe']['request'] = $_SERVER['HTTP_X_IU_ATOMIC_REQUEST'] ?? '';
    if (empty($iu_probe_config['engine']) && $GLOBALS['iu_atomic_probe']['selected'] === 'miss') {
        // Only this one-element private fixture is cached. This deliberately is
        // not a production context/key/invalidation policy or atomic UI adapter.
        $entry = array('fixture' => $GLOBALS['iu_atomic_probe']['fixture'], 'html' => $GLOBALS['iu_atomic_root_html']);
        foreach (array('styles', 'scripts') as $kind) {
            $registry = $kind === 'styles' ? wp_styles() : wp_scripts(); $entry[$kind] = array();
            foreach ($registry->queue as $handle) {
                $item = $registry->registered[$handle] ?? null;
                if ($item) $entry[$kind][$handle] = array('src' => $item->src, 'deps' => $item->deps,
                    'ver' => $item->ver, 'args' => $item->args, 'extra' => $item->extra);
            }
        }
        file_put_contents(__DIR__ . '/entry.json', wp_json_encode($entry), LOCK_EX);
    }
    header('X-IU-Atomic-Request: ' . ($_SERVER['HTTP_X_IU_ATOMIC_REQUEST'] ?? ''));
    echo '<!doctype html><html><head>' . $head . '</head><body><main>' . $html . '</main>';
    wp_footer(); echo '<!-- IU_ATOMIC_PROBE ' . wp_json_encode($GLOBALS['iu_atomic_probe']) . ' --></body></html>';
    exit;
// Elementor frontend::init runs at template_redirect priority 10. Let it
// register config/footer initialization before rendering this isolated fixture.
}, 20);
