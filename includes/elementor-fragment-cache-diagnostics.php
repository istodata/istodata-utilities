<?php
/** Administrator-only, bounded observation and on-demand saved-element review. */
if (!defined('ABSPATH')) exit;

final class IU_Elementor_Fragment_Diagnostics {
    const OPTION = 'iu_fragment_diagnostics';
    private static $pending = array();

    public static function boot() {
        add_action('iu_elementor_fragment_result', array(__CLASS__, 'result'), 10, 2);
        add_action('shutdown', array(__CLASS__, 'flush'));
        add_action('wp_ajax_iu_fragment_diagnostics', array(__CLASS__, 'ajax'));
        add_action('elementor/editor/after_enqueue_scripts', array(__CLASS__, 'editor_script'));
    }

    public static function add_control($element) {
        if (!function_exists('current_user_can') || !current_user_can('manage_options')) return;
        $element->add_control('iu_fragment_cache_diagnostics', array(
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => '<button type="button" class="elementor-button iu-fragment-diagnostic">' .
                esc_html__('Έλεγχος κατάστασης cache', 'istodata-utilities') . '</button><p class="iu-fragment-diagnostic-result" role="status" style="overflow-wrap:anywhere;line-height:1.4;margin-top:8px"></p>',
            'render_type' => 'none',
        ));
    }

    public static function editor_script() {
        if (!current_user_can('manage_options')) return;
        wp_enqueue_script('iu-fragment-diagnostics', plugins_url('../assets/js/elementor-fragment-diagnostics.js', __FILE__),
            array('jquery', 'wp-util', 'elementor-editor'), IU_PLUGIN_VERSION, true);
        wp_localize_script('iu-fragment-diagnostics', 'iuFragmentDiagnostics', array(
            'nonce' => wp_create_nonce('iu_fragment_diagnostics'),
            'loading' => __('Έλεγχος αποθηκευμένου στοιχείου…', 'istodata-utilities'),
            'error' => __('Ο έλεγχος δεν ολοκληρώθηκε. Αποθηκεύστε το στοιχείο και δοκιμάστε ξανά.', 'istodata-utilities'),
        ));
    }

    public static function request_bypass($nodes, $document_id, $reason) {
        if (!self::can_observe()) return;
        $budget = 2500;
        $walk = function ($nodes) use (&$walk, &$budget, $document_id, $reason) {
            foreach ((array) $nodes as $node) {
                if (--$budget < 0 || !is_array($node)) return;
                if (IU_Elementor_Fragment_Atomic::opted_in($node)) self::record($document_id, $node['id'] ?? '', 'bypass', array('reason' => $reason));
                if (!empty($node['elements'])) $walk($node['elements']);
            }
        };
        $walk($nodes);
    }

    private static function can_observe() {
        return !(defined('WP_CLI') && WP_CLI) && function_exists('current_user_can') &&
            current_user_can('manage_options') && iu_elementor_fragment_enabled() && function_exists('is_admin') && !is_admin() && !(defined('REST_REQUEST') && REST_REQUEST) &&
            !(defined('DOING_AJAX') && DOING_AJAX) && !isset($_GET['elementor-preview']) &&
            !(function_exists('is_preview') && is_preview());
    }

    public static function record($document_id, $element_id, $state, $detail = array()) {
        if (!self::can_observe() || !(int) $document_id || !is_string($element_id) ||
            !preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $element_id)) return;
        $language = defined('ICL_LANGUAGE_CODE') ? (string) ICL_LANGUAGE_CODE : get_locale();
        $device = function_exists('iu_request_is_phone') && iu_request_is_phone() ? 'phone' : 'desktop/tablet';
        $key = (int) $document_id . ':' . $element_id . ':' . $language . ':' . $device;
        self::$pending[$key] = array('document_id' => (int) $document_id, 'element_id' => $element_id,
            'state' => $state, 'detail' => $detail, 'language' => $language, 'device' => $device, 'observed_at' => time());
    }

    public static function result($result, $build) {
        self::record($build['document_id'] ?? 0, $build['element_id'] ?? '',
            in_array($result, array('hit', 'stale-hit'), true) ? $result : ($result === 'stored' ? 'miss-stored' : 'bypass'),
            array('reason' => $result));
    }

    public static function flush() {
        if (!self::$pending || !self::can_observe()) return;
        $rows = get_option(self::OPTION, array());
        if (!is_array($rows)) $rows = array();
        $changed = false;
        foreach (self::$pending as $key => $row) {
            $old = $rows[$key] ?? array();
            if (($old['state'] ?? '') === $row['state'] && ($old['detail'] ?? array()) === $row['detail'] &&
                (int) ($old['observed_at'] ?? 0) > time() - 300) continue;
            $rows[$key] = $row;
            $changed = true;
        }
        if (!$changed) return;
        uasort($rows, function ($a, $b) { return (int) ($b['observed_at'] ?? 0) <=> (int) ($a['observed_at'] ?? 0); });
        update_option(self::OPTION, array_slice($rows, 0, 50, true), false);
    }

    public static function reason($detail) {
        $reason = $detail['reason'] ?? 'unsupported-element';
        $messages = array(
            'global-off' => 'Ο γενικός διακόπτης Advanced Elements Cache είναι OFF.',
            'incompatible-elementor' => 'Ο συνδυασμός Elementor / Pro δεν έχει εγκριθεί για αυτή τη δυνατότητα.',
            'native-element-cache' => 'Η ενσωματωμένη Elementor Element Cache δεν είναι απενεργοποιημένη.',
            'editor-preview' => 'Editor και preview παρακάμπτουν πάντα την cache.',
            'unsupported-request' => 'Αυτή η αίτηση δεν είναι ασφαλές κανονικό frontend context.',
            'private-cookies' => 'Cookies ιδιωτικής συνεδρίας ή προσωπικού περιεχομένου απαιτούν κανονική απόδοση.',
            'session-context' => 'Ενεργά δεδομένα συνεδρίας απαιτούν κανονική απόδοση.',
            'request-query' => 'Οι παράμετροι της αίτησης δεν επιτρέπουν κοινή cache.',
            'language-context' => 'Η γλώσσα δεν έχει προσδιοριστεί με ασφάλεια.',
            'builder-order' => 'Μεταγενέστερο builder hook δεν επιτρέπει ασφαλή αντικατάσταση.',
            'query-context' => 'Query αναζήτησης, ιδιωτικού περιεχομένου ή ελέγχου πρόσβασης απαιτεί κανονική εκτέλεση.',
            'key-context' => 'Το context ή το excerpt profile δεν επιτρέπει ασφαλές κοινό cache key.',
            'concurrent-miss' => 'Άλλη αίτηση δημιουργεί το fragment. Η αναμονή έληξε και έγινε κανονική απόδοση.',
            'replay-context' => 'Το context ή οι εξαρτήσεις άλλαξαν πριν από την επιστροφή του fragment.',
            'generation-changed' => 'Έγινε ακύρωση της cache πριν ολοκληρωθεί η αίτηση.',
            'volatile-markup' => 'Το αποτέλεσμα περιέχει ιδιωτικό ή μεταβαλλόμενο περιεχόμενο.',
        );
        if (isset($messages[$reason])) return __($messages[$reason], 'istodata-utilities');
        return sprintf(__('Η δομή ή το context δεν υποστηρίζει κοινή cache για αυτό το στοιχείο (%s).', 'istodata-utilities'), $reason);
    }

    public static function status($document_id, $element_id) {
        if (!current_user_can('manage_options') || !current_user_can('edit_post', $document_id)) return array('message' => __('Δεν επιτρέπεται.', 'istodata-utilities'));
        $data = json_decode((string) get_post_meta($document_id, '_elementor_data', true), true);
        $node = null; $budget = 2500;
        $walk = function ($nodes) use (&$walk, &$node, &$budget, $element_id) {
            foreach ((array) $nodes as $item) {
                if (--$budget < 0 || !is_array($item)) return;
                if (($item['id'] ?? '') === $element_id) { $node = $item; return; }
                if (!empty($item['elements'])) $walk($item['elements']);
                if ($node) return;
            }
        };
        $walk($data);
        if (!$node) return array('message' => __('Το στοιχείο δεν βρέθηκε στο αποθηκευμένο document. Αποθηκεύστε τις αλλαγές πρώτα.', 'istodata-utilities'));
        $configured = IU_Elementor_Fragment_Atomic::opted_in($node);
        $global = iu_elementor_fragment_enabled();
        $detail = array(); $review = 'not-checked';
        if (!$global) $detail = array('reason' => 'global-off');
        elseif (!iu_elementor_feature_supported('fragment')) $detail = array('reason' => 'incompatible-elementor');
        elseif (get_option('elementor_element_cache_ttl') !== 'disable') $detail = array('reason' => 'native-element-cache');
        elseif ($configured) {
            $atomic = IU_Elementor_Fragment_Atomic::node($node);
            $policy = $atomic ? IU_Elementor_Fragment_Atomic::inspect($node) : IU_Elementor_Fragment_Graph::inspect($node);
            $review = $policy ? 'reviewed' : 'blocked';
            if (!$policy) $detail = $atomic ? IU_Elementor_Fragment_Atomic::rejection() : IU_Elementor_Fragment_Graph::rejection();
        }
        $message = sprintf(__('Αποθηκευμένη επιλογή: %1$s. Γενικός διακόπτης: %2$s.', 'istodata-utilities'), $configured ? 'ON' : 'OFF', $global ? 'ON' : 'OFF');
        if ($review !== 'not-checked') $message .= ' ' . __('Έλεγχος αποθηκευμένης δομής και τεχνικών απαιτήσεων replay, όχι έλεγχος όλων των callbacks.', 'istodata-utilities');
        if ($detail) $message .= ' ' . self::reason($detail);
        elseif (!$configured) $message .= ' ' . __('Το στοιχείο δεν έχει ενεργοποιημένη cache.', 'istodata-utilities');
        else $message .= ' ' . __('Η αποθηκευμένη δομή υποστηρίζεται. Η καταλληλότητα για κοινή επαναχρησιμοποίηση είναι ευθύνη του διαχειριστή· δεν αποδεικνύεται frontend HIT.', 'istodata-utilities');
        if (($node['widgetType'] ?? '') === 'mega-menu' || ($node['widgetType'] ?? '') === 'nav-menu') {
            $message .= ' ' . __('Προσοχή: η πλοήγηση μπορεί να αλλάζει ανά τρέχουσα σελίδα. Προτιμήστε ανεξάρτητα dropdown περιεχόμενα.', 'istodata-utilities');
        }
        $observed = array();
        foreach ((array) get_option(self::OPTION, array()) as $row) {
            if (!is_array($row) || ($row['document_id'] ?? 0) !== (int) $document_id || ($row['element_id'] ?? '') !== $element_id) continue;
            $observed[] = $row;
            $message .= ' ' . sprintf(__('Τελευταία παρατήρηση διαχειριστή: %1$s, %2$s / %3$s, %4$s.', 'istodata-utilities'),
                $row['state'], $row['language'], $row['device'], wp_date('Y-m-d H:i:s', $row['observed_at']));
            if ($row['state'] === 'bypass') $message .= ' ' . self::reason($row['detail']);
            if (count($observed) >= 4) break;
        }
        if (!$observed) $message .= ' ' . __('Δεν υπάρχει ακόμη καταγεγραμμένη frontend αίτηση διαχειριστή. Ανώνυμες αιτήσεις δεν καταγράφονται.', 'istodata-utilities');
        return array('configured' => $configured, 'global' => $global, 'static_review' => $review, 'detail' => $detail, 'observed' => $observed, 'message' => $message);
    }

    public static function ajax() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => __('Δεν επιτρέπεται.', 'istodata-utilities')), 403);
        check_ajax_referer('iu_fragment_diagnostics', 'nonce');
        $document_id = absint($_POST['document_id'] ?? 0);
        if (!$document_id || !current_user_can('edit_post', $document_id)) wp_send_json_error(array('message' => __('Δεν επιτρέπεται.', 'istodata-utilities')), 403);
        wp_send_json_success(self::status($document_id, sanitize_key(wp_unslash($_POST['element_id'] ?? ''))));
    }

    public static function admin_panel() {
        echo '<h2>' . esc_html__('Κατάσταση επιλεγμένου στοιχείου', 'istodata-utilities') . '</h2><form method="get">';
        echo '<input type="hidden" name="page" value="iu-elementor-fragments">';
        wp_nonce_field('iu_fragment_diagnostics', 'iu_status_nonce');
        echo '<label>Document ID <input type="number" min="1" name="document_id" required></label> ';
        echo '<label>Element ID <input type="text" name="element_id" required maxlength="64"></label> ';
        submit_button(__('Έλεγχος κατάστασης', 'istodata-utilities'), 'secondary', 'submit', false);
        echo '</form>';
        if (!empty($_GET['document_id']) && !empty($_GET['element_id']) && isset($_GET['iu_status_nonce']) &&
            is_string($_GET['iu_status_nonce']) && wp_verify_nonce(wp_unslash($_GET['iu_status_nonce']), 'iu_fragment_diagnostics')) {
            $report = self::status(absint($_GET['document_id']), sanitize_key(wp_unslash($_GET['element_id'])));
            echo '<p role="status">' . esc_html($report['message']) . '</p>';
        }
        echo '<p>' . esc_html__('Ο έλεγχος αφορά το αποθηκευμένο στοιχείο. Δεν εκτελεί widgets ή loop queries. Οι παρατηρήσεις είναι ιστορικές, όχι εγγύηση για την επόμενη αίτηση.', 'istodata-utilities') . '</p>';
    }
}
IU_Elementor_Fragment_Diagnostics::boot();
