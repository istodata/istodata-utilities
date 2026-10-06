<?php
/** Native loop CSS state must survive cached/uncached sibling combinations. */
namespace ElementorPro\Modules\LoopBuilder\Files\Css {
    class Loop {
        private static $printed_with_css = array();
        private $id;
        public static $calls = 0;
        public static function create($id) { $loop = new self(); $loop->id = $id; return $loop; }
        public static function reset() { self::$printed_with_css = array(); self::$calls = 0; }
        public function print_all_css($id) {
            ++self::$calls;
            $handle = 'loop-' . $this->id;
            if (isset(self::$printed_with_css[$handle])) return;
            echo '<style id="' . $handle . '">.test{color:red}</style>';
            self::$printed_with_css[$handle] = true;
        }
    }
}
namespace {
    define('ABSPATH', __DIR__);
    function add_action() {} function add_filter() {} function did_action() { return 0; }
    require dirname(__DIR__) . '/includes/elementor-fragment-cache.php';
    function invoke($name, ...$args) { return (new \ReflectionMethod('IU_Elementor_Fragment_Cache', $name))->invoke(null, ...$args); }
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    $loop = 'ElementorPro\\Modules\\LoopBuilder\\Files\\Css\\Loop';
    $style = '<style id="loop-1635">.test{color:red}</style>';
    $entry = array('html' => '<nav>' . $style . 'Cached menu</nav>', 'loop_css' => array('before' => array(), 'printed' => array('loop-1635')));
    $loop::reset();
    check(invoke('loop_css_ready', $entry), 'Cold CSS state rejected');
    $html = invoke('loop_css_html', $entry);
    ob_start(); $loop::create(1635)->print_all_css(1635); $later = ob_get_clean();
    check($html === $entry['html'] && $later === '', 'Cached first, native later duplicated/lost CSS');
    check(invoke('loop_css_html', $entry) === '<nav>Cached menu</nav>', 'Repeated cache hit duplicated CSS');
    $loop::reset();
    ob_start(); $loop::create(1635)->print_all_css(1635); $native = ob_get_clean();
    check($native === $style && invoke('loop_css_html', $entry) === '<nav>Cached menu</nav>', 'Native first, cached later duplicated CSS');
    $needs_prior = array('html' => '<nav>Cached menu</nav>', 'loop_css' => array('before' => array('loop-1635'), 'printed' => array()));
    $loop::reset();
    check(!invoke('loop_css_ready', $needs_prior), 'Missing prior native CSS did not bypass');
    ob_start(); $loop::create(1635)->print_all_css(1635); ob_end_clean();
    check(invoke('loop_css_ready', $needs_prior), 'Verified prior native CSS rejected');
    check(invoke('loop_css_manifest', array(), array('loop-1635'), $style) === $entry['loop_css'], 'Capture ledger mismatch');
    foreach (array(
        array(array(), array('loop-1635'), ''),
        array(array(), array('loop-1635'), $style . $style),
        array(array('loop-1635'), array(), ''),
        array(null, array(), ''),
    ) as $case) check(invoke('loop_css_manifest', ...$case) === null, 'Opaque/missing/duplicate CSS state accepted');
    check(invoke('loop_css_html', array('html' => '<style id="loop-dynamic-9">Dynamic</style>')) === '<style id="loop-dynamic-9">Dynamic</style>', 'Unrelated dynamic CSS altered');
    echo "PASS native/cached/repeated loop CSS ordering, prior-state fallback, opaque-state rejection and dynamic CSS preservation\n";
}
