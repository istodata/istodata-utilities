<?php
/** WooCommerce regression checks using the real fragment capture and replay pipeline. */
define('ELEMENTOR_VERSION', '4.3.3');
define('ELEMENTOR_PRO_VERSION', '4.3.1');
require __DIR__ . '/elementor-fragment-query.php';

define('COOKIEHASH', 'fixture');
define('LOGGED_IN_COOKIE', 'wordpress_logged_in_fixture');
// Keep the shared query fixture's baseline runs independent of WooCommerce.
if (!function_exists('WC')) {
    function WC() { return $GLOBALS['woocommerce']; }
    function is_cart() { return ($GLOBALS['woo_page'] ?? '') === 'cart'; }
    function is_checkout() { return ($GLOBALS['woo_page'] ?? '') === 'checkout'; }
    function is_account_page() { return ($GLOBALS['woo_page'] ?? '') === 'account'; }
    function is_wc_endpoint_url($endpoint = '') {
        $page = $GLOBALS['woo_page'] ?? '';
        return $endpoint === '' ? in_array($page, array('endpoint', 'customer-logout'), true) : $page === $endpoint;
    }
}
function woo_expect($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}

$woocommerce = (object) array(
    'cart' => new class {
        public $items = array();
        public function get_cart_contents() { return $this->items; }
    },
    'session' => new class {
        public $active = false;
        public $cart = array();
        public function has_session() { return $this->active || is_user_logged_in(); }
        public function get($key) { return $key === 'cart' ? $this->cart : null; }
    },
);
$_SERVER['HTTP_USER_AGENT'] = 'Desktop Browser';
$options = $transients = array();
$session_cookie = 'wp_woocommerce_session_' . COOKIEHASH;

function expect_native_without_cache_changes($query = array(), $path = '/') {
    global $renders, $transients, $options;
    $before = array($renders, $transients, $options);
    list(, $rendered) = request_fragment($query, $path);
    woo_expect($rendered['widgetType'] !== 'iu-fragment-proxy' && !isset($rendered['_iu_fragment_build']),
        'Private or read-only miss did not preserve the native widget');
    woo_expect(array($renders - 1, $transients, $options) === $before, 'Native path changed cache or locks');
}

$_COOKIE = array($session_cookie => 'guest-cart-A');
expect_native_without_cache_changes();
$_COOKIE = array(LOGGED_IN_COOKIE => 'valid-wordpress-cookie', 'woocommerce_items_in_cart' => '1');
$user_id = 42;
expect_native_without_cache_changes();

$_COOKIE = array(LOGGED_IN_COOKIE => 'valid-wordpress-cookie');
list(, $auth_miss) = request_fragment(array());
$key = array_key_first($transients);
woo_expect(isset($auth_miss['_iu_fragment_build']) && empty($transients[$key]['woocommerce_guest_source']),
    'Normal logged-in request lost its existing write path or became a guest source');
$_COOKIE['woocommerce_cart_hash'] = 'customer-cart';
expect_native_without_cache_changes();

$user_id = 0;
$_COOKIE = array();
list($guest_html, $guest_miss) = request_fragment(array());
woo_expect(isset($guest_miss['_iu_fragment_build']) && $transients[$key]['woocommerce_guest_source'] === true &&
    count($transients) === 1, 'Clean guest did not refresh the existing entry under the same key');

foreach (array(
    array(0, array($session_cookie => 'guest-cart-A')),
    array(0, array('woocommerce_cart_hash' => 'guest-cart-B')),
    array(42, array(LOGGED_IN_COOKIE => 'valid-wordpress-cookie', $session_cookie => 'customer-cart')),
    array(42, array(LOGGED_IN_COOKIE => 'valid-wordpress-cookie')),
) as list($user_id, $_COOKIE)) {
    $before = array($renders, $transients, $options);
    list($html, $hit) = request_fragment(array('utm_source' => 'store'));
    woo_expect($hit['widgetType'] === 'iu-fragment-proxy' && $html === $guest_html && end($events) === 'hit' &&
        array($renders, $transients, $options) === $before, 'Customer did not reuse the fresh guest fragment');
}

// WooCommerce can already have an active cart before a cookie reaches the browser.
$woocommerce->session->cart = array('saved-item');
$_COOKIE = array(LOGGED_IN_COOKIE => 'valid-wordpress-cookie');
$before = $renders;
list(, $cookie_less_hit) = request_fragment(array());
woo_expect($cookie_less_hit['widgetType'] === 'iu-fragment-proxy' && $renders === $before,
    'A restored cart did not reuse the fresh guest entry');
$_SERVER['HTTP_USER_AGENT'] = 'iPhone Mobile';
expect_native_without_cache_changes();
$woocommerce->session->cart = array();
$_SERVER['HTTP_USER_AGENT'] = 'Desktop Browser';

$user_id = 0;
$_COOKIE = array($session_cookie => 'guest-cart-A', 'wp-wpml_current_language' => 'en');
expect_native_without_cache_changes();
$attribution_cookie = 'pgs=3|||cpg=https://example.test/checkout/';
$_COOKIE = array($session_cookie => 'guest-cart-A', 'sbjs_session' => $attribution_cookie);
expect_native_without_cache_changes(); // Without WooCommerce's feature API, keep the default cookie guard.
final class Woo_Attribution_Features {
    public static function feature_is_enabled($id) {
        return $id === 'order_attribution' && !empty($GLOBALS['woo_attribution_enabled']);
    }
}
class_alias(Woo_Attribution_Features::class, 'Automattic\WooCommerce\Utilities\FeaturesUtil');
expect_native_without_cache_changes(); // Feature disabled.
$woo_attribution_enabled = true;
$public_widget = $node;
$checkout_widget = array_replace($node, array('id' => 'checkout-form', 'widgetType' => 'woocommerce-checkout-page'));
$mini_cart_widget = array_replace($node, array('id' => 'mini-cart', 'widgetType' => 'woocommerce-menu-cart'));
foreach (array('cart' => '/cart/', 'checkout' => '/checkout/', 'account' => '/my-account/',
    'endpoint' => '/my-account/orders/') as $woo_page => $path) {
    foreach (array(array(), array($session_cookie => 'guest-cart-A', 'sbjs_session' => $attribution_cookie),
        array(LOGGED_IN_COOKIE => 'valid-wordpress-cookie', $session_cookie => 'customer-cart',
            'sbjs_session' => $attribution_cookie)) as $_COOKIE) {
        $user_id = isset($_COOKIE[LOGGED_IN_COOKIE]) ? 42 : 0;
        $before = array($renders, $transients, $options, $_COOKIE);
        list($html, $hit) = request_fragment(array(), $path);
        woo_expect($hit['widgetType'] === 'iu-fragment-proxy' && $html === $guest_html &&
            array($renders, $transients, $options, $_COOKIE) === $before, 'Read-only hit or cookie preservation failed on ' . $path);
    }

    $_COOKIE = array();
    $user_id = 0;
    $node['id'] = 'cold-public-widget';
    expect_native_without_cache_changes(array(), $path);
    $node = $public_widget;
}

$woo_page = 'checkout';
$_COOKIE = array($session_cookie => 'guest-cart-A', 'sbjs_session' => $attribution_cookie);
$filtered = IU_Elementor_Fragment_Cache::filter(array($public_widget, $checkout_widget, $mini_cart_widget), 30);
woo_expect($filtered[0]['widgetType'] === 'iu-fragment-proxy' && $filtered[1] === $checkout_widget &&
    $filtered[2] === $mini_cart_widget, 'Attribution allowed caching private widgets alongside the shared header');

foreach (array('<div class="wp-block-woocommerce-checkout">Personalized checkout</div>',
    '<wc-order-attribution-inputs></wc-order-attribution-inputs>') as $private_html) {
    $transients[$key]['html'] = $private_html;
    expect_native_without_cache_changes(array(), '/checkout/');
}
$transients[$key]['html'] = $guest_html;

foreach (array('custom_session', 'sbjs_session_other', 'PHPSESSID') as $private_cookie) {
    $_COOKIE[$private_cookie] = 'private';
    expect_native_without_cache_changes(array(), '/checkout/');
    unset($_COOKIE[$private_cookie]);
}
$_COOKIE['sbjs_session'] = array('unexpected');
expect_native_without_cache_changes(array(), '/checkout/');
$_COOKIE['sbjs_session'] = $attribution_cookie;

$woo_page = 'customer-logout';
expect_native_without_cache_changes(array(), '/my-account/customer-logout/');
$woo_page = '';
foreach (array('add-to-cart', 'session', 'wc-ajax', 'wc-api', 'elementor-preview') as $param) {
    expect_native_without_cache_changes(array($param => '1'));
}
$_COOKIE['PHPSESSID'] = 'private';
expect_native_without_cache_changes();
$_COOKIE = array($session_cookie => 'guest-cart-A', LOGGED_IN_COOKIE => 'invalid');
expect_native_without_cache_changes();
$_COOKIE = array($session_cookie => 'guest-cart-A');
$transients[$key]['fresh_until'] = time() - 1;
expect_native_without_cache_changes();

$normal_widget = $node;
$force_eligible = true;
foreach (array('woocommerce-menu-cart', 'woocommerce-cart', 'woocommerce-checkout-page', 'woocommerce-my-account') as $type) {
    $private_widget = array_replace($node, array('id' => 'private', 'widgetType' => $type));
    foreach (array($private_widget, array_replace($node, array('elements' => array($private_widget)))) as $node) {
        expect_native_without_cache_changes();
        woo_expect((IU_Elementor_Fragment_Graph::rejection()['reason'] ?? '') === 'woocommerce-private-element',
            'Known personalized widget did not trigger the graph guard: ' . $type);
    }
    $node = $normal_widget;
}
$force_eligible = false;

$_COOKIE = $options = $transients = array();
$on_widget_render = static function () { WC()->session->active = true; };
list(, $late_session) = request_fragment(array());
woo_expect(isset($late_session['_iu_fragment_build']) && !$transients && end($events) === 'woocommerce-private-context',
    'Session created during rendering was allowed to publish');
$woocommerce->session->active = false;
$on_widget_render = static function () { echo '<div class="woocommerce-mini-cart">Private cart</div>'; };
request_fragment(array());
woo_expect(!$transients && end($events) === 'volatile-markup', 'Embedded cart markup entered the cache');
unset($on_widget_render);

echo "WooCommerce guest provenance, shared hits, read-only misses and private context guards: OK\n";

// Exercise Atomic policy and its writer-only epoch initialization through the shared pipeline.
eval('namespace Elementor\\Modules\\AtomicWidgets\\Elements\\Base; class Atomic_Widget_Base extends \\Query_Widget {}');
class Woo_Atomic_Widget extends \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base {
    public function get_name() { return 'e-heading'; }
}
function get_queried_object_id() { return 7; }

$manager = new class($manager) {
    public function __construct(private $classic) {}
    public function get_widget_types($name) {
        return $name === 'e-heading' ? new Woo_Atomic_Widget() : $this->classic->get_widget_types($name);
    }
    public function create_element_instance($data) {
        return $data['widgetType'] === 'e-heading' ? new Woo_Atomic_Widget($data) : $this->classic->create_element_instance($data);
    }
};
\Elementor\Plugin::$instance->widgets_manager = \Elementor\Plugin::$instance->elements_manager = $manager;
$node = array('id' => 'atomic-fixture', 'elType' => 'widget', 'widgetType' => 'e-heading',
    'settings' => array('iu_fragment_cache' => array('$$type' => 'boolean', 'value' => true)));
$_COOKIE = array($session_cookie => 'guest-cart-A');
expect_native_without_cache_changes();
woo_expect(!isset($options[IU_Elementor_Fragment_Atomic::EPOCH]), 'Read-only Atomic miss initialized the epoch');

$_COOKIE = array();
list($atomic_html, $atomic_miss) = request_fragment(array());
$atomic_key = array_key_first($transients);
woo_expect(isset($atomic_miss['_iu_fragment_build'], $transients[$atomic_key]['generation']['atomic']) &&
    $transients[$atomic_key]['woocommerce_guest_source'] === true, 'Atomic guest capture did not publish a shareable entry');

$user_id = 42;
$_COOKIE = array(LOGGED_IN_COOKIE => 'valid-wordpress-cookie', $session_cookie => 'customer-cart');
$before = $renders;
list($atomic_hit_html, $atomic_hit) = request_fragment(array());
woo_expect($atomic_hit['widgetType'] === 'iu-fragment-proxy' && $atomic_hit_html === $atomic_html && $renders === $before,
    'Signed-in shopper did not replay the guest Atomic entry');
$woo_page = 'checkout';
expect_native_without_cache_changes(array(), '/checkout/');
$woo_page = '';
$node['elements'] = array(array('id' => 'private', 'elType' => 'widget', 'widgetType' => 'woocommerce-menu-cart'));
expect_native_without_cache_changes();
woo_expect((IU_Elementor_Fragment_Atomic::rejection()['reason'] ?? '') === 'woocommerce-private-element',
    'Atomic parent containing the Menu Cart did not bypass');
echo "Atomic WooCommerce capture, read-only replay, epoch guard and private descendant: OK\n";
