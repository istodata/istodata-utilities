<?php
/** Root dependencies already queued before capture must survive the cache entry. */
define('ABSPATH', __DIR__);
function add_action() {}
function add_filter() {}
function did_action() { return 0; }
function apply_filters($hook, $value) { return $value; }
function do_action() {}
function get_the_ID() { return 7; }
function get_option($key) { return $key === 'istodata_utilities_settings' ? array('optimizations' => array('elementor_fragment_cache' => true)) : false; }
function add_option() { return true; }
function wp_cache_delete() {}
function set_transient($key, $entry, $ttl) { $GLOBALS['entry'] = $entry; return true; }
class IU_Test_Queue {
    public $registered = array('widget-heading' => true, 'root-script' => true);
    public $queue;
    public function __construct($handle) { $this->queue = array($handle); }
    public function get_data() { return false; }
}
$styles = new IU_Test_Queue('widget-heading');
$scripts = new IU_Test_Queue('root-script');
function wp_styles() { return $GLOBALS['styles']; }
function wp_scripts() { return $GLOBALS['scripts']; }
$wpdb = new class {
    public $options = 'options';
    public function prepare($sql, $name) { return $name; }
    public function get_var($name) { return $name === 'test-lock' ? $GLOBALS['lock_owner'] : ($GLOBALS['test_epoch'] ?? null); }
    public function delete($table, $where, $formats) {
        if ($where !== array('option_name' => 'test-lock', 'option_value' => $GLOBALS['lock_token'])) {
            throw new RuntimeException('Lock release omitted ownership');
        }
    }
};
$include = getenv('IU_FRAGMENT_INCLUDE') ?: dirname(__DIR__) . '/includes/elementor-fragment-cache.php';
require $include;
$widget = new class {
    public function get_id() { return 'heading'; }
    public function get_style_depends() { return array('widget-heading'); }
    public function get_script_depends() { return array('root-script'); }
    public function get_data($key) {
        return array('key' => 'entry', 'lock' => array('test-lock', $GLOBALS['lock_token']), 'prior' => array(),
            'dependencies' => $GLOBALS['test_dependencies'] ?? array(),
            'generation' => array('document_id' => 1, 'element_id' => 'heading', 'site' => '0', 'element' => '0'),
            'post_context' => 7, 'excerpt_prior' => array(), 'ttl' => 86400, 'document_id' => 1, 'element_id' => 'heading');
    }
};
$GLOBALS['lock_token'] = time() . ':owner';
$GLOBALS['lock_owner'] = $GLOBALS['lock_token'];
ob_start();
IU_Elementor_Fragment_Cache::begin($widget);
echo '<h2>Public heading</h2>';
IU_Elementor_Fragment_Cache::finish($widget);
$html = ob_get_clean();
if ($html !== '<h2>Public heading</h2>' || $GLOBALS['entry']['styles'] !== array('widget-heading') ||
    $GLOBALS['entry']['scripts'] !== array('root-script')) {
    throw new RuntimeException('Already-queued root dependency was lost');
}
echo "already-queued-root-assets: OK\n";
$GLOBALS['test_dependencies'] = array('styles' => array('widget-nested-tabs', 'widget-posts'),
    'scripts' => array('child-script'));
$styles->registered += array('widget-nested-tabs' => true, 'widget-posts' => true, 'unrelated' => true);
$scripts->registered += array('child-script' => true, 'unrelated-script' => true);
$styles->queue = array('unrelated', 'widget-heading');
$scripts->queue = array('unrelated-script', 'root-script');
ob_start();
IU_Elementor_Fragment_Cache::begin($widget);
$styles->queue = array_merge($styles->queue, array('elementor-post-7767', 'widget-nested-tabs', 'elementor-post-7778', 'widget-posts'));
$scripts->queue[] = 'child-script';
echo '<nav>Public menu</nav>';
IU_Elementor_Fragment_Cache::finish($widget);
ob_end_clean();
if ($GLOBALS['entry']['styles'] !== array('widget-heading', 'elementor-post-7767', 'widget-nested-tabs', 'elementor-post-7778', 'widget-posts') ||
    $GLOBALS['entry']['scripts'] !== array('root-script', 'child-script')) {
    throw new RuntimeException('Native enqueue order or unrelated-page exclusion lost');
}
$old = $GLOBALS['entry']; $old['format'] = 15;
$valid = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'valid_entry');
if ($valid->invoke(null, $old)) throw new RuntimeException('Old unordered manifest accepted');
unset($GLOBALS['test_dependencies']);
$styles->queue = array('widget-heading'); $scripts->queue = array('root-script');
echo "native-template-widget-order-and-old-manifest-rejection: OK\n";
unset($GLOBALS['entry']);
$GLOBALS['lock_owner'] = time() . ':new-owner';
ob_start();
IU_Elementor_Fragment_Cache::begin($widget);
echo '<h2>Public heading</h2>';
IU_Elementor_Fragment_Cache::finish($widget);
ob_end_clean();
if (isset($GLOBALS['entry'])) throw new RuntimeException('Old owner published over replacement lock');
echo "replacement-lock-owner: OK\n";
$GLOBALS['lock_owner'] = $GLOBALS['lock_token'];
$GLOBALS['test_epoch'] = 'purged-while-building';
ob_start(); IU_Elementor_Fragment_Cache::begin($widget); echo '<h2>Old build</h2>'; IU_Elementor_Fragment_Cache::finish($widget); ob_end_clean();
if (isset($GLOBALS['entry'])) throw new RuntimeException('Old writer published after site/element purge');
echo "purge-during-first-capture: OK\n";
unset($GLOBALS['test_epoch']);
$markup = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'shared_markup_safe');
foreach (array('<input name="_wpnonce" value="secret">', '<a href="/wp-admin/post.php">Edit</a>',
    '<div data-user-id="23">Profile</div>', '<div data-session="secret"></div>',
    '<div class="elementor-edit-area"></div>') as $html) {
    if ($markup->invoke(null,$html)) throw new RuntimeException('Private/admin markup allowed');
}
if (!$markup->invoke(null,'<h2>Public content</h2>')) throw new RuntimeException('Public markup rejected');
echo "shared-markup-privacy: OK\n";

// A menu after a Posts sibling can have a different render-slot input than its
// builder-time input, while making no excerpt changes itself.
$GLOBALS['wp_filter']['excerpt_more'] = (object) array('callbacks' => array(20 => array(array('function' => function ($s) { return $s; }, 'accepted_args' => 1))));
unset($GLOBALS['entry']);
ob_start(); IU_Elementor_Fragment_Cache::begin($widget); echo '<nav>Public menu</nav>'; IU_Elementor_Fragment_Cache::finish($widget); ob_end_clean();
if (empty($GLOBALS['entry']['preserve_excerpt']) || !$GLOBALS['entry']['excerpt_before']) throw new RuntimeException('No-effect render-slot excerpt context rejected');
unset($GLOBALS['entry']);
ob_start(); IU_Elementor_Fragment_Cache::begin($widget); IU_Elementor_Fragment_Cache::finish($widget); ob_end_clean();
if (!isset($GLOBALS['entry']) || $GLOBALS['entry']['html'] !== '') throw new RuntimeException('Native empty output rejected');
unset($GLOBALS['entry']);
ob_start(); IU_Elementor_Fragment_Cache::begin($widget); echo '<nav>Unsafe late handler</nav>';
$GLOBALS['wp_filter']['wp_footer'] = (object) array('callbacks' => array(10 => array('new' => array('function' => function () {}, 'accepted_args' => 0))));
IU_Elementor_Fragment_Cache::finish($widget); ob_end_clean();
if (isset($GLOBALS['entry'])) throw new RuntimeException('Unreplayed footer registration stored');
unset($GLOBALS['wp_filter']['wp_footer']);
unset($GLOBALS['entry']);
ob_start(); IU_Elementor_Fragment_Cache::begin($widget); echo '<nav>Unsafe script dequeue</nav>';
$GLOBALS['scripts']->queue = array();
IU_Elementor_Fragment_Cache::finish($widget); ob_end_clean();
if (isset($GLOBALS['entry'])) throw new RuntimeException('Unreplayed dependency dequeue stored');
$GLOBALS['scripts']->queue = array('root-script');
unset($GLOBALS['entry']);
ob_start(); IU_Elementor_Fragment_Cache::begin($widget); echo '<nav>Unsafe late script</nav>';
$GLOBALS['scripts']->registered['late-script'] = true; $GLOBALS['scripts']->queue[] = 'late-script';
IU_Elementor_Fragment_Cache::finish($widget); ob_end_clean();
if (isset($GLOBALS['entry'])) throw new RuntimeException('Late asset registration stored');
echo "render-slot-excerpt-and-opaque-side-effect-guards: OK\n";
