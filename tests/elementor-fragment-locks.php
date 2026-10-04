<?php
/** Real cache pipeline with shared-owner transitions; no copied lock algorithm. */
function wp_using_ext_object_cache() { return false; }
function wp_cache_get() { return false; }
function wp_cache_set() {}
require __DIR__ . '/elementor-fragment-query.php';
function lock_call($name, ...$args) {
    $method = new ReflectionMethod('IU_Elementor_Fragment_Cache', $name);
    return $method->invokeArgs(null, $args);
}
function lock_property($name, $value) {
    (new ReflectionProperty('IU_Elementor_Fragment_Cache', $name))->setValue(null, $value);
}
function lock_reset() {
    IU_Elementor_Fragment_Cache::cleanup_locks();
    foreach (array('owned_locks'=>array(), 'wait_spent'=>0.0, 'wait_budget'=>null, 'waiting'=>false) as $name=>$value) lock_property($name,$value);
    $GLOBALS['options'] = $GLOBALS['transients'] = array();
    $GLOBALS['db_transition'] = null; $GLOBALS['db_fault'] = false;
    $GLOBALS['owner_reads'] = 0;
}
function lock_expect($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$wpdb = new class {
    public $options = 'options'; public $last_error = '';
    public function prepare($sql,$name) { return $name; }
    public function get_var($name) {
        $this->last_error = $GLOBALS['db_fault'] ? 'fixture database failure' : '';
        if ($this->last_error) return null;
        if (strpos($name,'iu_frag_lock_')===0) {
            ++$GLOBALS['owner_reads'];
            if (is_callable($GLOBALS['db_transition'])) ($GLOBALS['db_transition'])($name,$GLOBALS['owner_reads']);
        }
        return $GLOBALS['options'][$name] ?? null;
    }
    public function delete($table,$where,$formats) {
        if (($GLOBALS['options'][$where['option_name']]??null)===$where['option_value']) unset($GLOBALS['options'][$where['option_name']]);
    }
};
$generation = array('document_id'=>1,'element_id'=>'query-fixture','site'=>'0','element'=>'0');
$entry = array('format'=>IU_Elementor_Fragment_Cache::FORMAT,'html'=>'<nav>shared</nav>',
    'styles'=>array('widget-css'),'scripts'=>array('widget-js'),'excerpt'=>array(),'excerpt_before'=>array(),
    'preserve_excerpt'=>true,'displayed'=>array(),'generation'=>$generation,'independent_query'=>true,
    'fresh_until'=>time()+60,'stale_until'=>time()+180);
function lock_wait($key,$generation) {
    $reason='';$method=new ReflectionMethod('IU_Elementor_Fragment_Cache','wait_for_entry');
    $start=microtime(true);$result=$method->invokeArgs(null,array($key,$generation,&$reason));
    return array($result,$reason,microtime(true)-$start);
}
foreach (array('published','released','replaced') as $case) {
    lock_reset();$key='transition';$name=lock_call('lock_name',$key);$options[$name]=time().':foreign';
    $GLOBALS['db_transition']=function($name,$reads) use($case,$key,$entry) {
        if($reads!==2)return;
        if($case==='replaced')$GLOBALS['options'][$name]=time().':replacement';
        else unset($GLOBALS['options'][$name]);
        if($case==='published')$GLOBALS['transients'][$key]=$entry;
    };
    list($result,$reason,$elapsed)=lock_wait($key,$generation);
    lock_expect($elapsed<0.15,'Owner transition consumed deadline');
    lock_expect($case==='published' ? $result===$entry && $reason==='published' : $result===null && $reason==='owner-'.$case,'Owner transition not observed');
}
echo "successful-and-rejected-owner-wakeup: OK\n";
lock_reset();$start=microtime(true);
for($i=0;$i<6;++$i) {
    $key='sibling-'.$i;$options[lock_call('lock_name',$key)]=time().':foreign';
    list($result,$reason)=lock_wait($key,$generation);lock_expect($result===null && $reason==='wait-budget','Missing request-wide deadline');
}
$elapsed=microtime(true)-$start;
lock_expect($elapsed>=0.95 && $elapsed<1.20,'Six siblings exceeded single one-second budget');
lock_expect($GLOBALS['owner_reads']<60,'Exhausted siblings continued polling');
echo "six-siblings-one-request-budget: OK ($elapsed seconds)\n";
lock_reset();lock_property('wait_budget',0.0);
list($result,$reason,$elapsed)=lock_wait('zero',$generation);
lock_expect($reason==='wait-budget' && $elapsed<0.01 && $GLOBALS['owner_reads']===0,'Zero budget slept or polled');
lock_reset();$ours=lock_call('lock','ours');
list($result,$reason,$elapsed)=lock_wait('ours',$generation);lock_expect($reason==='self-lock' && $elapsed<0.01,'Self wait');
list($result,$reason,$elapsed)=lock_wait('foreign',$generation);lock_expect($reason==='owned-locks' && $elapsed<0.01,'Holder waited on another lock');
lock_property('waiting',true);IU_Elementor_Fragment_Cache::cleanup_locks();
list($result,$reason,$elapsed)=lock_wait('foreign',$generation);lock_expect($reason==='owned-locks','Reentrant poll waited');
echo "zero-budget-self-and-holder-reentry: OK\n";
foreach(array('expiry','purge','database') as $case) {
    lock_reset();$key='unsafe';$options[lock_call('lock_name',$key)]=($case==='expiry'?time()-121:time()).':foreign';
    if($case==='purge')$options[IU_Elementor_Fragment_Cache::EPOCH]='new-generation';
    if($case==='database')$GLOBALS['db_fault']=true;
    list($result,$reason,$elapsed)=lock_wait($key,$generation);
    lock_expect($result===null && $elapsed<0.15,'Unsafe owner/generation waited');
    lock_expect($reason===($case==='expiry'?'lock-expired':'generation-changed'),'Unsafe state accepted');
}
echo "expiry-generation-and-shared-state-failure: OK\n";
lock_reset();$_GET=array();$_SERVER['REQUEST_METHOD']='GET';
$first=IU_Elementor_Fragment_Cache::filter(array($node),1);$build=$first[0]['_iu_fragment_build'];
$start=microtime(true);$second=IU_Elementor_Fragment_Cache::filter($first,1);
lock_expect(!isset($second[0]['_iu_fragment_build']) && microtime(true)-$start<0.05,'Repeated traversal reused writer reservation or self-waited');
IU_Elementor_Fragment_Cache::cleanup_locks();
lock_expect(!isset($options[$build['lock'][0]]),'Unrendered reservation survived cleanup');
$old=lock_call('lock','replacement');$options[$old[0]]=time().':new-owner';
IU_Elementor_Fragment_Cache::cleanup_locks();lock_expect(isset($options[$old[0]]),'Cleanup removed a newer owner');
IU_Elementor_Fragment_Cache::cleanup_locks();lock_expect(isset($options[$old[0]]),'Repeated cleanup removed a newer owner');
echo "unrendered-reservation-and-token-safe-cleanup: OK\n";
lock_reset();$cold=IU_Elementor_Fragment_Cache::filter(array($node),1);$build=$cold[0]['_iu_fragment_build'];
$options[$build['lock'][0]]=time().':new-owner';
ob_start();$manager->create_element_instance($cold[0])->print_element();ob_end_clean();
lock_expect(!isset($transients[$build['key']]) && isset($options[$build['lock'][0]]),'Old writer published or removed replacement');
echo "old-writer-replacement-publication-fence: OK\n";
lock_reset();$cold=IU_Elementor_Fragment_Cache::filter(array($node),1);$key=$cold[0]['_iu_fragment_build']['key'];
IU_Elementor_Fragment_Cache::cleanup_locks();$options[lock_call('lock_name',$key)]=time().':foreign';
$entry['fresh_until']=time()-1;$transients[$key]=$entry;$before=$renders;
$stale=IU_Elementor_Fragment_Cache::filter(array($node),1);
ob_start();$manager->create_element_instance($stale[0])->print_element();$html=ob_get_clean();
lock_expect($html===$entry['html'] && $renders===$before,'Replay-safe stale executed original');
$options[IU_Elementor_Fragment_Cache::EPOCH]='purged';
ob_start();$manager->create_element_instance($stale[0])->print_element();$html=ob_get_clean();
lock_expect($html!==$entry['html'] && $renders===$before+1,'Generation-invalid stale was replayed');
echo "safe-stale-early-bypass-and-invalid-stale-native-fallback: OK\n";
