<?php
namespace Elementor\Modules\AtomicWidgets\Utils { class Custom_Attributes_Trust {} }
namespace Elementor { class Utils { public static function is_allowed_custom_attribute_name($name, $unfiltered=false) { return !preg_match('/^on/i', $name) && $name !== 'srcdoc'; } } }
namespace {
    define('ABSPATH', __DIR__); function add_action() {} function add_filter() {}
    require dirname(__DIR__) . '/includes/elementor-fragment-cache-atomic.php';
    $method = new \ReflectionMethod('IU_Elementor_Fragment_Atomic', 'safe_values');
    $attr = static function ($name) { return array('$$type'=>'attributes','value'=>array(array('$$type'=>'key-value','value'=>array('key'=>array('$$type'=>'string','value'=>$name),'value'=>array('$$type'=>'string','value'=>'return false'))))); };
    $check = static function($value,$expected,$label) use($method) { if (($method->invoke(null,$value,false) === true) !== $expected) throw new \RuntimeException($label); };
    $check(array('attributes'=>$attr('onmouseenter')),false,'event handler must bypass');
    $check(array('attributes'=>$attr('srcdoc')),false,'privileged attribute must bypass');
    $check(array('attributes'=>$attr('data-example')),true,'ordinary attributes remain eligible');
    $check(array('attributes'=>array('$$type'=>'attributes','value'=>array())),true,'empty attributes remain eligible');
    $check(array('attributes'=>array('$$type'=>'attributes','value'=>array(array('$$type'=>'key-value','value'=>array())))),false,'malformed names fail closed');
    $check(array('nested'=>array('attributes'=>$attr('onmouseenter'))),false,'unsafe descendant is not cacheable');
    echo "PASS Atomic privileged attribute trust eligibility and nested bypass\n";
}
