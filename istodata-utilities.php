<?php
/*
Plugin Name: ISTODATA Utilities
Description: Εργαλεία διαχείρισης, βελτιστοποιήσεις και πρόσθετες λειτουργίες από την ISTODATA.
Version: 1.8.7
Author: <a href="https://www.istodata.com/" target="_blank">ISTODATA</a>
Text Domain: istodata-utilities
*/

if (!defined('ABSPATH')) {
    exit;
}

define('IU_PLUGIN_VERSION', '1.8.7');
define('IU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IU_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Simple activation hook  
register_activation_hook(__FILE__, 'iu_activate');
function iu_activate() {
    $default_settings = array(
        'hosting' => array(
            'istodata_hosted' => true,
            'storage_limit' => 5.0
        ),
        'dashboard' => array(
            'remove_welcome' => true,
            'remove_activity' => true,
            'remove_quick_draft' => true,
            'remove_news' => true,
            'remove_site_health' => true,
            'remove_at_glance' => true,
            'remove_woocommerce_setup' => true,
            'remove_elementor_overview' => true,
            'remove_qode_news' => true,
            'remove_avada_news' => true,
            'remove_premium_addons_news' => true,
            'remove_rank_math_overview' => true,
            'remove_smash_balloon_feeds' => false
        ),
        'optimizations' => array(
            'disable_emojis' => false,
            'disable_gutenberg' => false,
            'disable_comments' => false,
            'remove_dashicons' => false,
            'disable_jquery_migrate' => false,
            'remove_block_library_css' => false,
            'disable_widget_blocks' => false,
            'remove_rss_feeds' => false,
            'disable_embeds' => false,
            'remove_wp_generator' => false,
            'limit_post_revisions' => false,
            'remove_shortlink' => false,
            'disable_xmlrpc' => false,
            'disable_file_editing' => false,
            'remove_rest_api_links' => false,
            'disable_pingbacks' => false,
            'remove_rsd_link' => false,
            'remove_wlw_link' => false,
            'disable_image_sizes' => false,
            'remove_attributes' => true
        ),
        'additional' => array(
            'elementor_reading_time' => false,
            'rank_math_remove_categories' => false,
            'typed_js' => false
        )
    );
    
    add_option('istodata_utilities_settings', $default_settings);
    
    // Schedule weekly storage recalculation
    if (!wp_next_scheduled('iu_weekly_storage_recalc')) {
        wp_schedule_event(time(), 'weekly', 'iu_weekly_storage_recalc');
    }
    
    // Start initial storage calculation with queue system
    iu_start_queue_storage_calculation();
}

// Deactivation hook to clean up cron job
register_deactivation_hook(__FILE__, 'iu_deactivate');
function iu_deactivate() {
    // Clear scheduled cron jobs
    wp_clear_scheduled_hook('iu_weekly_storage_recalc');
    wp_clear_scheduled_hook('iu_storage_calculation_batch');
    
    // Clean up ALL plugin storage data completely
    delete_option('iu_storage_used');
    delete_option('iu_storage_last_updated');
    delete_option('iu_storage_batch_progress');
    delete_option('iu_storage_batch_directories');
    delete_option('iu_storage_queue_status');
    delete_option('iu_storage_queue_progress');
    delete_option('iu_storage_used_backup');
    
    // Clear any transients
    delete_transient('iu_queue_last_check');
    
    // Force clear any WordPress object cache for our options
    wp_cache_delete('iu_storage_used', 'options');
    wp_cache_delete('iu_storage_last_updated', 'options');
    wp_cache_delete('iu_storage_queue_status', 'options');
    wp_cache_delete('iu_storage_batch_progress', 'options');
    wp_cache_delete('iu_storage_batch_directories', 'options');
}

// Simple settings page
add_action('admin_menu', 'iu_add_admin_menu');
function iu_add_admin_menu() {
    add_options_page(
        'ISTODATA Utilities',
        'ISTODATA Utilities', 
        'manage_options',
        'istodata-utilities',
        'iu_settings_page'
    );
}

function iu_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'hosting';
    
    // Handle storage recalculation via GET request
    if (isset($_GET['recalc_storage']) && $_GET['recalc_storage'] == '1' && wp_verify_nonce($_GET['_wpnonce'], 'iu_recalc_storage')) {
        iu_start_smart_storage_calculation();
        
        // Use JavaScript redirect since headers are already sent
        $redirect_url = remove_query_arg(array('recalc_storage', '_wpnonce'));
        echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
        echo '<div class="notice notice-success"><p>Ο επανυπολογισμός ξεκίνησε... Ανακατεύθυνση...</p></div>';
    }
    
    // Handle manual cleanup
    if (isset($_GET['cleanup_storage']) && $_GET['cleanup_storage'] == '1' && wp_verify_nonce($_GET['_wpnonce'], 'iu_cleanup_storage')) {
        // Force cleanup all storage data
        delete_option('iu_storage_used');
        delete_option('iu_storage_files');
        delete_option('iu_storage_database');
        delete_option('iu_storage_last_updated');
        delete_option('iu_storage_batch_progress');
        delete_option('iu_storage_batch_directories');
        delete_option('iu_storage_queue_status');
        delete_option('iu_storage_queue_progress');
        delete_option('iu_storage_used_backup');
        delete_transient('iu_queue_last_check');
        
        // Clear object cache
        wp_cache_flush();
        
        // Use JavaScript redirect since headers are already sent
        $redirect_url = remove_query_arg(array('cleanup_storage', '_wpnonce'));
        echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
        echo '<div class="notice notice-success"><p>Storage δεδομένα καθαρίστηκαν... Ανακατεύθυνση...</p></div>';
    }
    
    // Handle manual queue trigger
    if (isset($_GET['trigger_queue']) && $_GET['trigger_queue'] == '1' && wp_verify_nonce($_GET['_wpnonce'], 'iu_trigger_queue')) {
        delete_transient('iu_queue_last_check');
        iu_process_queue_batch();
        
        // Use JavaScript redirect since headers are already sent
        $redirect_url = remove_query_arg(array('trigger_queue', '_wpnonce'));
        echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
        echo '<div class="notice notice-info"><p>Queue επεξεργασία ξεκίνησε... Ανακατεύθυνση...</p></div>';
    }
    
    // Handle manual update cache clear (debug mode)
    if (isset($_GET['clear_update_cache']) && $_GET['clear_update_cache'] == '1' && wp_verify_nonce($_GET['_wpnonce'], 'iu_clear_cache')) {
        $cache_key = 'iu_remote_version_' . md5(IU_GITHUB_API_URL);
        delete_transient($cache_key);
        delete_site_transient('update_plugins');
        
        // Use JavaScript redirect since headers are already sent
        $redirect_url = remove_query_arg(array('clear_update_cache', '_wpnonce'));
        echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
        echo '<div class="notice notice-info"><p>Update cache καθαρίστηκε... Ανακατεύθυνση...</p></div>';
    }
    
    // Handle direct calculation (fallback)
    if (isset($_GET['direct_calc']) && $_GET['direct_calc'] == '1' && wp_verify_nonce($_GET['_wpnonce'], 'iu_direct_calc')) {
        $result = iu_direct_storage_calculation();
        
        // Use JavaScript redirect since headers are already sent
        $redirect_url = remove_query_arg(array('direct_calc', '_wpnonce'));
        echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
        echo '<div class="notice notice-success"><p>Άμεσος υπολογισμός ολοκληρώθηκε... Ανακατεύθυνση...</p></div>';
    }
    
    // Handle form submission
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'istodata_utilities_settings-options')) {
        $submitted_tab = isset($_POST['active_tab']) ? sanitize_text_field($_POST['active_tab']) : $active_tab;
        $existing_settings = get_option('istodata_utilities_settings', array());
        $new_settings = isset($_POST['istodata_utilities_settings']) ? $_POST['istodata_utilities_settings'] : array();
        
        // Update settings for the submitted tab only
        if ($submitted_tab == 'hosting') {
            $existing_settings['hosting'] = isset($new_settings['hosting']) ? $new_settings['hosting'] : array();
        } elseif ($submitted_tab == 'dashboard') {
            $existing_settings['dashboard'] = isset($new_settings['dashboard']) ? $new_settings['dashboard'] : array();
        } elseif ($submitted_tab == 'optimizations') {
            $existing_settings['optimizations'] = isset($new_settings['optimizations']) ? $new_settings['optimizations'] : array();
        } elseif ($submitted_tab == 'additional') {
            $existing_settings['additional'] = isset($new_settings['additional']) ? $new_settings['additional'] : array();
        }
        
        update_option('istodata_utilities_settings', $existing_settings);
        echo '<div class="notice notice-success"><p>Οι ρυθμίσεις αποθηκεύτηκαν επιτυχώς!</p></div>';
    }
    
    $settings = get_option('istodata_utilities_settings', array());
    ?>
    <div class="wrap">
        <h1>ISTODATA Utilities</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=istodata-utilities&tab=hosting" class="nav-tab <?php echo $active_tab == 'hosting' ? 'nav-tab-active' : ''; ?>">Φιλοξενία</a>
            <a href="?page=istodata-utilities&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">Πίνακας Ελέγχου</a>
            <a href="?page=istodata-utilities&tab=optimizations" class="nav-tab <?php echo $active_tab == 'optimizations' ? 'nav-tab-active' : ''; ?>">Βελτιστοποιήσεις</a>
            <a href="?page=istodata-utilities&tab=additional" class="nav-tab <?php echo $active_tab == 'additional' ? 'nav-tab-active' : ''; ?>">Πρόσθετες Λειτουργίες</a>
        </h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('istodata_utilities_settings-options'); ?>
            <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>" />
            
            <?php if ($active_tab == 'hosting'): ?>
                <div class="istodata-tab-content">
                <h3>Φιλοξενία Ιστοσελίδας</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Φιλοξενία και Τεχνική Υποστήριξη</th>
                        <td>
                            <label>
                                <input type="checkbox" name="istodata_utilities_settings[hosting][istodata_hosted]" value="1" 
                                       <?php checked(isset($settings['hosting']['istodata_hosted']) ? $settings['hosting']['istodata_hosted'] : false); ?> />
                                Η ιστοσελίδα φιλοξενείται και υποστηρίζεται από την ISTODATA
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Όριο Αποθηκευτικού Χώρου (GB)</th>
                        <td>
                            <input type="number" step="0.1" min="0.1" name="istodata_utilities_settings[hosting][storage_limit]" 
                                   value="<?php echo isset($settings['hosting']['storage_limit']) ? esc_attr($settings['hosting']['storage_limit']) : '5.0'; ?>" />
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                
                <?php if (isset($settings['hosting']['istodata_hosted']) && $settings['hosting']['istodata_hosted']): ?>
                <div id="storage-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-radius: 4px;">
                    <h3 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Αποθηκευτικός Χώρος</h3>
                    
                    <h4>Κατάσταση Αποθηκευτικού Χώρου</h4>
                    <table class="form-table storage-table">
                        <tr>
                            <th scope="row">Όριο:</th>
                            <td><?php echo isset($settings['hosting']['storage_limit']) ? esc_html($settings['hosting']['storage_limit']) : '5.0'; ?> GB</td>
                        </tr>
                        <tr>
                            <th scope="row">Σε Χρήση:</th>
                            <td><?php 
                                $breakdown = iu_get_storage_breakdown();
                                $progress_bytes = iu_get_storage_calculation_progress();
                                
                                echo '<strong>' . iu_format_bytes($breakdown['total']) . '</strong>';
                                echo '<br><small style="color: #666;">├── Αρχεία: ' . iu_format_bytes($breakdown['files_with_overhead']) . ' (+20% overhead)</small>';
                                echo '<br><small style="color: #666;">└── Database: ' . iu_format_bytes($breakdown['database']) . '</small>';
                                
                            ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Διαθέσιμο:</th>
                            <td><?php 
                                $breakdown = iu_get_storage_breakdown();
                                $limit_gb = isset($settings['hosting']['storage_limit']) ? $settings['hosting']['storage_limit'] : 5.0;
                                $limit_bytes = $limit_gb * 1024 * 1024 * 1024;
                                $available = max(0, $limit_bytes - $breakdown['total']);
                                echo iu_format_bytes($available);
                            ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Κατάσταση:</th>
                            <td><?php 
                                $last_updated = get_option('iu_storage_last_updated');
                                $queue_status = get_option('iu_storage_queue_status', false);
                                
                                if ($queue_status && $queue_status['status'] === 'pending') {
                                    echo '<p style="color: #0073aa; font-weight: 500; margin-bottom: 10px;">⏳ Υπολογισμός σε εξέλιξη...</p>';
                                    
                                    // Show detailed progress information
                                    if (isset($queue_status['current_dir_index']) && isset($queue_status['total_size'])) {
                                        $directories = array('wp-admin', 'wp-content', 'wp-includes');
                                        $current_dir = isset($directories[$queue_status['current_dir_index']]) ? $directories[$queue_status['current_dir_index']] : 'database';
                                        $progress_percent = round(($queue_status['current_dir_index'] / 3) * 100);
                                        
                                        echo '<small style="color: #0073aa;">📂 Σαρώνει: <strong>' . $current_dir . '</strong> (' . $progress_percent . '%)</small>';
                                        echo '<br><div style="background: #ddd; height: 8px; border-radius: 4px; margin: 5px 0; max-width: 200px;"><div style="background: #0073aa; height: 100%; width: ' . $progress_percent . '%; border-radius: 4px; transition: width 0.3s;"></div></div>';
                                        echo '<small style="color: #666;">Προσωρινό μέγεθος: ' . iu_format_bytes($queue_status['total_size']) . '</small>';
                                    }
                                    
                                    // Show debug info only in debug mode
                                    $debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
                                    if ($debug_mode && isset($queue_status['last_processed'])) {
                                        echo '<br><small>🔧 Τελευταία επεξεργασία: ' . date('H:i:s', strtotime($queue_status['last_processed'])) . '</small>';
                                    }
                                } elseif ($last_updated) {
                                    echo 'Τελευταία ενημέρωση: ' . date('d/m/Y H:i:s', strtotime($last_updated));
                                } else {
                                    echo '<span style="color: #d63638;">Εκκρεμεί υπολογισμός</span>';
                                }
                            ?></td>
                        </tr>
                    </table>
                    
                    <div class="storage-progress-bar" style="margin: 15px 0;">
                        <?php
                        $breakdown = iu_get_storage_breakdown();
                        $limit_gb = isset($settings['hosting']['storage_limit']) ? $settings['hosting']['storage_limit'] : 5.0;
                        $limit_bytes = $limit_gb * 1024 * 1024 * 1024;
                        $percentage = $limit_bytes > 0 ? min(100, ($breakdown['total'] / $limit_bytes) * 100) : 0;
                        $color = $percentage >= 100 ? '#dc3232' : ($percentage >= 80 ? '#ffb900' : '#46b450');
                        ?>
                        <div style="background: #ddd; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0;">
                            <div style="background: <?php echo $color; ?>; height: 100%; width: <?php echo $percentage; ?>%; transition: width 0.3s;"></div>
                        </div>
                        <p><strong><?php echo number_format($percentage, 1); ?>%</strong> του διαθέσιμου χώρου</p>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <p style="margin-bottom: 10px;"><strong>Επανυπολογισμός:</strong></p>
                        <p style="margin-bottom: 15px; color: #666; font-size: 13px;">Ο χώρος επανυπολογίζεται αυτόματα κάθε εβδομάδα. Για άμεσο επανυπολογισμό, πατήστε το παρακάτω κουμπί.</p>
                        
                        <div style="margin-bottom: 10px;">
                            <a href="?page=istodata-utilities&tab=hosting&recalc_storage=1&_wpnonce=<?php echo wp_create_nonce('iu_recalc_storage'); ?>" class="button button-secondary">Επανυπολογισμός (Background)</a>
                            
                            <?php 
                            // Show debug tools only in debug mode
                            $debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
                            if ($debug_mode): ?>
                                <br><br>
                                <strong>🔧 Debug Tools:</strong><br>
                                <a href="?page=istodata-utilities&tab=hosting&cleanup_storage=1&_wpnonce=<?php echo wp_create_nonce('iu_cleanup_storage'); ?>&debug=1" class="button button-secondary" style="margin-right: 10px;">🗑️ Καθαρισμός Storage Data</a>
                                <a href="?page=istodata-utilities&tab=hosting&clear_update_cache=1&_wpnonce=<?php echo wp_create_nonce('iu_clear_cache'); ?>&debug=1" class="button button-secondary">🔄 Clear Update Cache</a>
                            <?php endif; ?>
                            
                            <?php 
                            // Show debug buttons only in debug mode
                            $debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
                            if ($debug_mode) {
                                $queue_status = get_option('iu_storage_queue_status', false);
                                if ($queue_status && $queue_status['status'] === 'pending'): ?>
                                    <a href="?page=istodata-utilities&tab=hosting&trigger_queue=1&_wpnonce=<?php echo wp_create_nonce('iu_trigger_queue'); ?>&debug=1" class="button button-primary" style="margin-right: 10px;">🔧 Force Queue Step</a>
                                <?php endif; ?>
                                <a href="?page=istodata-utilities&tab=hosting&direct_calc=1&_wpnonce=<?php echo wp_create_nonce('iu_direct_calc'); ?>&debug=1" class="button button-secondary">🔧 Άμεσος Υπολογισμός</a>
                            <?php } ?>
                        </div>
                        
                        <?php 
                        // Show direct calculation option if queue is stale
                        $queue_status = get_option('iu_storage_queue_status', false);
                        $show_manual = false;
                        
                        if ($queue_status && $queue_status['status'] === 'pending') {
                            $started_time = strtotime($queue_status['started_at']);
                            $last_processed_time = strtotime($queue_status['last_processed']);
                            
                            // Show manual option if queue hasn't been processed for 10 minutes
                            if ((time() - $last_processed_time) > 600) {
                                $show_manual = true;
                            }
                        }
                        
                        if ($show_manual): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                            <p style="margin: 0 0 10px 0; color: #856404;"><strong>⚠️ Queue εκτελείται αργά</strong></p>
                            <p style="margin: 0 0 10px 0; font-size: 13px; color: #856404;">Το αυτόματο processing φαίνεται να έχει σταματήσει. Δοκιμάστε άμεσο υπολογισμό:</p>
                            <button id="iu-manual-calc" class="button button-primary">Άμεσος Υπολογισμός (Ασφαλής)</button>
                            <div id="iu-calc-progress" style="display: none; margin-top: 10px;">
                                <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                                    <div id="iu-progress-bar" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s;"></div>
                                </div>
                                <p id="iu-progress-text" style="margin: 5px 0; font-size: 13px;">Προετοιμασία...</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                </div>
                
            <?php elseif ($active_tab == 'dashboard'): ?>
                <div class="istodata-tab-content">
                <h3>Διαχείριση Πίνακα Ελέγχου</h3>
                <p>Επιλέξτε τα widgets που θέλετε να αφαιρεθούν από τον πίνακα ελέγχου:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_welcome]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_welcome']) ? $settings['dashboard']['remove_welcome'] : false); ?> />
                                   Welcome</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_activity]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_activity']) ? $settings['dashboard']['remove_activity'] : false); ?> />
                                   Activity</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_quick_draft]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_quick_draft']) ? $settings['dashboard']['remove_quick_draft'] : false); ?> />
                                   Quick Draft</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_news']) ? $settings['dashboard']['remove_news'] : false); ?> />
                                   WordPress News</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_site_health]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_site_health']) ? $settings['dashboard']['remove_site_health'] : false); ?> />
                                   Site Health Status</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_at_glance]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_at_glance']) ? $settings['dashboard']['remove_at_glance'] : false); ?> />
                                   At a Glance</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_woocommerce_setup]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_woocommerce_setup']) ? $settings['dashboard']['remove_woocommerce_setup'] : false); ?> />
                                   WooCommerce Setup</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_elementor_overview]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_elementor_overview']) ? $settings['dashboard']['remove_elementor_overview'] : false); ?> />
                                   Elementor Overview</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_qode_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_qode_news']) ? $settings['dashboard']['remove_qode_news'] : false); ?> />
                                   Qode Interactive News</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_avada_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_avada_news']) ? $settings['dashboard']['remove_avada_news'] : false); ?> />
                                   Avada News</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_premium_addons_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_premium_addons_news']) ? $settings['dashboard']['remove_premium_addons_news'] : false); ?> />
                                   Premium Addons News</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_rank_math_overview]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_rank_math_overview']) ? $settings['dashboard']['remove_rank_math_overview'] : false); ?> />
                                   Rank Math Overview</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_smash_balloon_feeds]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_smash_balloon_feeds']) ? $settings['dashboard']['remove_smash_balloon_feeds'] : false); ?> />
                                   Smash Balloon Feeds</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                </div>
                
            <?php elseif ($active_tab == 'optimizations'): ?>
                <div class="istodata-tab-content">
                <h3>Βελτιστοποιήσεις WordPress</h3>
                <p>Ενεργοποιήστε τις επιθυμητές βελτιστοποιήσεις:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_emojis]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_emojis']) ? $settings['optimizations']['disable_emojis'] : false); ?> />
                                   Απενεργοποίηση Emojis</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_gutenberg]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_gutenberg']) ? $settings['optimizations']['disable_gutenberg'] : false); ?> />
                                   Απενεργοποίηση Gutenberg Editor</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_comments]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_comments']) ? $settings['optimizations']['disable_comments'] : false); ?> />
                                   Πλήρης απενεργοποίηση σχολίων</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_dashicons]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_dashicons']) ? $settings['optimizations']['remove_dashicons'] : false); ?> />
                                   Αφαίρεση Dashicons</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_jquery_migrate]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_jquery_migrate']) ? $settings['optimizations']['disable_jquery_migrate'] : false); ?> />
                                   Απενεργοποίηση jQuery Migrate</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_block_library_css]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_block_library_css']) ? $settings['optimizations']['remove_block_library_css'] : false); ?> />
                                   Αφαίρεση WP Block Library CSS</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_widget_blocks]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_widget_blocks']) ? $settings['optimizations']['disable_widget_blocks'] : false); ?> />
                                   Απενεργοποίηση Widget Blocks</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_rss_feeds]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_rss_feeds']) ? $settings['optimizations']['remove_rss_feeds'] : false); ?> />
                                   Αφαίρεση RSS Feed Links</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_embeds]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_embeds']) ? $settings['optimizations']['disable_embeds'] : false); ?> />
                                   Απενεργοποίηση WordPress Embeds</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_wp_generator]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_wp_generator']) ? $settings['optimizations']['remove_wp_generator'] : false); ?> />
                                   Αφαίρεση WP Generator Meta</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][limit_post_revisions]" value="1" 
                                         <?php checked(isset($settings['optimizations']['limit_post_revisions']) ? $settings['optimizations']['limit_post_revisions'] : false); ?> />
                                   Περιορισμός Post Revisions (10 max)</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_shortlink]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_shortlink']) ? $settings['optimizations']['remove_shortlink'] : false); ?> />
                                   Αφαίρεση Shortlink Meta</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_xmlrpc]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_xmlrpc']) ? $settings['optimizations']['disable_xmlrpc'] : false); ?> />
                                   Απενεργοποίηση XML-RPC</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_file_editing]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_file_editing']) ? $settings['optimizations']['disable_file_editing'] : false); ?> />
                                   Απενεργοποίηση File Editing</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_rest_api_links]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_rest_api_links']) ? $settings['optimizations']['remove_rest_api_links'] : false); ?> />
                                   Αφαίρεση REST API Links</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_pingbacks]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_pingbacks']) ? $settings['optimizations']['disable_pingbacks'] : false); ?> />
                                   Απενεργοποίηση Pingbacks/Trackbacks</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_rsd_link]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_rsd_link']) ? $settings['optimizations']['remove_rsd_link'] : false); ?> />
                                   Αφαίρεση RSD Link</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_wlw_link]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_wlw_link']) ? $settings['optimizations']['remove_wlw_link'] : false); ?> />
                                   Αφαίρεση Windows Live Writer Link</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_image_sizes]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_image_sizes']) ? $settings['optimizations']['disable_image_sizes'] : false); ?> />
                                   Απενεργοποίηση default διαστάσεων εικόνων</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_attributes]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_attributes']) ? $settings['optimizations']['remove_attributes'] : false); ?> />
                                   Αφαίρεση Attributes</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                </div>
                
            <?php else: ?>
                <div class="istodata-tab-content">
                <h3>Πρόσθετες Λειτουργίες</h3>
                <p>Ενεργοποιήστε τις επιθυμητές πρόσθετες λειτουργίες:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <?php 
                            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                            $elementor_pro_active = is_plugin_active('elementor-pro/elementor-pro.php');
                            ?>
                            <label>
                                <input type="checkbox" name="istodata_utilities_settings[additional][elementor_reading_time]" value="1" 
                                       <?php checked(isset($settings['additional']['elementor_reading_time']) ? $settings['additional']['elementor_reading_time'] : false); ?>
                                       <?php echo !$elementor_pro_active ? 'disabled' : ''; ?> />
                                Χρόνος Ανάγνωσης για Elementor
                                <?php if (!$elementor_pro_active): ?>
                                    <span style="color: #d63638;">(Απαιτείται Elementor Pro)</span>
                                <?php endif; ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php 
                            $rank_math_active = is_plugin_active('seo-by-rankmath/rank-math.php') || 
                                              is_plugin_active('seo-by-rankmath/rankmath.php') ||
                                              function_exists('rank_math');
                            ?>
                            <label>
                                <input type="checkbox" name="istodata_utilities_settings[additional][rank_math_remove_categories]" value="1" 
                                       <?php checked(isset($settings['additional']['rank_math_remove_categories']) ? $settings['additional']['rank_math_remove_categories'] : false); ?>
                                       <?php echo !$rank_math_active ? 'disabled' : ''; ?> />
                                Αφαίρεση κατηγοριών από Rank Math Breadcrumbs
                                <?php if (!$rank_math_active): ?>
                                    <span style="color: #d63638;">(Απαιτείται Rank Math)</span>
                                <?php endif; ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label>
                                <input type="checkbox" name="istodata_utilities_settings[additional][typed_js]" value="1" 
                                       <?php checked(isset($settings['additional']['typed_js']) ? $settings['additional']['typed_js'] : false); ?> />
                                Φόρτωση Typed.js
                            </label>
                            <a href="javascript:void(0);" onclick="toggleTypedCode()" style="font-size: 12px; text-decoration: none; margin-left: 20px;">
                                📋 Εμφάνιση ενδεικτικού κώδικα ενσωμάτωσης
                            </a>
                            <div id="typed-code-container" style="display: none; margin-top: 10px;">
                                <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; background: #f9f9f9; border: 1px solid #ddd; padding: 10px;" onclick="this.select();">
<script>
// Βρίσκουμε το στοιχείο με το id "typed"
const targetElement = document.getElementById('typed');

// Ορίζουμε την callback function που θα εκτελείται όταν το στοιχείο γίνει ορατό
function handleIntersection(entries, observer) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            // Αν το στοιχείο είναι ορατό, περιμένουμε 0.5 δευτερόλεπτο και μετά εκτελούμε τον κώδικα για το Typed.js
            setTimeout(() => {
                var typed = new Typed("#typed", {
                    stringsElement: '#typed-strings .jet-listing-dynamic-repeater__items',
                    typeSpeed: 30,
                    backSpeed: 5,
                    backDelay: 1000,
                    loop: true,
                });
            }, 500);

            // Σταματάμε να παρακολουθούμε το στοιχείο (αν δεν χρειάζεται να το παρακολουθούμε πλέον)
            observer.unobserve(targetElement);
        }
    });
}

// Δημιουργία του IntersectionObserver
const observer = new IntersectionObserver(handleIntersection, {
    root: null,   // Παρακολουθούμε το viewport (προεπιλογή)
    threshold: 1.0  // Το στοιχείο πρέπει να είναι 100% ορατό για να εκτελεστεί
});

// Ξεκινάμε την παρακολούθηση του στοιχείου
observer.observe(targetElement);
</script></textarea>
                                <p style="font-size: 11px; color: #666; margin-top: 5px;">
                                    💡 Κάντε κλικ στο textarea για επιλογή όλου του κώδικα
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
    
    <script>
    function toggleTypedCode() {
        var container = document.getElementById('typed-code-container');
        if (container.style.display === 'none') {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    }
    </script>
    
    <?php
}

// Basic optimizations
add_action('init', 'iu_apply_optimizations');
function iu_apply_optimizations() {
    $settings = get_option('istodata_utilities_settings', array());
    $optimizations = isset($settings['optimizations']) ? $settings['optimizations'] : array();
    
    if (!empty($optimizations['disable_emojis'])) {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }
    
    if (!empty($optimizations['disable_gutenberg'])) {
        add_filter('use_block_editor_for_post', '__return_false');
    }
    
    if (!empty($optimizations['disable_comments'])) {
        // Disable comment functionality
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        add_filter('comments_array', '__return_empty_array', 10, 2);
        
        // Remove comment support from post types
        add_action('init', function() {
            $post_types = get_post_types();
            foreach ($post_types as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        });
        
        // Remove comment-related admin menus
        add_action('admin_menu', function() {
            remove_menu_page('edit-comments.php');
        });
        
        // Remove comment meta boxes from post editor
        add_action('admin_init', function() {
            remove_meta_box('commentstatusdiv', 'post', 'normal');
            remove_meta_box('commentsdiv', 'post', 'normal');
            remove_meta_box('trackbacksdiv', 'post', 'normal');
            remove_meta_box('commentstatusdiv', 'page', 'normal');
            remove_meta_box('trackbacksdiv', 'page', 'normal');
        });
        
        // Remove from admin bar
        add_action('admin_bar_menu', function($wp_admin_bar) {
            $wp_admin_bar->remove_node('comments');
        }, 999);
        
        // Remove comment-related scripts and styles
        add_action('wp_enqueue_scripts', function() {
            wp_dequeue_script('comment-reply');
            wp_dequeue_style('wp-block-comments');
        });
        
        // Remove comments from dashboard
        add_action('wp_dashboard_setup', function() {
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        });
        
        // Redirect comment pages to homepage
        add_action('template_redirect', function() {
            if (is_comment_feed()) {
                wp_redirect(home_url(), 301);
                exit;
            }
        });
    }
    
    if (!empty($optimizations['remove_dashicons'])) {
        add_action('wp_enqueue_scripts', function() {
            if (!is_user_logged_in()) {
                wp_deregister_style('dashicons');
            }
        });
    }
    
    if (!empty($optimizations['disable_jquery_migrate'])) {
        add_action('wp_enqueue_scripts', function() {
            if (!is_admin()) {
                wp_deregister_script('jquery-migrate');
                wp_dequeue_script('jquery-migrate');
                
                // Re-register jQuery without migrate dependency
                global $wp_scripts;
                if (isset($wp_scripts->registered['jquery'])) {
                    $wp_scripts->registered['jquery']->deps = array('jquery-core');
                }
            }
        }, 100);
    }
    
    if (!empty($optimizations['remove_block_library_css'])) {
        add_action('wp_enqueue_scripts', function() {
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('wc-blocks-style');
        });
    }
    
    if (!empty($optimizations['disable_widget_blocks'])) {
        add_filter('use_widgets_block_editor', '__return_false');
        add_action('wp_enqueue_scripts', function() {
            wp_dequeue_style('wp-widgets-blocks');
        });
    }
    
    if (!empty($optimizations['remove_rss_feeds'])) {
        // Remove RSS feed links from head
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'feed_links', 2);
        
        // Redirect RSS feed requests to homepage
        add_action('template_redirect', function() {
            if (is_feed()) {
                wp_redirect(home_url(), 301);
                exit;
            }
        });
    }
    
    if (!empty($optimizations['disable_embeds'])) {
        // Remove embed functionality
        add_action('init', function() {
            global $wp;
            $wp->public_query_vars = array_diff($wp->public_query_vars, array('embed'));
            remove_action('rest_api_init', 'wp_oembed_register_route');
            add_filter('embed_oembed_discover', '__return_false');
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        });
        
        // Remove oEmbed discovery links from head - higher priority
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        
        // Remove embed scripts
        add_action('wp_enqueue_scripts', function() {
            wp_deregister_script('wp-embed');
        });
    }
    
    if (!empty($optimizations['remove_wp_generator'])) {
        // Remove WordPress version from head
        remove_action('wp_head', 'wp_generator');
        
        // Remove version from RSS feeds
        add_filter('the_generator', '__return_empty_string');
    }
    
    if (!empty($optimizations['limit_post_revisions'])) {
        // Limit post revisions to 10
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 10);
        }
    }
    
    if (!empty($optimizations['remove_shortlink'])) {
        // Remove shortlink from head
        remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
        
        // Remove shortlink from HTTP headers
        remove_action('template_redirect', 'wp_shortlink_header', 11, 0);
    }
    
    if (!empty($optimizations['disable_xmlrpc'])) {
        // Disable XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Remove XML-RPC pingback
        add_filter('xmlrpc_methods', function($methods) {
            unset($methods['pingback.ping']);
            unset($methods['pingback.extensions.getPingbacks']);
            return $methods;
        });
        
        // Remove X-Pingback header
        add_filter('wp_headers', function($headers) {
            unset($headers['X-Pingback']);
            return $headers;
        });
    }
    
    if (!empty($optimizations['disable_file_editing'])) {
        // Disable file editing in admin
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }
    
    if (!empty($optimizations['remove_rest_api_links'])) {
        // Remove REST API links from head with early hook
        add_action('wp_head', function() {
            remove_action('wp_head', 'rest_output_link_wp_head');
        }, 1);
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
    }
    
    if (!empty($optimizations['disable_pingbacks'])) {
        // Disable pingbacks/trackbacks
        add_filter('xmlrpc_methods', function($methods) {
            unset($methods['pingback.ping']);
            unset($methods['pingback.extensions.getPingbacks']);
            return $methods;
        });
        
        // Remove pingback URL from head
        add_filter('bloginfo_url', function($output, $property) {
            return ($property == 'pingback_url') ? null : $output;
        }, 11, 2);
    }
    
    if (!empty($optimizations['remove_rsd_link'])) {
        // Remove RSD (Really Simple Discovery) link with early hook
        add_action('wp_head', function() {
            remove_action('wp_head', 'rsd_link');
        }, 1);
    }
    
    if (!empty($optimizations['remove_wlw_link'])) {
        // Remove Windows Live Writer link with early hook
        add_action('wp_head', function() {
            remove_action('wp_head', 'wlwmanifest_link');
        }, 1);
    }
    
    if (!empty($optimizations['disable_image_sizes'])) {
        // Disable default WordPress image sizes
        add_filter('intermediate_image_sizes_advanced', function($sizes) {
            // Remove default WordPress image sizes
            unset($sizes['thumbnail']);
            unset($sizes['medium']);
            unset($sizes['medium_large']);
            unset($sizes['large']);
            unset($sizes['1536x1536']);
            unset($sizes['2048x2048']);
            return $sizes;
        });
        
        // Also disable generation of default sizes
        add_filter('big_image_size_threshold', '__return_false');
    }
    
    if (!empty($optimizations['remove_attributes'])) {
        add_filter('hidden_meta_boxes', 'iu_hide_meta_box_attributes', 10, 2);
    }
}

// Basic dashboard cleanup
add_action('wp_dashboard_setup', 'iu_dashboard_cleanup');
function iu_dashboard_cleanup() {
    $settings = get_option('istodata_utilities_settings', array());
    $dashboard = isset($settings['dashboard']) ? $settings['dashboard'] : array();
    
    if (!empty($dashboard['remove_welcome'])) {
        remove_action('welcome_panel', 'wp_welcome_panel');
    }
    
    if (!empty($dashboard['remove_activity'])) {
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    }
    
    if (!empty($dashboard['remove_quick_draft'])) {
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_news'])) {
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_site_health'])) {
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    }
    
    if (!empty($dashboard['remove_at_glance'])) {
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    }
    
    if (!empty($dashboard['remove_woocommerce_setup'])) {
        remove_meta_box('wc_admin_dashboard_setup', 'dashboard', 'normal');
    }
    
    if (!empty($dashboard['remove_elementor_overview'])) {
        remove_meta_box('e-dashboard-overview', 'dashboard', 'normal');
        remove_action('wp_dashboard_setup', array('Elementor\Core\Admin\Dashboard', 'register_dashboard_widget'));
    }
    
    if (!empty($dashboard['remove_qode_news'])) {
        remove_meta_box('qode_interactive_dashboard_widget', 'dashboard', 'side');
        remove_action('wp_dashboard_setup', 'qode_interactive_add_dashboard_widget');
    }
    
    if (!empty($dashboard['remove_avada_news'])) {
        remove_meta_box('fusion_builder_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('avada_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('themefusion-news', 'dashboard', 'normal');
        remove_meta_box('themefusion-news', 'dashboard', 'side');
        remove_action('wp_dashboard_setup', 'fusion_builder_add_dashboard_widget');
    }
    
    if (!empty($dashboard['remove_premium_addons_news'])) {
        // Premium Addons for Elementor - correct ID found: pa-stories
        remove_meta_box('pa-stories', 'dashboard', 'normal');
        remove_meta_box('pa-stories', 'dashboard', 'side');
        // Backup IDs just in case
        remove_meta_box('pa_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('premium_addons_dashboard_widget', 'dashboard', 'normal');
        // Remove actions
        remove_action('wp_dashboard_setup', 'pa_dashboard_widget');
        remove_action('wp_dashboard_setup', 'premium_addons_dashboard_widget_display');
    }
    
    if (!empty($dashboard['remove_rank_math_overview'])) {
        // Rank Math - confirmed ID: rank_math_dashboard_widget
        remove_meta_box('rank_math_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('rank_math_dashboard_widget', 'dashboard', 'side');
        // Backup IDs just in case
        remove_meta_box('rank-math-dashboard-widget', 'dashboard', 'normal');
        remove_meta_box('rankmath_dashboard_widget', 'dashboard', 'normal');
        // Remove actions
        remove_action('wp_dashboard_setup', 'rank_math_dashboard_widget');
        remove_action('wp_dashboard_setup', array('RankMath\Admin\Dashboard', 'dashboard_widget'));
    }
    
    if (!empty($dashboard['remove_smash_balloon_feeds'])) {
        // Remove Smash Balloon Feeds dashboard widget
        remove_meta_box('sb_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('sb_dashboard_widget', 'dashboard', 'side');
    }
    
    // General widget removal hook - catches widgets that load later
    add_action('admin_head-index.php', function() use ($dashboard) {
        if (!empty($dashboard['remove_premium_addons_news'])) {
            echo '<style>
                #pa-stories, [id*="pa-stories"], [class*="pa-stories"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#pa-stories, [id*=\'pa-stories\'], [class*=\'pa-stories\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_rank_math_overview'])) {
            echo '<style>
                #rank_math_dashboard_widget, [id*="rank_math_dashboard"], [class*="rank-math"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#rank_math_dashboard_widget, [id*=\'rank_math_dashboard\'], [class*=\'rank-math\']").remove();
                });
            </script>';
        }
    });
    
    // Add ISTODATA widgets if hosting is enabled
    $settings = get_option('istodata_utilities_settings', array());
    if (!empty($settings['hosting']['istodata_hosted'])) {
        wp_add_dashboard_widget(
            'iu_support_widget',
            'Τεχνική Υποστήριξη',
            'iu_support_widget_content'
        );
        
        wp_add_dashboard_widget(
            'iu_storage_widget',
            'Αποθηκευτικός Χώρος',
            'iu_storage_widget_content'
        );
    }
}

function iu_support_widget_content() {
    ?>
    <p>Φροντίζουμε την ιστοσελίδα σας, ώστε τα πάντα να λειτουργούν άψογα! Εάν, ωστόσο, αντιμετωπίσετε κάποιο πρόβλημα, επικοινωνήστε με το Helpdesk της ISTODATA με έναν από τους παρακάτω τρόπους:</p>
    
    <p><strong>Με Ticket (Όλες τις ημέρες, 24 ώρες)</strong><br>
    <a target="_blank" href="https://share.hsforms.com/1Nd1YbK2QTyaGDE-fptFe7g5aii9?website=www.istodata.com">Υποβολή Αιτήματος Υποστήριξης</a></p>
    
    <p><strong>Με E-mail (Όλες τις ημέρες, 24 ώρες)</strong><br>
    στο <a href="mailto:helpdesk@istodata.com">helpdesk@istodata.com</a></p>
    
    <p><strong>Τηλεφωνικά (Δευτέρα – Παρασκευή, 9 π.μ. - 5 μ.μ.)</strong><br>
    στο <a href="tel:00302111989240">(+30) 211 19 89 240</a></p>
    <?php
}

function iu_storage_widget_content() {
    $settings = get_option('istodata_utilities_settings', array());
    $limit = isset($settings['hosting']['storage_limit']) ? $settings['hosting']['storage_limit'] : 5.0;
    
    // Get storage usage
    $breakdown = iu_get_storage_breakdown();
    $limit_bytes = $limit * 1024 * 1024 * 1024; // Convert GB to bytes
    $available_bytes = max(0, $limit_bytes - $breakdown['total']);
    $percentage = $limit_bytes > 0 ? min(100, ($breakdown['total'] / $limit_bytes) * 100) : 0;
    
    $color = $percentage >= 100 ? '#dc3232' : ($percentage >= 80 ? '#ffb900' : '#46b450');
    ?>
    <table class="widefat">
        <tr>
            <td><strong>Όριο:</strong></td>
            <td><?php echo esc_html($limit); ?> GB</td>
        </tr>
        <tr>
            <td><strong>Σε Χρήση:</strong></td>
            <td><?php echo iu_format_bytes($breakdown['total']); ?></td>
        </tr>
        <tr>
            <td><strong>Διαθέσιμο:</strong></td>
            <td><?php echo iu_format_bytes($available_bytes); ?></td>
        </tr>
    </table>
    
    <div style="margin: 15px 0;">
        <div style="background: #ddd; height: 20px; border-radius: 10px; overflow: hidden;">
            <div style="background: <?php echo $color; ?>; height: 100%; width: <?php echo min(100, $percentage); ?>%; transition: width 0.3s;"></div>
        </div>
        <p style="text-align: center; margin: 5px 0 0 0;"><strong><?php echo number_format($percentage, 1); ?>%</strong> του διαθέσιμου χώρου</p>
    </div>
    <?php
}

// Storage calculation functions
function iu_get_storage_usage() {
    $cached_usage = get_option('iu_storage_used', false);
    
    // Check if queue calculation is in progress
    $queue_status = get_option('iu_storage_queue_status', false);
    
    if ($cached_usage === false && !$queue_status) {
        // No cached data and no calculation in progress - start appropriate calculation
        iu_start_smart_storage_calculation();
        return 0; // Return 0 while calculation is starting
    } elseif ($queue_status && $queue_status['status'] === 'pending') {
        // Calculation in progress - use old cached value for consistency
        $backup_usage = get_option('iu_storage_used_backup', false);
        return $backup_usage !== false ? $backup_usage : $cached_usage;
    }
    
    return $cached_usage !== false ? $cached_usage : 0;
}

// Get current calculation progress (for display only)
function iu_get_storage_calculation_progress() {
    $queue_status = get_option('iu_storage_queue_status', false);
    
    if ($queue_status && $queue_status['status'] === 'pending') {
        return isset($queue_status['total_size']) ? $queue_status['total_size'] : 0;
    }
    
    return false;
}

// Smart calculation starter - chooses direct vs queue based on estimated file count
function iu_start_smart_storage_calculation() {
    $estimated_files = iu_estimate_file_count();
    
    // Conservative limit for shared server with multiple sites
    // 8GB RAM, 4 cores but shared = max 6000 files for direct calculation
    $direct_calculation_limit = 6000;
    
    if ($estimated_files <= $direct_calculation_limit) {
        // Small site - use direct calculation
        error_log("ISTODATA Utilities: Using direct calculation for ~{$estimated_files} files");
        iu_direct_storage_calculation_safe();
    } else {
        // Large site - use queue system
        error_log("ISTODATA Utilities: Using queue system for ~{$estimated_files} files");
        iu_start_queue_storage_calculation();
    }
}

// Estimate total file count quickly (sampling approach)
function iu_estimate_file_count() {
    $directories = array(
        ABSPATH . 'wp-admin',
        ABSPATH . 'wp-content', 
        ABSPATH . 'wp-includes'
    );
    
    $estimated_total = 0;
    
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }
        
        try {
            // Quick estimation using directory listing depth
            $estimated_total += iu_quick_file_count_estimate($directory);
        } catch (Exception $e) {
            // If estimation fails, assume it's a large directory
            $estimated_total += 10000;
        }
    }
    
    return $estimated_total;
}

// Quick file count estimation (non-exhaustive)
function iu_quick_file_count_estimate($directory) {
    $count = 0;
    $max_sample = 100; // Only sample first 100 items per directory level
    
    try {
        if ($handle = opendir($directory)) {
            $items_checked = 0;
            while (($item = readdir($handle)) !== false && $items_checked < $max_sample) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $full_path = $directory . DIRECTORY_SEPARATOR . $item;
                
                if (is_file($full_path)) {
                    $count++;
                } elseif (is_dir($full_path)) {
                    // For subdirectories, add estimated count (recursive but limited)
                    $count += iu_quick_file_count_estimate($full_path);
                }
                
                $items_checked++;
            }
            closedir($handle);
            
            // If we hit the sample limit, extrapolate
            if ($items_checked >= $max_sample) {
                $count = $count * 2; // Conservative extrapolation
            }
        }
    } catch (Exception $e) {
        // If we can't read the directory, assume it has some files
        $count = 500;
    }
    
    return $count;
}

// Safe direct calculation for smaller sites
function iu_direct_storage_calculation_safe() {
    // Preserve current cached value during calculation
    $current_cached = get_option('iu_storage_used', false);
    if ($current_cached !== false) {
        update_option('iu_storage_used_backup', $current_cached);
    }
    
    // Set conservative limits for shared environment
    @ini_set('max_execution_time', 180); // 3 minutes
    @ini_set('memory_limit', '512M');
    
    $total_size = 0;
    $directories = array(
        ABSPATH . 'wp-admin',
        ABSPATH . 'wp-content', 
        ABSPATH . 'wp-includes'
    );
    
    $start_time = time();
    $max_time = 150; // 2.5 minutes to leave buffer
    
    try {
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            
            // Check time limit
            if ((time() - $start_time) > $max_time) {
                // Time limit approaching, fall back to queue system
                error_log('ISTODATA Utilities: Direct calculation taking too long, switching to queue');
                iu_start_queue_storage_calculation();
                return;
            }
            
            $total_size += iu_get_directory_size_direct($directory);
        }
        
        // Save results with full breakdown
        $result = iu_store_storage_calculation($total_size);
        
        error_log('ISTODATA Utilities: Direct calculation completed. Files: ' . iu_format_bytes($total_size) . ', Database: ' . iu_format_bytes($result['database']) . ', Total: ' . iu_format_bytes($result['total']));
        
    } catch (Exception $e) {
        error_log('ISTODATA Utilities: Direct calculation failed, falling back to queue: ' . $e->getMessage());
        iu_start_queue_storage_calculation();
    }
}

// Legacy function - now redirects to background processing
function iu_calculate_storage_usage() {
    // Check if background calculation is already running
    $batch_progress = get_option('iu_storage_batch_progress', false);
    if ($batch_progress && $batch_progress['status'] === 'processing') {
        return $batch_progress['total_size'];
    }
    
    // Start background calculation
    iu_start_batch_storage_calculation();
    return 0; // Return 0 while calculation starts
}

function iu_get_directory_size($directory) {
    $size = 0;
    
    if (!is_dir($directory)) {
        return 0;
    }
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    } catch (Exception $e) {
        // If there's an error reading a directory, skip it
        error_log('ISTODATA Utilities: Error reading directory ' . $directory . ': ' . $e->getMessage());
    }
    
    return $size;
}

function iu_format_bytes($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

// Get database size
function iu_get_database_size() {
    global $wpdb;
    
    try {
        // Get WordPress tables only
        $tables = $wpdb->get_results("SHOW TABLE STATUS LIKE '{$wpdb->prefix}%'");
        $size = 0;
        
        foreach ($tables as $table) {
            $size += $table->Data_length + $table->Index_length;
        }
        
        return $size;
        
    } catch (Exception $e) {
        // Fallback: return 0 if database query fails
        error_log('ISTODATA Utilities: Database size query failed: ' . $e->getMessage());
        return 0;
    }
}

// Store complete storage calculation results
function iu_store_storage_calculation($files_raw_bytes, $database_bytes = null) {
    // Calculate database size if not provided
    if ($database_bytes === null) {
        $database_bytes = iu_get_database_size();
    }
    
    // Calculate files with 20% overhead
    $files_with_overhead = $files_raw_bytes * 1.20;
    
    // Calculate total
    $total = $files_with_overhead + $database_bytes;
    
    // Store all values
    update_option('iu_storage_files', $files_with_overhead);
    update_option('iu_storage_database', $database_bytes);
    update_option('iu_storage_used', $total);
    update_option('iu_storage_last_updated', current_time('mysql'));
    
    // Clean up backup
    delete_option('iu_storage_used_backup');
    
    return array(
        'files_raw' => $files_raw_bytes,
        'files_with_overhead' => $files_with_overhead,
        'database' => $database_bytes,
        'total' => $total
    );
}

// Get breakdown storage values (for UI display)
function iu_get_storage_breakdown() {
    return array(
        'files_with_overhead' => get_option('iu_storage_files', 0),
        'database' => get_option('iu_storage_database', 0),
        'total' => get_option('iu_storage_used', 0)
    );
}

// Enhanced storage warning in admin with calculation status
add_action('admin_notices', 'iu_storage_warning');
function iu_storage_warning() {
    $settings = get_option('istodata_utilities_settings', array());
    
    if (!isset($settings['hosting']['istodata_hosted']) || !$settings['hosting']['istodata_hosted']) {
        return;
    }
    
    // Use stable cached value for warnings
    $breakdown = iu_get_storage_breakdown();
    $limit = isset($settings['hosting']['storage_limit']) ? $settings['hosting']['storage_limit'] : 5.0;
    $limit_bytes = $limit * 1024 * 1024 * 1024;
    $percentage = $limit_bytes > 0 ? min(100, ($breakdown['total'] / $limit_bytes) * 100) : 0;
    
    // Check if calculation is in progress
    $queue_status = get_option('iu_storage_queue_status', false);
    $calculation_in_progress = ($queue_status && $queue_status['status'] === 'pending');
    
    $message = '';
    $class = '';
    
    if ($percentage >= 100) {
        $message = 'Έχετε εξαντλήσει τον αποθηκευτικό σας χώρο. Παρακαλούμε επικοινωνήστε με το Helpdesk για να αναβαθμίσετε το πακέτο φιλοξενίας σας.';
        $class = 'notice-error';
        
    } elseif ($percentage >= 90) {
        $message = sprintf('Χρησιμοποιείται το %.1f%% του αποθηκευτικού χώρου σας!', $percentage);
        $class = 'notice-error';
        
    } elseif ($percentage >= 80) {
        $message = sprintf('Χρησιμοποιείται το %.1f%% του αποθηκευτικού χώρου σας!', $percentage);
        $class = 'notice-warning';
        
    } else {
        return; // No warning needed
    }
    
    if ($message) {
        echo '<div class="notice ' . $class . ' is-dismissible">';
        echo '<p><strong>⚠️ ΠΡΟΣΟΧΗ:</strong> ' . $message . '</p>';
        echo '</div>';
    }
}

// Enhanced upload prevention with real-time storage checking
add_filter('wp_handle_upload_prefilter', 'iu_check_storage_before_upload');
function iu_check_storage_before_upload($file) {
    $settings = get_option('istodata_utilities_settings', array());
    
    // Only check if hosting is enabled
    if (!isset($settings['hosting']['istodata_hosted']) || !$settings['hosting']['istodata_hosted']) {
        return $file;
    }
    
    // Get current usage (including ongoing calculations)
    $current_usage = iu_get_real_time_storage_usage();
    $limit = isset($settings['hosting']['storage_limit']) ? $settings['hosting']['storage_limit'] : 5.0;
    $limit_bytes = $limit * 1024 * 1024 * 1024;
    
    // Calculate how much space the new file will take
    $file_size = isset($file['size']) ? $file['size'] : 0;
    $new_total = $current_usage + $file_size;
    
    // Check if we're at or over limit
    if ($new_total > $limit_bytes) {
        $available_space = max(0, $limit_bytes - $current_usage);
        
        // Check if calculation is in progress
        $queue_status = get_option('iu_storage_queue_status', false);
        $calculation_in_progress = ($queue_status && $queue_status['status'] === 'pending');
        
        $error_message = sprintf(
            'Δεν υπάρχει επαρκής αποθηκευτικός χώρος. Διαθέσιμος χώρος: %s, Μέγεθος αρχείου: %s.',
            iu_format_bytes($available_space),
            iu_format_bytes($file_size)
        );
        
        
        $error_message .= ' Παρακαλούμε επικοινωνήστε με το Helpdesk για αναβάθμιση του πακέτου φιλοξενίας.';
        
        $file['error'] = $error_message;
    }
    
    return $file;
}

// Get real-time storage usage for upload checking (now just uses stable cached value)
function iu_get_real_time_storage_usage() {
    // Always use the stable cached value - no complex estimations needed
    return iu_get_storage_usage();
}

// Load admin CSS and JS
add_action('admin_enqueue_scripts', 'iu_admin_scripts');
function iu_admin_scripts($hook) {
    // Load CSS on all admin pages
    wp_enqueue_style('istodata-utilities-admin', IU_PLUGIN_URL . 'assets/css/admin.css', array(), IU_PLUGIN_VERSION);
    
    // Load JS only on our settings page
    if ($hook === 'settings_page_istodata-utilities') {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            // Auto-refresh page if calculation is in progress
            if ($(".storage-progress-bar").length > 0) {
                var queueStatus = "' . (($queue_status = get_option('iu_storage_queue_status', false)) && $queue_status['status'] === 'pending' ? 'pending' : 'none') . '";
                if (queueStatus === "pending") {
                    // Refresh page every 10 seconds to show progress
                    setTimeout(function() {
                        location.reload();
                    }, 10000);
                }
            }
            
            $("#iu-manual-calc").click(function() {
                var button = $(this);
                var progress = $("#iu-calc-progress");
                var progressBar = $("#iu-progress-bar");
                var progressText = $("#iu-progress-text");
                
                button.prop("disabled", true).text("Εκτελείται...");
                progress.show();
                
                // Start calculation
                performBatchCalculation(0, 0, 0);
                
                function performBatchCalculation(dirIndex, dirPosition, totalSize) {
                    progressText.text("Επεξεργασία καταλόγου " + (dirIndex + 1) + "/3...");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "iu_manual_batch_calc",
                            dir_index: dirIndex,
                            dir_position: dirPosition,
                            total_size: totalSize,
                            nonce: "' . wp_create_nonce('iu_manual_batch') . '"
                        },
                        success: function(response) {
                            if (response.success) {
                                var data = response.data;
                                var percent = Math.round((data.dir_index / 3) * 100);
                                
                                progressBar.css("width", percent + "%");
                                progressText.text("Κατάλογος " + (data.dir_index + 1) + "/3 - " + data.formatted_size);
                                
                                if (data.completed) {
                                    progressBar.css("width", "100%");
                                    progressText.text("Ολοκληρώθηκε! Συνολικός χώρος: " + data.formatted_size);
                                    button.prop("disabled", false).text("Ολοκληρώθηκε");
                                    
                                    // Reload page after 2 seconds
                                    setTimeout(function() {
                                        location.reload();
                                    }, 2000);
                                } else {
                                    // Continue with next batch
                                    setTimeout(function() {
                                        performBatchCalculation(data.dir_index, data.dir_position, data.total_size);
                                    }, 500);
                                }
                            } else {
                                progressText.text("Σφάλμα: " + (response.data || "Άγνωστο σφάλμα"));
                                button.prop("disabled", false).text("Δοκιμάστε ξανά");
                            }
                        },
                        error: function() {
                            progressText.text("Σφάλμα δικτύου");
                            button.prop("disabled", false).text("Δοκιμάστε ξανά");
                        }
                    });
                }
            });
        });
        ');
    }
}

// Elementor Reading Time functionality
add_action('init', 'iu_init_additional_features');
function iu_init_additional_features() {
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    
    
    if (!empty($additional['elementor_reading_time'])) {
        iu_init_elementor_reading_time();
    }
    
    if (!empty($additional['rank_math_remove_categories'])) {
        iu_init_rank_math_remove_categories();
    }
    
    if (!empty($additional['typed_js'])) {
        add_action('wp_enqueue_scripts', 'iu_enqueue_typed_js');
    }
}

function iu_init_elementor_reading_time() {
    // Include the necessary WordPress admin file for is_plugin_active() function
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    
    if (is_plugin_active('elementor-pro/elementor-pro.php') && class_exists('Reading_Time_Tag')) {
        // Elementor Pro is active, so register your dynamic tag action
        add_action('elementor/dynamic_tags/register_tags', function($dynamic_tags) {
            // Finally register the tag
            $dynamic_tags->register_tag('Reading_Time_Tag');
        });
    }
}

function iu_init_rank_math_remove_categories() {
    // Include the necessary WordPress admin file for is_plugin_active() function
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    
    if (is_plugin_active('seo-by-rankmath/rank-math.php') || 
        is_plugin_active('seo-by-rankmath/rankmath.php') ||
        function_exists('rank_math')) {
        // Rank Math is active, so add the breadcrumb filter
        add_filter('rank_math/frontend/breadcrumb/items', 'iu_remove_categories_from_breadcrumbs', 10, 2);
    }
}

// Filter to remove categories from Rank Math Breadcrumbs
function iu_remove_categories_from_breadcrumbs($crumbs, $class) {
    // Check if we are viewing single posts
    if (is_singular('post')) {
        // Unset elements with key 1 (usually the category)
        unset($crumbs[1]);
        $crumbs = array_values($crumbs);
        return $crumbs;
    }
    return $crumbs;
}

// Hide meta box attributes
function iu_hide_meta_box_attributes($hidden, $screen) {
    $hidden[] = 'pageparentdiv';
    return $hidden;
}

// Enqueue local Typed.js file
function iu_enqueue_typed_js() {
    wp_enqueue_script('typed-js', plugin_dir_url(__FILE__) . 'assets/js/typed.js', array(), '2.0.16', true);
}

// Only define Elementor Reading Time Tag if Elementor is active
if (class_exists('\Elementor\Core\DynamicTags\Tag')) {
    class Reading_Time_Tag extends \Elementor\Core\DynamicTags\Tag {
    
    public function get_name() {
        return 'reading-time';
    }
    
    public function get_title() {
        return __('Reading Time', 'text-domain');
    }
    
    public function get_group() {
        return 'post';
    }
    
    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }
    
    protected function register_controls() {
        // Add any necessary controls for your dynamic tag here
    }
    
    public function render() {
        $post_id = get_the_ID();
        $content = get_post_field('post_content', $post_id);
        echo iu_estimate_reading_time($content);
    }
}
}

// Updated estimate_reading_time() function for Greek content
function iu_estimate_reading_time($content) {
    // Remove HTML tags from the content
    $clean_content = strip_tags($content);
    // Split content into words based on spaces and punctuation marks
    $words = preg_split('/\s+/', $clean_content, -1, PREG_SPLIT_NO_EMPTY);
    $word_count = count($words);
    $words_per_minute = 200; // Average reading speed (words per minute)
    $reading_time = ceil($word_count / $words_per_minute);
    return $reading_time;
}

// Footer change
add_filter('admin_footer_text', 'iu_footer_text');
function iu_footer_text($text) {
    $settings = get_option('istodata_utilities_settings', array());
    if (!empty($settings['hosting']['istodata_hosted'])) {
        return 'Developed by <a target="_blank" href="https://www.istodata.com/">ISTODATA</a> | Βασισμένο στο WordPress';
    }
    return $text;
}

// Weekly storage recalculation cron job - now uses smart calculation
add_action('iu_weekly_storage_recalc', 'iu_recalculate_storage_cron');
function iu_recalculate_storage_cron() {
    // Start smart calculation (auto-chooses direct vs queue)
    iu_start_smart_storage_calculation();
}

// Background batch storage calculation
add_action('iu_storage_calculation_batch', 'iu_process_storage_batch');
function iu_start_batch_storage_calculation() {
    // Clear any existing data
    delete_option('iu_storage_used');
    delete_option('iu_storage_last_updated');
    delete_option('iu_storage_batch_progress');
    
    // Initialize directories to scan
    $directories = array(
        ABSPATH . 'wp-admin',
        ABSPATH . 'wp-content', 
        ABSPATH . 'wp-includes'
    );
    
    // Store directories and initialize progress
    update_option('iu_storage_batch_directories', $directories);
    update_option('iu_storage_batch_progress', array(
        'total_size' => 0,
        'current_dir_index' => 0,
        'current_dir_position' => 0,
        'status' => 'processing'
    ));
    
    // Schedule first batch
    if (!wp_next_scheduled('iu_storage_calculation_batch')) {
        wp_schedule_single_event(time() + 5, 'iu_storage_calculation_batch');
    }
}

function iu_process_storage_batch() {
    // Safety: increase time and memory limits but don't fail if they can't be set
    @ini_set('max_execution_time', 300); // 5 minutes
    @ini_set('memory_limit', '512M');
    
    $directories = get_option('iu_storage_batch_directories', array());
    $progress = get_option('iu_storage_batch_progress', array());
    
    if (empty($directories) || empty($progress) || $progress['status'] !== 'processing') {
        return; // Already completed or invalid state
    }
    
    $start_time = time();
    $max_execution_time = 240; // 4 minutes to leave buffer
    $batch_size = 1000; // Process 1000 files per batch
    $processed_files = 0;
    
    $current_dir_index = $progress['current_dir_index'];
    $current_dir_position = $progress['current_dir_position'];
    $total_size = $progress['total_size'];
    
    // Process current directory
    if ($current_dir_index < count($directories)) {
        $directory = $directories[$current_dir_index];
        
        if (is_dir($directory)) {
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                $file_count = 0;
                foreach ($iterator as $file) {
                    // Skip to our current position
                    if ($file_count < $current_dir_position) {
                        $file_count++;
                        continue;
                    }
                    
                    if ($file->isFile()) {
                        $total_size += $file->getSize();
                        $processed_files++;
                        
                        // Check if we should take a break
                        if ($processed_files >= $batch_size || (time() - $start_time) >= $max_execution_time) {
                            break;
                        }
                    }
                    $file_count++;
                }
                
                // Update position
                $current_dir_position = $file_count;
                
                // Check if directory is complete
                if ($processed_files < $batch_size && (time() - $start_time) < $max_execution_time) {
                    // Directory completed, move to next
                    $current_dir_index++;
                    $current_dir_position = 0;
                }
                
            } catch (Exception $e) {
                // Skip problematic directory and move to next
                error_log('ISTODATA Utilities: Error processing directory ' . $directory . ': ' . $e->getMessage());
                $current_dir_index++;
                $current_dir_position = 0;
            }
        } else {
            // Directory doesn't exist, skip to next
            $current_dir_index++;
            $current_dir_position = 0;
        }
    }
    
    // Update progress
    $progress = array(
        'total_size' => $total_size,
        'current_dir_index' => $current_dir_index,
        'current_dir_position' => $current_dir_position,
        'status' => ($current_dir_index >= count($directories)) ? 'completed' : 'processing'
    );
    
    update_option('iu_storage_batch_progress', $progress);
    
    if ($progress['status'] === 'completed') {
        // Calculation complete - store with full breakdown
        $result = iu_store_storage_calculation($total_size);
        
        // Clean up batch data
        delete_option('iu_storage_batch_progress');
        delete_option('iu_storage_batch_directories');
        
        error_log('ISTODATA Utilities: Storage calculation completed. Files: ' . iu_format_bytes($total_size) . ', Database: ' . iu_format_bytes($result['database']) . ', Total: ' . iu_format_bytes($result['total']));
    } else {
        // Schedule next batch with a small delay to prevent overload
        wp_schedule_single_event(time() + 10, 'iu_storage_calculation_batch');
    }
}

// AJAX handler for manual batch calculation
add_action('wp_ajax_iu_manual_batch_calc', 'iu_handle_manual_batch_calc');
function iu_handle_manual_batch_calc() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iu_manual_batch')) {
        wp_die('Security check failed');
    }
    
    // Get parameters
    $dir_index = intval($_POST['dir_index']);
    $dir_position = intval($_POST['dir_position']);
    $total_size = intval($_POST['total_size']);
    
    // Set safe limits
    @ini_set('max_execution_time', 60); // 1 minute per batch
    @ini_set('memory_limit', '256M');
    
    $directories = array(
        ABSPATH . 'wp-admin',
        ABSPATH . 'wp-content', 
        ABSPATH . 'wp-includes'
    );
    
    $start_time = time();
    $max_execution_time = 50; // 50 seconds to leave buffer
    $batch_size = 1000; // Process 1000 files per batch
    $processed_files = 0;
    
    try {
        // Process current directory
        if ($dir_index < count($directories)) {
            $directory = $directories[$dir_index];
            
            if (is_dir($directory)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                $file_count = 0;
                foreach ($iterator as $file) {
                    // Skip to our current position
                    if ($file_count < $dir_position) {
                        $file_count++;
                        continue;
                    }
                    
                    if ($file->isFile()) {
                        $total_size += $file->getSize();
                        $processed_files++;
                        
                        // Check if we should take a break
                        if ($processed_files >= $batch_size || (time() - $start_time) >= $max_execution_time) {
                            break;
                        }
                    }
                    $file_count++;
                }
                
                // Update position
                $dir_position = $file_count;
                
                // Check if directory is complete
                if ($processed_files < $batch_size && (time() - $start_time) < $max_execution_time) {
                    // Directory completed, move to next
                    $dir_index++;
                    $dir_position = 0;
                }
            } else {
                // Directory doesn't exist, skip to next
                $dir_index++;
                $dir_position = 0;
            }
        }
        
        // Check if all directories are complete
        $completed = ($dir_index >= count($directories));
        
        if ($completed) {
            // Save final results with full breakdown
            iu_store_storage_calculation($total_size);
            
            // Clear any existing batch data
            delete_option('iu_storage_batch_progress');
            delete_option('iu_storage_batch_directories');
        }
        
        wp_send_json_success(array(
            'dir_index' => $dir_index,
            'dir_position' => $dir_position,
            'total_size' => $total_size,
            'formatted_size' => iu_format_bytes($total_size),
            'completed' => $completed
        ));
        
    } catch (Exception $e) {
        wp_send_json_error('Σφάλμα: ' . $e->getMessage());
    }
}

// ========================================================================
// NEW QUEUE-BASED STORAGE CALCULATION SYSTEM (Cron-Independent)
// ========================================================================

// Start queue-based storage calculation
function iu_start_queue_storage_calculation() {
    // Preserve current cached value during calculation
    $current_cached = get_option('iu_storage_used', false);
    if ($current_cached !== false) {
        update_option('iu_storage_used_backup', $current_cached);
    }
    
    // Clear batch data but keep storage_used for now
    delete_option('iu_storage_batch_progress');
    delete_option('iu_storage_batch_directories');
    
    // Initialize queue
    $queue_data = array(
        'status' => 'pending',
        'directories' => array(
            ABSPATH . 'wp-admin',
            ABSPATH . 'wp-content', 
            ABSPATH . 'wp-includes'
        ),
        'current_dir_index' => 0,
        'current_dir_position' => 0,
        'total_size' => 0,
        'started_at' => current_time('mysql'),
        'last_processed' => current_time('mysql')
    );
    
    update_option('iu_storage_queue_status', $queue_data);
    
    // Process first batch immediately if possible
    iu_maybe_process_queue_batch();
}

// Auto-trigger function that runs on various WordPress hooks
function iu_maybe_process_queue_batch() {
    $queue_data = get_option('iu_storage_queue_status', false);
    
    // No queue or already completed
    if (!$queue_data || $queue_data['status'] !== 'pending') {
        return;
    }
    
    // Check if we should process (avoid too frequent processing)
    $last_check = get_transient('iu_queue_last_check');
    $last_processed = strtotime($queue_data['last_processed']);
    $time_since_last = time() - $last_processed;
    
    // If last check was recent AND last processing was less than 30 seconds ago, skip
    if ($last_check && $time_since_last < 30) {
        return;
    }
    
    // Set transient to prevent frequent checks (5 seconds for faster processing)
    set_transient('iu_queue_last_check', time(), 5);
    
    // Check if queue is stale (older than 1 hour) - restart it
    $started_time = strtotime($queue_data['started_at']);
    if ((time() - $started_time) > 3600) {
        iu_start_queue_storage_calculation();
        return;
    }
    
    // Process batch
    iu_process_queue_batch();
}

// Process one batch from the queue
function iu_process_queue_batch() {
    $queue_data = get_option('iu_storage_queue_status', false);
    
    if (!$queue_data || $queue_data['status'] !== 'pending') {
        return;
    }
    
    // Debug logging
    error_log('ISTODATA Utilities: Processing queue batch - dir ' . $queue_data['current_dir_index'] . ', pos ' . $queue_data['current_dir_position'] . ', size ' . $queue_data['total_size']);
    
    // Safety limits
    @ini_set('max_execution_time', 60);
    @ini_set('memory_limit', '256M');
    
    $start_time = time();
    $max_execution_time = 45; // 45 seconds to leave buffer
    $batch_size = 500; // Smaller batches for auto-processing
    $processed_files = 0;
    
    $directories = $queue_data['directories'];
    $current_dir_index = $queue_data['current_dir_index'];
    $current_dir_position = $queue_data['current_dir_position'];
    $total_size = $queue_data['total_size'];
    
    try {
        // Process current directory
        if ($current_dir_index < count($directories)) {
            $directory = $directories[$current_dir_index];
            
            if (is_dir($directory)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                $file_count = 0;
                foreach ($iterator as $file) {
                    // Skip to our current position
                    if ($file_count < $current_dir_position) {
                        $file_count++;
                        continue;
                    }
                    
                    if ($file->isFile()) {
                        $total_size += $file->getSize();
                        $processed_files++;
                        
                        // Check if we should take a break
                        if ($processed_files >= $batch_size || (time() - $start_time) >= $max_execution_time) {
                            break;
                        }
                    }
                    $file_count++;
                }
                
                // Update position
                $current_dir_position = $file_count;
                
                // Check if directory is complete
                if ($processed_files < $batch_size && (time() - $start_time) < $max_execution_time) {
                    // Directory completed, move to next
                    $current_dir_index++;
                    $current_dir_position = 0;
                }
            } else {
                // Directory doesn't exist, skip to next
                $current_dir_index++;
                $current_dir_position = 0;
            }
        }
        
        // Update queue status
        if ($current_dir_index >= count($directories)) {
            // Calculation complete - store with full breakdown
            $result = iu_store_storage_calculation($total_size);
            delete_option('iu_storage_queue_status');
            
            error_log('ISTODATA Utilities: Queue-based storage calculation completed. Files: ' . iu_format_bytes($total_size) . ', Database: ' . iu_format_bytes($result['database']) . ', Total: ' . iu_format_bytes($result['total']));
        } else {
            // Update progress
            $queue_data['current_dir_index'] = $current_dir_index;
            $queue_data['current_dir_position'] = $current_dir_position;
            $queue_data['total_size'] = $total_size;
            $queue_data['last_processed'] = current_time('mysql');
            
            update_option('iu_storage_queue_status', $queue_data);
        }
        
    } catch (Exception $e) {
        error_log('ISTODATA Utilities: Queue batch error: ' . $e->getMessage());
        
        // Skip problematic directory and continue
        $queue_data['current_dir_index'] = $current_dir_index + 1;
        $queue_data['current_dir_position'] = 0;
        $queue_data['last_processed'] = current_time('mysql');
        
        update_option('iu_storage_queue_status', $queue_data);
    }
}

// Hook the queue processor to various WordPress actions for auto-execution
add_action('wp_loaded', 'iu_maybe_process_queue_batch');
add_action('admin_init', 'iu_maybe_process_queue_batch');
add_action('wp_ajax_heartbeat', 'iu_maybe_process_queue_batch');
add_action('admin_head', 'iu_maybe_process_queue_batch');
add_action('wp_head', 'iu_maybe_process_queue_batch');
add_action('admin_footer', 'iu_maybe_process_queue_batch');

// Also hook to AJAX actions to process during AJAX requests
add_action('wp_ajax_nopriv_heartbeat', 'iu_maybe_process_queue_batch');

// Add a direct trigger for the settings page to ensure processing
add_action('load-settings_page_istodata-utilities', 'iu_force_queue_check');

function iu_force_queue_check() {
    // Force check queue without transient restriction
    $queue_data = get_option('iu_storage_queue_status', false);
    
    if ($queue_data && $queue_data['status'] === 'pending') {
        // Check if queue hasn't been processed for more than 1 minute
        $last_processed = strtotime($queue_data['last_processed']);
        $time_since_last = time() - $last_processed;
        
        if ($time_since_last > 60) {
            // Clear the transient to allow immediate processing
            delete_transient('iu_queue_last_check');
            iu_process_queue_batch();
        }
    }
}

// ==========================================
// AUTO UPDATE SYSTEM
// ==========================================

// Plugin update constants - GitHub based
define('IU_GITHUB_API_URL', 'https://api.github.com/repos/istodata/istodata-utilities/releases/latest');
define('IU_GITHUB_REPO_URL', 'https://github.com/istodata/istodata-utilities');
define('IU_PLUGIN_SLUG', 'istodata-utilities');
define('IU_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Hook into WordPress update system
add_filter('pre_set_site_transient_update_plugins', 'iu_check_for_plugin_update');
add_filter('plugins_api', 'iu_plugin_api_call', 10, 3);
add_action('upgrader_process_complete', 'iu_purge_update_cache', 10, 2);

// Improve update reliability
add_filter('upgrader_package_options', 'iu_upgrader_package_options');
add_filter('http_request_args', 'iu_http_request_args', 10, 2);

// Check for plugin updates
function iu_check_for_plugin_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    // Get remote version info
    $remote_version = iu_get_remote_version();
    
    if ($remote_version && version_compare(IU_PLUGIN_VERSION, $remote_version->new_version, '<')) {
        $transient->response[IU_PLUGIN_BASENAME] = (object) array(
            'slug' => IU_PLUGIN_SLUG,
            'plugin' => IU_PLUGIN_BASENAME,
            'new_version' => $remote_version->new_version,
            'url' => $remote_version->homepage,
            'package' => $remote_version->download_url,
            'tested' => $remote_version->tested,
            'requires_php' => $remote_version->requires_php,
        );
    }
    
    return $transient;
}

// Get remote version information from GitHub
function iu_get_remote_version() {
    // Check cache first
    $cache_key = 'iu_remote_version_' . md5(IU_GITHUB_API_URL);
    $cache_data = get_transient($cache_key);
    
    if ($cache_data !== false) {
        return $cache_data;
    }
    
    // Fetch GitHub release data
    $request = wp_remote_get(IU_GITHUB_API_URL, array(
        'timeout' => 15,
        'sslverify' => true,
        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_site_url(),
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json'
        )
    ));
    
    if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
        $body = wp_remote_retrieve_body($request);
        $github_data = json_decode($body);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($github_data->tag_name)) {
            // Convert GitHub release data to WordPress format
            $version = ltrim($github_data->tag_name, 'v'); // Remove 'v' prefix if present
            
            // Find ZIP asset
            $download_url = '';
            if (isset($github_data->assets) && is_array($github_data->assets)) {
                foreach ($github_data->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }
            
            // Fallback to source code ZIP if no asset found
            if (empty($download_url)) {
                $download_url = $github_data->zipball_url;
            }
            
            $data = (object) array(
                'new_version' => $version,
                'name' => 'ISTODATA Utilities',
                'slug' => IU_PLUGIN_SLUG,
                'author' => 'ISTODATA',
                'homepage' => IU_GITHUB_REPO_URL,
                'requires' => '5.0',
                'tested' => '6.4',
                'requires_php' => '7.4',
                'last_updated' => $github_data->published_at,
                'description' => 'Εργαλεία διαχείρισης, βελτιστοποιήσεις και πρόσθετες λειτουργίες από την ISTODATA.',
                'changelog' => isset($github_data->body) ? $github_data->body : 'See GitHub release notes.',
                'download_url' => $download_url
            );
            
            // Cache for 12 hours
            set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
            return $data;
        }
    }
    
    return false;
}

// Improve package download reliability
function iu_upgrader_package_options($options) {
    // Increase timeout for package downloads
    $options['timeout'] = 300; // 5 minutes
    $options['clear_destination'] = true;
    return $options;
}

// Improve HTTP request reliability for GitHub API
function iu_http_request_args($args, $url) {
    // Only modify requests to GitHub API or download URLs
    if (strpos($url, 'api.github.com') !== false || strpos($url, 'github.com') !== false) {
        $args['timeout'] = 60; // Increase timeout
        $args['sslverify'] = true; // Keep SSL verification for GitHub
        $args['httpversion'] = '1.1'; // Force HTTP 1.1
        $args['user-agent'] = 'WordPress/' . get_bloginfo('version') . '; ' . get_site_url() . '; ISTODATA-Utilities/' . IU_PLUGIN_VERSION;
        $args['headers']['Accept'] = 'application/vnd.github.v3+json';
    }
    return $args;
}

// Handle plugin API calls for update information
function iu_plugin_api_call($result, $action, $args) {
    if ($action !== 'plugin_information' || $args->slug !== IU_PLUGIN_SLUG) {
        return $result;
    }
    
    $remote_version = iu_get_remote_version();
    
    if (!$remote_version) {
        return $result;
    }
    
    return (object) array(
        'name' => $remote_version->name,
        'slug' => $remote_version->slug,
        'version' => $remote_version->new_version,
        'author' => $remote_version->author,
        'homepage' => $remote_version->homepage,
        'requires' => $remote_version->requires,
        'tested' => $remote_version->tested,
        'requires_php' => $remote_version->requires_php,
        'last_updated' => $remote_version->last_updated,
        'sections' => array(
            'description' => $remote_version->description,
            'changelog' => $remote_version->changelog,
        ),
        'download_link' => $remote_version->download_url,
    );
}

// Clear update cache after plugin update
function iu_purge_update_cache($upgrader, $options) {
    if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        if (isset($options['plugins']) && in_array(IU_PLUGIN_BASENAME, $options['plugins'])) {
            $cache_key = 'iu_remote_version_' . md5(IU_GITHUB_API_URL);
            delete_transient($cache_key);
        }
    }
}

// Auto-reactivate plugin after update
add_action('upgrader_process_complete', 'iu_auto_reactivate_plugin', 20, 2);

// Debug update process
add_action('upgrader_process_complete', 'iu_debug_update_process', 5, 2);

function iu_debug_update_process($upgrader, $options) {
    if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        if (isset($options['plugins']) && in_array(IU_PLUGIN_BASENAME, $options['plugins'])) {
            $success = !is_wp_error($upgrader->result);
            $version_after = get_plugin_data(__FILE__)['Version'];
            
            error_log('ISTODATA Utilities Update Debug: Success=' . ($success ? 'YES' : 'NO') . ', Version After=' . $version_after);
            
            if (!$success && is_wp_error($upgrader->result)) {
                error_log('ISTODATA Utilities Update Error: ' . $upgrader->result->get_error_message());
            }
        }
    }
}

function iu_auto_reactivate_plugin($upgrader, $options) {
    if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        if (isset($options['plugins']) && in_array(IU_PLUGIN_BASENAME, $options['plugins'])) {
            // Check if update was successful
            if (!is_wp_error($upgrader->result)) {
                // Schedule reactivation to happen after WordPress finishes the update process
                wp_schedule_single_event(time() + 2, 'iu_delayed_reactivation');
            }
        }
    }
}

// Handle delayed reactivation
add_action('iu_delayed_reactivation', 'iu_delayed_reactivation_handler');

function iu_delayed_reactivation_handler() {
    // Check if plugin file exists before reactivating
    if (file_exists(WP_PLUGIN_DIR . '/' . IU_PLUGIN_BASENAME)) {
        $result = activate_plugin(IU_PLUGIN_BASENAME);
        if (is_wp_error($result)) {
            error_log('ISTODATA Utilities: Failed to reactivate after update - ' . $result->get_error_message());
        } else {
            error_log('ISTODATA Utilities: Successfully reactivated after update');
        }
    } else {
        error_log('ISTODATA Utilities: Plugin file not found for reactivation: ' . WP_PLUGIN_DIR . '/' . IU_PLUGIN_BASENAME);
    }
}

// Force show auto-update option even when no update available
add_filter('plugin_auto_update_setting_html', 'iu_show_auto_update_option', 10, 3);
add_action('admin_init', 'iu_handle_auto_update_actions');

function iu_show_auto_update_option($html, $plugin_file, $plugin_data) {
    if ($plugin_file === IU_PLUGIN_BASENAME) {
        $auto_updates = get_site_option('auto_update_plugins', array());
        $is_enabled = in_array($plugin_file, $auto_updates);
        
        $nonce = wp_create_nonce('updates');
        $toggle_text = $is_enabled ? __('Disable auto-updates') : __('Enable auto-updates');
        $action = $is_enabled ? 'disable-auto-update' : 'enable-auto-update';
        
        $url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => $action,
                    'plugin' => $plugin_file
                ),
                admin_url('update.php')
            ),
            'updates'
        );
        
        return sprintf(
            '<a href="%s" class="auto-update-link">%s</a>',
            esc_url($url),
            esc_html($toggle_text)
        );
    }
    return $html;
}

// Handle auto-update enable/disable actions
function iu_handle_auto_update_actions() {
    if (!current_user_can('update_plugins')) {
        return;
    }
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $plugin = isset($_GET['plugin']) ? $_GET['plugin'] : '';
    
    if (($action === 'enable-auto-update' || $action === 'disable-auto-update') && $plugin === IU_PLUGIN_BASENAME) {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'updates')) {
            wp_die(__('Security check failed.'));
        }
        
        $auto_updates = get_site_option('auto_update_plugins', array());
        
        if ($action === 'enable-auto-update') {
            if (!in_array($plugin, $auto_updates)) {
                $auto_updates[] = $plugin;
                update_site_option('auto_update_plugins', $auto_updates);
            }
            $message = __('Auto-updates enabled for ISTODATA Utilities.');
        } else {
            $auto_updates = array_diff($auto_updates, array($plugin));
            update_site_option('auto_update_plugins', $auto_updates);
            $message = __('Auto-updates disabled for ISTODATA Utilities.');
        }
        
        // Redirect back to plugins page with success message
        wp_redirect(add_query_arg(
            array('auto-update-message' => urlencode($message)),
            admin_url('plugins.php')
        ));
        exit;
    }
}

// Show auto-update message on plugins page
add_action('admin_notices', 'iu_show_auto_update_message');

function iu_show_auto_update_message() {
    if (isset($_GET['auto-update-message'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(urldecode($_GET['auto-update-message'])) . '</p></div>';
    }
}

// ==========================================
// REAL-TIME STORAGE TRACKING
// ==========================================

// Hook into file upload/delete events
add_action('wp_handle_upload', 'iu_update_storage_on_upload', 10, 2);
add_action('wp_generate_attachment_metadata', 'iu_update_storage_after_thumbnails', 10, 2);
add_action('delete_attachment', 'iu_update_storage_on_delete');
add_action('wp_ajax_delete-post', 'iu_handle_ajax_delete', 1);
add_action('wp_ajax_nopriv_delete-post', 'iu_handle_ajax_delete', 1);

// Prevent uploads when storage limit is reached
add_filter('wp_handle_upload_prefilter', 'iu_check_storage_limit_before_upload');
add_filter('upload_size_limit', 'iu_adjust_upload_limit_based_on_storage');

// Update storage when file is uploaded (only main file)
function iu_update_storage_on_upload($upload, $context = '') {
    if (isset($upload['file']) && file_exists($upload['file'])) {
        $file_size = filesize($upload['file']);
        iu_add_to_cached_storage($file_size);
    }
    return $upload;
}

// Update storage after thumbnails are generated
function iu_update_storage_after_thumbnails($metadata, $attachment_id) {
    if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
        $file_path = get_attached_file($attachment_id);
        if ($file_path) {
            $base_dir = dirname($file_path);
            $thumbnails_size = 0;
            
            foreach ($metadata['sizes'] as $size_data) {
                if (isset($size_data['file'])) {
                    $thumb_path = $base_dir . '/' . $size_data['file'];
                    if (file_exists($thumb_path)) {
                        $thumb_size = filesize($thumb_path);
                        $thumbnails_size += $thumb_size;
                    }
                }
            }
            
            if ($thumbnails_size > 0) {
                iu_add_to_cached_storage($thumbnails_size);
            }
        }
    }
    
    return $metadata;
}

// Handle AJAX delete requests (captures before actual deletion)
function iu_handle_ajax_delete() {
    if (isset($_POST['id'])) {
        $post_id = intval($_POST['id']);
        if (get_post_type($post_id) === 'attachment') {
            iu_store_attachment_size_before_delete($post_id);
        }
    }
}

// Store attachment sizes before deletion
function iu_store_attachment_size_before_delete($post_id) {
    $file_path = get_attached_file($post_id);
    $total_size = 0;
    
    // Get main file size
    if ($file_path && file_exists($file_path)) {
        $total_size += filesize($file_path);
        
        // Get thumbnail sizes
        $metadata = wp_get_attachment_metadata($post_id);
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $base_dir = dirname($file_path);
            
            foreach ($metadata['sizes'] as $size_data) {
                if (isset($size_data['file'])) {
                    $thumb_path = $base_dir . '/' . $size_data['file'];
                    if (file_exists($thumb_path)) {
                        $thumb_size = filesize($thumb_path);
                        $total_size += $thumb_size;
                    }
                }
            }
        }
    }
    
    // Store the total size temporarily
    if ($total_size > 0) {
        set_transient('iu_deleting_attachment_' . $post_id, $total_size, 300); // 5 minutes
    }
}

// Update storage when attachment is deleted
function iu_update_storage_on_delete($attachment_id) {
    
    // Try to get stored size first
    $stored_size = get_transient('iu_deleting_attachment_' . $attachment_id);
    
    if ($stored_size !== false) {
        iu_subtract_from_cached_storage($stored_size);
        delete_transient('iu_deleting_attachment_' . $attachment_id);
        return;
    }
    
    // Fallback: try to calculate size if files still exist
    $file_path = get_attached_file($attachment_id);
    $total_size = 0;
    
    if ($file_path && file_exists($file_path)) {
        $total_size += filesize($file_path);
        
        // Also handle thumbnails
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $base_dir = dirname($file_path);
            
            foreach ($metadata['sizes'] as $size_data) {
                if (isset($size_data['file'])) {
                    $thumb_path = $base_dir . '/' . $size_data['file'];
                    if (file_exists($thumb_path)) {
                        $total_size += filesize($thumb_path);
                    }
                }
            }
        }
        
        if ($total_size > 0) {
            iu_subtract_from_cached_storage($total_size);
        }
    } else {
    }
}

// Add bytes to cached storage
function iu_add_to_cached_storage($bytes) {
    $current_usage = get_option('iu_storage_used', 0);
    $new_usage = $current_usage + $bytes;
    update_option('iu_storage_used', $new_usage);
    update_option('iu_storage_last_updated', current_time('mysql'));
}

// Subtract bytes from cached storage
function iu_subtract_from_cached_storage($bytes) {
    $current_usage = get_option('iu_storage_used', 0);
    $new_usage = max(0, $current_usage - $bytes); // Don't go below 0
    update_option('iu_storage_used', $new_usage);
    update_option('iu_storage_last_updated', current_time('mysql'));
}

// Check storage limit before upload
function iu_check_storage_limit_before_upload($file) {
    // Only check if hosting option is enabled
    $settings = get_option('istodata_utilities_settings', array());
    if (empty($settings['hosting']['istodata_hosted'])) {
        return $file;
    }
    
    $storage_limit_gb = isset($settings['hosting']['storage_limit']) ? floatval($settings['hosting']['storage_limit']) : 5.0;
    $storage_limit_bytes = $storage_limit_gb * 1024 * 1024 * 1024; // Convert to bytes
    
    $breakdown = iu_get_storage_breakdown();
    $file_size = $file['size'];
    
    // Check if this upload would exceed the limit
    if (($breakdown['total'] + $file_size) > $storage_limit_bytes) {
        $available_space = max(0, $storage_limit_bytes - $breakdown['total']);
        
        $file['error'] = sprintf(
            'Upload failed: Storage limit exceeded. Available space: %s, File size: %s. Please delete some files or contact ISTODATA to increase your storage limit.',
            iu_format_bytes($available_space),
            iu_format_bytes($file_size)
        );
    }
    
    return $file;
}

// Adjust upload size limit based on available storage
function iu_adjust_upload_limit_based_on_storage($limit) {
    // Only check if hosting option is enabled
    $settings = get_option('istodata_utilities_settings', array());
    if (empty($settings['hosting']['istodata_hosted'])) {
        return $limit;
    }
    
    $storage_limit_gb = isset($settings['hosting']['storage_limit']) ? floatval($settings['hosting']['storage_limit']) : 5.0;
    $storage_limit_bytes = $storage_limit_gb * 1024 * 1024 * 1024;
    
    $breakdown = iu_get_storage_breakdown();
    $available_space = max(0, $storage_limit_bytes - $breakdown['total']);
    
    // Return the smaller of the original limit or available space
    return min($limit, $available_space);
}
