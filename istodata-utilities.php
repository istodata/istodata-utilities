<?php
/*
Plugin Name: ISTODATA Kit
Description: Εργαλεία διαχείρισης, βελτιστοποιήσεις και πρόσθετες λειτουργίες από την ISTODATA.
Version: 2.19.7
Author: <a href="https://www.istodata.com/" target="_blank">ISTODATA</a>
Text Domain: istodata-utilities
*/

if (!defined('ABSPATH')) {
    exit;
}

define('IU_PLUGIN_VERSION', '2.19.7');
define('IU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IU_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Reusable helpers for Elementor-related setting keys
    if (!function_exists('iu_elem_opt_keys')) {
        function iu_elem_opt_keys() {
            return array(
                'disable_elementor_upsells',
                'elementor_remove_profile_ai_notes',
                'elementor_accordion_scroll_to_active',
                'elementor_animations',
                'elementor_additional_animations',
                'elementor_disable_mobile_animations',
                'elementor_mobile_anim_per_element',
                // legacy location of social share toggle
                'elementor_social_share_widget',
            );
        }
    }
if (!function_exists('iu_elem_add_bool_keys')) {
    function iu_elem_add_bool_keys() {
        return array(
            'elementor_device_visibility',
            'scroll_to_top',
            'elementor_reading_time',
            'elementor_google_maps_widget',
            'elementor_wpml_language_switcher',
            'elementor_wpml_shortcode_widget',
            'elementor_social_share_widget',
            'elementor_heading_group_widget',
            'elementor_typed_widget',
            'elementor_taxonomy_links_widget',
            'elementor_query_posts_widget',
            'acf_simple_repeater',
            'elementor_simple_repeater_widget',
            'elementor_image_gallery',
        );
    }
}
if (!function_exists('iu_elem_add_array_keys')) {
    function iu_elem_add_array_keys() {
        return array(
            'elementor_image_gallery_post_types',
        );
    }
}
if (!function_exists('iu_is_acf_available')) {
    function iu_is_acf_available() {
        return class_exists('ACF') || class_exists('acf') || function_exists('acf') || function_exists('get_field');
    }
}

// Elementor integration: device visibility controls and server-side rendering guards
function iu_maybe_load_elementor_integration() {
    // Load only once
    static $loaded = false;
    if ($loaded) return;

    // Detect Elementor availability
    if (did_action('elementor/loaded') || class_exists('Elementor\\Plugin') || defined('ELEMENTOR_VERSION')) {
        // Evaluate settings to decide which Elementor integrations to load
        $settings = get_option('istodata_utilities_settings', array());
        $additional = isset($settings['additional']) ? $settings['additional'] : array();
        $optimizations = isset($settings['optimizations']) ? $settings['optimizations'] : array();

        // Device Visibility toggle: default ON if not set
        $device_vis_enabled = !isset($additional['elementor_device_visibility']) || !empty($additional['elementor_device_visibility']);

        if ($device_vis_enabled) {
            $devvis = IU_PLUGIN_PATH . 'includes/elementor-device-visibility.php';
            if (file_exists($devvis)) {
                require_once $devvis;
                $loaded = true;
            }
        }
        
        // Register custom Elementor category only if at least one ISTODATA widget is enabled
        $has_iu_widget = (!empty($additional['elementor_google_maps_widget']))
                      || (!empty($additional['elementor_wpml_language_switcher']) && is_plugin_active('sitepress-multilingual-cms/sitepress.php'))
                      || (!empty($additional['elementor_wpml_shortcode_widget']) && is_plugin_active('sitepress-multilingual-cms/sitepress.php'))
                      || (!empty($additional['elementor_social_share_widget']))
                      || (!empty($additional['elementor_heading_group_widget']))
                      || (!empty($additional['elementor_typed_widget']))
                      || (!empty($additional['elementor_taxonomy_links_widget']))
                      || (!empty($additional['elementor_query_posts_widget']))
                      || (!empty($additional['elementor_simple_repeater_widget']))
                      || (!empty($additional['scroll_to_top']))
                      || (!empty($optimizations['elementor_social_share_widget'])); // legacy key support
        if ($has_iu_widget) {
            add_action('elementor/elements/categories_registered', function($elements_manager){
                if (method_exists($elements_manager, 'add_category')) {
                    $elements_manager->add_category('istodata-kit', array(
                        'title' => __('ISTODATA Kit', 'istodata-utilities'),
                        'icon'  => 'eicon-kit-details',
                    ));
                }
            });
        }
        $share = IU_PLUGIN_PATH . 'includes/elementor-social-share-widget.php';
        if (file_exists($share)) {
            require_once $share;
        }
        $gmap = IU_PLUGIN_PATH . 'includes/elementor-google-maps-widget.php';
        if (file_exists($gmap)) {
            require_once $gmap;
        }
        $wpml_switcher = IU_PLUGIN_PATH . 'includes/elementor-wpml-language-switcher.php';
        if (file_exists($wpml_switcher)) {
            require_once $wpml_switcher;
        }
        $wpml_shortcode = IU_PLUGIN_PATH . 'includes/elementor-wpml-shortcode-widget.php';
        if (file_exists($wpml_shortcode)) {
            require_once $wpml_shortcode;
        }
        // Elementor Dynamic Tag: Scroll To Top URL
        if (!empty($additional['scroll_to_top'])) {
            $scroll_tag = IU_PLUGIN_PATH . 'includes/elementor-scroll-to-top-tag.php';
            if (file_exists($scroll_tag)) {
                require_once $scroll_tag;
            }
        }
        $heading_group = IU_PLUGIN_PATH . 'includes/elementor-heading-group-widget.php';
        if (file_exists($heading_group)) {
            require_once $heading_group;
        }
        $typed_widget = IU_PLUGIN_PATH . 'includes/elementor-typed-widget.php';
        if (file_exists($typed_widget)) {
            require_once $typed_widget;
        }
        $taxonomy_links_widget = IU_PLUGIN_PATH . 'includes/elementor-taxonomy-links-widget.php';
        if (file_exists($taxonomy_links_widget)) {
            require_once $taxonomy_links_widget;
        }
        $query_posts_widget = IU_PLUGIN_PATH . 'includes/elementor-query-posts-widget.php';
        if (file_exists($query_posts_widget)) {
            require_once $query_posts_widget;
        }
        $simple_repeater_widget = IU_PLUGIN_PATH . 'includes/elementor-simple-repeater-widget.php';
        if (file_exists($simple_repeater_widget)) {
            require_once $simple_repeater_widget;
        }
        // Scroll To Top widget (Elementor)
        if (!empty($additional['scroll_to_top'])) {
            $stt_widget = IU_PLUGIN_PATH . 'includes/elementor-scroll-to-top-widget.php';
            if (file_exists($stt_widget)) {
                require_once $stt_widget;
            }
        }
        $gallery_tag = IU_PLUGIN_PATH . 'includes/elementor-gallery-dynamic-tag.php';
        if (file_exists($gallery_tag)) {
            require_once $gallery_tag;
        }

        // Optional per-element mobile animation toggle (requires WP Rocket)
        if (defined('WP_ROCKET_VERSION')) {
            if (!empty($optimizations['elementor_mobile_anim_per_element'])) {
                $per_el = IU_PLUGIN_PATH . 'includes/elementor-mobile-anim-toggle.php';
                if (file_exists($per_el)) {
                    require_once $per_el;
                }
            }
        }
    }
}
// Try on general plugin load (after most plugins), and also when Elementor fires its ready hook
add_action('plugins_loaded', 'iu_maybe_load_elementor_integration', 20);
add_action('elementor/loaded', 'iu_maybe_load_elementor_integration');

function iu_maybe_load_acf_simple_repeater() {
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    if (empty($additional['acf_simple_repeater'])) {
        return;
    }

    $field = IU_PLUGIN_PATH . 'includes/acf-simple-repeater-field.php';
    if (file_exists($field)) {
        require_once $field;
    }
}
add_action('plugins_loaded', 'iu_maybe_load_acf_simple_repeater', 25);
add_action('acf/init', 'iu_maybe_load_acf_simple_repeater', 5);

// Simple multilingual system - no translation files needed

// WP Rocket: globally exclude our Heading Group JS from Delay JS, only when widget is enabled
add_filter('rocket_delay_js_exclusions', function($patterns){
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    if (!empty($additional['elementor_heading_group_widget'])) {
        $patterns[] = 'heading-group.js';
        // Optional: also add full plugin path pattern for robustness
        $patterns[] = 'istodata-utilities/assets/js/heading-group.js';
    }
    return $patterns;
});

// WP Rocket RUCSS: safelist our Heading Group classes and keyframes when widget is enabled
add_filter('rocket_rucss_safelist', function($safelist){
    $safelist = is_array($safelist) ? $safelist : array();
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    if (!empty($additional['elementor_heading_group_widget'])) {
        $keep = array(
            '.iu-heading-group',
            '.iu-heading-group--stagger',
            '.iu-hg-play',
            '.iu-hg-dir-fade',
            '.iu-hg-dir-up',
            '.iu-hg-dir-down',
            '.iu-hg-dir-left',
            '.iu-hg-dir-right',
            '.iu-heading-group .iu-overline',
            '.iu-heading-group .iu-heading',
            '.iu-heading-group .iu-text',
            // Keyframes identifier (varies by implementation; include both notations)
            'iu-hg-fade-up',
            '@keyframes iu-hg-fade-up',
        );
        $safelist = array_merge($safelist, $keep);
    }
    // Safelist Typed widget classes so RUCSS doesn't remove their rules
    if (!empty($additional['elementor_typed_widget'])) {
        $safelist = array_merge($safelist, array(
            '.iu-typed-wrapper',
            '.iu-typed__icon',
        ));
    }
    return $safelist;
});

// Simple activation hook  
register_activation_hook(__FILE__, 'iu_activate');
function iu_activate() {
    $default_settings = array(
        'hosting' => array(
            'istodata_hosted' => true,
            'storage_limit' => 5.0,
            'observations' => '',
            'site_suspended' => false
        ),
        'dashboard' => array(
            'remove_welcome' => false,
            'remove_activity' => false,
            'remove_quick_draft' => false,
            'remove_news' => false,
            'remove_site_health' => false,
            'remove_at_glance' => false,
            'remove_woocommerce_setup' => false,
            'remove_woocommerce_recent_reviews' => false,
            'remove_woocommerce_status' => false,
            'remove_elementor_overview' => false,
            'remove_elementor_accessibility' => false,
            'remove_elementor_manage_dashboard' => true,
            'remove_qode_news' => false,
            'remove_avada_news' => false,
            'remove_premium_addons_news' => false,
            'remove_rank_math_overview' => false,
            'remove_smash_balloon_feeds' => false,
            'remove_wpmet_stories' => false,
            'remove_object_cache_pro' => false,
            'remove_cookie_compliance' => false,
            'remove_yoast_posts_overview' => false,
            'remove_yoast_wincher_overview' => false,
            'remove_wp_mail_smtp' => false,
            'remove_wpforms' => false,
            'remove_yith_updates' => false,
            'remove_yith_blog_news' => false,
            'remove_fluent_forms' => false,
            'remove_pixelwars' => false,
            'remove_siteorigin_news' => false,
            'remove_themeisle' => false,
            'remove_webappick_news' => false,
            'remove_quadlayers_news' => false
        ),
        'optimizations' => array(
            'disable_emojis' => false,
            'disable_gutenberg' => false,
            'disable_comments' => false,
            'remove_dashicons' => false,
            'move_jquery_to_footer' => false,
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
            'remove_attributes' => false,
            'remove_format' => false,
            'remove_tags' => false,
            'remove_cookiebanner' => false,
            'disable_auto_updates' => false,
            'disable_auto_update_emails' => false,
            'disable_search' => false,
            'disable_author_archives' => false,
            'disable_elementor_upsells' => false,
            'elementor_remove_profile_ai_notes' => false,
            'elementor_social_share_widget' => false,
            'elementor_animations' => false,
            'elementor_additional_animations' => false,
            'elementor_accordion_scroll_to_active' => false,
            'elementor_mobile_anim_per_element' => false
        ),
        'woocommerce' => array(
            'disable_product_tags' => false,
            'hide_shipping_when_free' => false,
            'enable_sku_search' => false,
            'enable_brand_custom_order' => false,
            'enable_attributes_in_menus' => false,
            'husky_archive_assets_only' => false,
            'remove_wc_blocks_css' => false,
            // Catalog Mode defaults
            'catalog_remove_add_to_cart_archive' => false,
            'catalog_remove_add_to_cart_single' => false,
            'catalog_hide_prices' => false,
            'catalog_disable_cart_checkout' => false,
            'catalog_disable_add_to_cart' => false,
            'catalog_remove_wc_css' => false,
            'catalog_remove_wc_js' => false
        ),
        'additional' => array(
            'greeklish_permalinks' => false,
            'duplicate_post_link' => false,
            'protect_content' => false,
            'scroll_to_top' => false,
            'elementor_device_visibility' => true,
            'elementor_reading_time' => false,
            'elementor_social_share_widget' => false,
            'elementor_google_maps_widget' => false,
            'elementor_wpml_language_switcher' => false,
            'elementor_wpml_shortcode_widget' => false,
            'elementor_heading_group_widget' => false,
            'elementor_typed_widget' => false,
            'elementor_taxonomy_links_widget' => false,
            'elementor_query_posts_widget' => false,
            'acf_simple_repeater' => false,
            'elementor_simple_repeater_widget' => false,
            'elementor_image_gallery' => false,
            'elementor_image_gallery_post_types' => array(),
            'rank_math_remove_categories' => false,
            'typed_js' => false,
            'typed_js_wp_rocket_exclude' => false
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

// Enable the new Elementor Manage Dashboard cleanup once on existing installations.
add_action('admin_init', 'iu_migrate_elementor_manage_dashboard_default');
function iu_migrate_elementor_manage_dashboard_default() {
    $migration_key = 'iu_migration_elementor_manage_dashboard_default';

    if (get_option($migration_key, false)) {
        return;
    }

    $settings = get_option('istodata_utilities_settings', array());

    if (!isset($settings['dashboard']) || !is_array($settings['dashboard'])) {
        $settings['dashboard'] = array();
    }

    $settings['dashboard']['remove_elementor_manage_dashboard'] = true;
    update_option('istodata_utilities_settings', $settings);
    update_option($migration_key, 1, false);
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
        'ISTODATA Kit',
        'ISTODATA Kit', 
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
            
            // If hosting is disabled, automatically disable site suspension
            if (!isset($existing_settings['hosting']['istodata_hosted']) || !$existing_settings['hosting']['istodata_hosted']) {
                $existing_settings['hosting']['site_suspended'] = false;
            }
        } elseif ($submitted_tab == 'dashboard') {
            $existing_settings['dashboard'] = isset($new_settings['dashboard']) ? $new_settings['dashboard'] : array();
        } elseif ($submitted_tab == 'optimizations' || $submitted_tab == 'wordpress') {
            // Preserve Elementor-related optimization keys when saving the WordPress tab
            $prev_opt = isset($existing_settings['optimizations']) ? $existing_settings['optimizations'] : array();
            $incoming_opt = isset($new_settings['optimizations']) ? $new_settings['optimizations'] : array();

            // Keys managed by the Elementor tab
            $elementor_opt_keys = iu_elem_opt_keys();

            // Start from the incoming (current tab) values
            $existing_settings['optimizations'] = is_array($incoming_opt) ? $incoming_opt : array();

            // Re-apply preserved Elementor-related values (not managed in this tab)
            foreach ($elementor_opt_keys as $k) {
                if (array_key_exists($k, $prev_opt)) {
                    $existing_settings['optimizations'][$k] = $prev_opt[$k];
                }
            }
        } elseif ($submitted_tab == 'woocommerce') {
            $existing_settings['woocommerce'] = isset($new_settings['woocommerce']) ? $new_settings['woocommerce'] : array();
        } elseif ($submitted_tab == 'additional') {
            // Preserve Elementor-related additional keys when saving the Additional tab
            $prev_add = isset($existing_settings['additional']) ? $existing_settings['additional'] : array();
            $incoming_add = isset($new_settings['additional']) ? $new_settings['additional'] : array();

            // Keys managed by the Elementor tab
            $elementor_add_keys = array_merge(iu_elem_add_bool_keys(), iu_elem_add_array_keys());

            // Start from the incoming (current tab) values
            $existing_settings['additional'] = is_array($incoming_add) ? $incoming_add : array();

            // Re-apply preserved Elementor-related values (not managed in this tab)
            foreach ($elementor_add_keys as $k) {
                if (array_key_exists($k, $prev_add)) {
                    $existing_settings['additional'][$k] = $prev_add[$k];
                }
            }
        } elseif ($submitted_tab == 'elementor') {
            // Merge-update only Elementor-related keys across 'optimizations' and 'additional'
            $existing_settings['optimizations'] = isset($existing_settings['optimizations']) ? $existing_settings['optimizations'] : array();
            $existing_settings['additional'] = isset($existing_settings['additional']) ? $existing_settings['additional'] : array();

            $incoming_opt = isset($new_settings['optimizations']) ? $new_settings['optimizations'] : array();
            $incoming_add = isset($new_settings['additional']) ? $new_settings['additional'] : array();

            // Whitelists
            // Elementor tab manages only a subset of optimization keys (excludes legacy share toggle here)
            $opt_keys = array_values(array_diff(iu_elem_opt_keys(), array('elementor_social_share_widget')));
            $add_keys_bool = iu_elem_add_bool_keys();

            // Update optimization boolean keys
            foreach ($opt_keys as $k) {
                $existing_settings['optimizations'][$k] = !empty($incoming_opt[$k]);
            }

            // Update additional boolean keys
            foreach ($add_keys_bool as $k) {
                $existing_settings['additional'][$k] = !empty($incoming_add[$k]);
            }

            if (!iu_is_acf_available()) {
                $existing_settings['additional']['acf_simple_repeater'] = false;
                $existing_settings['additional']['elementor_simple_repeater_widget'] = false;
            }

            // Keep legacy key in sync for backward compatibility
            if (array_key_exists('elementor_social_share_widget', $existing_settings['additional'])) {
                $existing_settings['optimizations']['elementor_social_share_widget'] = !empty($existing_settings['additional']['elementor_social_share_widget']);
            }

            // Update post types (array)
            $pts = array();
            if (!empty($incoming_add['elementor_image_gallery_post_types']) && is_array($incoming_add['elementor_image_gallery_post_types'])) {
                foreach ($incoming_add['elementor_image_gallery_post_types'] as $pt) {
                    $pt = sanitize_key($pt);
                    if ($pt !== '') { $pts[] = $pt; }
                }
            }
            $existing_settings['additional']['elementor_image_gallery_post_types'] = $pts;
        }
        
        update_option('istodata_utilities_settings', $existing_settings);
        echo '<div class="notice notice-success"><p>Οι ρυθμίσεις αποθηκεύτηκαν επιτυχώς!</p></div>';
    }
    
    $settings = get_option('istodata_utilities_settings', array());
    ?>
    <div class="wrap">
        <h1>ISTODATA Kit</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=istodata-utilities&tab=hosting" class="nav-tab <?php echo $active_tab == 'hosting' ? 'nav-tab-active' : ''; ?>">Φιλοξενία</a>
            <a href="?page=istodata-utilities&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">Πίνακας Ελέγχου</a>
            <a href="?page=istodata-utilities&tab=optimizations" class="nav-tab <?php echo $active_tab == 'optimizations' ? 'nav-tab-active' : ''; ?>">WordPress</a>
            <?php
            // Conditional Elementor tab (before WooCommerce)
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            if (is_plugin_active('elementor/elementor.php')): ?>
                <a href="?page=istodata-utilities&tab=elementor" class="nav-tab <?php echo $active_tab == 'elementor' ? 'nav-tab-active' : ''; ?>">Elementor</a>
            <?php endif; ?>
            <?php
            // Show WooCommerce tab only if WooCommerce is active
            if (is_plugin_active('woocommerce/woocommerce.php') || class_exists('WooCommerce')):
            ?>
            <a href="?page=istodata-utilities&tab=woocommerce" class="nav-tab <?php echo $active_tab == 'woocommerce' ? 'nav-tab-active' : ''; ?>">WooCommerce</a>
            <?php endif; ?>
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
                    
                    <?php if (isset($settings['hosting']['istodata_hosted']) && $settings['hosting']['istodata_hosted']): ?>
                    <tr>
                        <th scope="row">Όριο Αποθηκευτικού Χώρου (GB)</th>
                        <td>
                            <input type="number" step="0.1" min="0.1" name="istodata_utilities_settings[hosting][storage_limit]" 
                                   value="<?php echo isset($settings['hosting']['storage_limit']) ? esc_attr($settings['hosting']['storage_limit']) : '5.0'; ?>" />
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row">Παρατηρήσεις</th>
                        <td>
                            <textarea name="istodata_utilities_settings[hosting][observations]" 
                                      style="width: 680px;" rows="10"><?php echo isset($settings['hosting']['observations']) ? esc_textarea($settings['hosting']['observations']) : ''; ?></textarea>
                        </td>
                    </tr>
                    <?php if (isset($settings['hosting']['istodata_hosted']) && $settings['hosting']['istodata_hosted']): ?>
                    <tr>
                        <th scope="row">Αναστολή Ιστοσελίδας</th>
                        <td>
                            <label>
                                <input type="checkbox" name="istodata_utilities_settings[hosting][site_suspended]" value="1" 
                                       <?php checked(isset($settings['hosting']['site_suspended']) ? $settings['hosting']['site_suspended'] : false); ?> />
                                <span style="color: #d63638; font-weight: 600;">Αναστολή προσπέλασης της ιστοσελίδας</span>
                            </label>
                            <p class="description" style="color: #666;">Όταν ενεργοποιηθεί, οι επισκέπτες βλέπουν μήνυμα αναστολής αντί για την κανονική ιστοσελίδα.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                </table>
                
                <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                
                <?php if (isset($settings['hosting']['istodata_hosted']) && $settings['hosting']['istodata_hosted']): ?>
                <div id="storage-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-radius: 4px;">
                    <h3 style="margin-top: 0; border-bottom: 1px solid #f5f5f5; padding-bottom: 10px;">Αποθηκευτικός Χώρος</h3>
                    
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
                                if (false) {
                                echo '<br><small style="color: #666;">├── Αρχεία: ' . iu_format_bytes($breakdown['files_with_overhead']) . ' (+' . $breakdown['overhead_percent'] . '% overhead)</small>';
                                echo '<br><small style="color: #666;">β””β”€β”€ Database: ' . iu_format_bytes($breakdown['database']) . '</small>';
                                }
                                echo '<br><small style="color: #666;">- Files: ' . iu_format_bytes($breakdown['files_with_overhead']) . ' (+' . $breakdown['overhead_percent'] . '% overhead)</small>';
                                echo '<br><small style="color: #666;">- Database: ' . iu_format_bytes($breakdown['database']) . '</small>';
                                
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
                                    echo '<p style="color: #564be4; font-weight: 500; margin-bottom: 10px;">⏳ Υπολογισμός σε εξέλιξη...</p>';
                                    
                                    // Show detailed progress information
                                    if (isset($queue_status['current_dir_index']) && isset($queue_status['total_size'])) {
                                        $directories = array('wp-admin', 'wp-content', 'wp-includes');
                                        $current_dir = isset($directories[$queue_status['current_dir_index']]) ? $directories[$queue_status['current_dir_index']] : 'database';
                                        $progress_percent = round(($queue_status['current_dir_index'] / 3) * 100);
                                        
                                        echo '<small style="color: #564be4;">📂 Σαρώνει: <strong>' . $current_dir . '</strong></small>';
                                        echo '<br><div style="background: #f5f5f5; height: 8px; border-radius: 4px; margin: 5px 0; max-width: 200px;"><div style="background: #564be4; height: 100%; width: ' . $progress_percent . '%; border-radius: 4px; transition: width 0.3s;"></div></div>';
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
                        <div style="background: #f5f5f5; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0;">
                            <div style="background: <?php echo $color; ?>; height: 100%; width: <?php echo $percentage; ?>%; transition: width 0.3s;"></div>
                        </div>
                        <p><strong><?php echo number_format($percentage, 1); ?>%</strong> του διαθέσιμου χώρου</p>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <p style="margin-bottom: 10px;"><strong>Επανυπολογισμός:</strong></p>
                        <p style="margin-bottom: 15px; color: #666; font-size: 13px;">Ο χώρος επανυπολογίζεται αυτόματα κάθε εβδομάδα. Για άμεσο επανυπολογισμό, πατήστε το παρακάτω κουμπί.</p>
                        
                        <div style="margin-bottom: 10px;">
                            <a href="?page=istodata-utilities&tab=hosting&recalc_storage=1&_wpnonce=<?php echo wp_create_nonce('iu_recalc_storage'); ?>" class="button button-secondary">Επανυπολογισμός</a>
                            
                            <?php 
                            // Show debug tools only in debug mode
                            $debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
                            if ($debug_mode): ?>
                                <br><br>
                                <strong>π”§ Debug Tools:</strong><br>
                                <a href="?page=istodata-utilities&tab=hosting&cleanup_storage=1&_wpnonce=<?php echo wp_create_nonce('iu_cleanup_storage'); ?>&debug=1" class="button button-secondary" style="margin-right: 10px;">🗑️ Καθαρισμός Storage Data</a>
                                <a href="?page=istodata-utilities&tab=hosting&clear_update_cache=1&_wpnonce=<?php echo wp_create_nonce('iu_clear_cache'); ?>&debug=1" class="button button-secondary">π”„ Clear Update Cache</a>
                            <?php endif; ?>
                            
                            <?php 
                            // Show debug buttons only in debug mode
                            $debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
                            if ($debug_mode) {
                                $queue_status = get_option('iu_storage_queue_status', false);
                                if ($queue_status && $queue_status['status'] === 'pending'): ?>
                                    <a href="?page=istodata-utilities&tab=hosting&trigger_queue=1&_wpnonce=<?php echo wp_create_nonce('iu_trigger_queue'); ?>&debug=1" class="button button-primary" style="margin-right: 10px;">π”§ Force Queue Step</a>
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
                                    <div id="iu-progress-bar" style="background: #564be4; height: 100%; width: 0%; transition: width 0.3s;"></div>
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
                <h3>Documentation</h3>
                <table class="form-table">
                    <tr>
                        <td>
                            <label for="iu_documentation_url">Σύνδεσμος προς το Documentation</label><br />
                            <input type="url" id="iu_documentation_url" class="regular-text" placeholder="https://..."
                                   name="istodata_utilities_settings[dashboard][documentation_url]"
                                   value="<?php echo isset($settings['dashboard']['documentation_url']) ? esc_attr($settings['dashboard']['documentation_url']) : ''; ?>" />
                            <p class="description">Ο σύνδεσμος προς τον οδηγό διαχείρισης (θα εμφανίζεται CTA στο Dashboard).</p>
                        </td>
                    </tr>
                </table>

                <h3>WordPress</h3>
                <p>Επιλέξτε τα WordPress widgets που θέλετε να αφαιρεθούν από τον πίνακα ελέγχου:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_activity]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_activity']) ? $settings['dashboard']['remove_activity'] : false); ?> />
                                   Activity</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_quick_draft]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_quick_draft']) ? $settings['dashboard']['remove_quick_draft'] : false); ?> />
                                   Quick Draft</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_welcome]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_welcome']) ? $settings['dashboard']['remove_welcome'] : false); ?> />
                                   Welcome</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_news']) ? $settings['dashboard']['remove_news'] : false); ?> />
                                   WordPress News</label>
                        </td>
                    </tr>
                </table>
                
                <?php
                // Check if WooCommerce is active
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                if (is_plugin_active('woocommerce/woocommerce.php')):
                ?>
                <h3>WooCommerce</h3>
                <p>Επιλέξτε τα WooCommerce widgets που θέλετε να αφαιρεθούν από τον πίνακα ελέγχου:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_woocommerce_setup]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_woocommerce_setup']) ? $settings['dashboard']['remove_woocommerce_setup'] : false); ?> />
                                   WooCommerce Setup</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_woocommerce_status]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_woocommerce_status']) ? $settings['dashboard']['remove_woocommerce_status'] : false); ?> />
                                   WooCommerce Status</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_woocommerce_recent_reviews]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_woocommerce_recent_reviews']) ? $settings['dashboard']['remove_woocommerce_recent_reviews'] : false); ?> />
                                   WooCommerce Recent Reviews</label>
                        </td>
                    </tr>
                </table>
                <?php endif; ?>
                
                <h3>Plugins</h3>
                <p>Επιλέξτε τα plugin widgets που θέλετε να αφαιρεθούν από τον πίνακα ελέγχου:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_avada_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_avada_news']) ? $settings['dashboard']['remove_avada_news'] : false); ?> />
                                   Avada News</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_cookie_compliance]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_cookie_compliance']) ? $settings['dashboard']['remove_cookie_compliance'] : false); ?> />
                                   Cookie Compliance</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_elementor_accessibility]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_elementor_accessibility']) ? $settings['dashboard']['remove_elementor_accessibility'] : false); ?> />
                                   Elementor Accessibility</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_elementor_manage_dashboard]" value="1"
                                         <?php checked(isset($settings['dashboard']['remove_elementor_manage_dashboard']) ? $settings['dashboard']['remove_elementor_manage_dashboard'] : false); ?> />
                                   Elementor Manage Dashboard</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_fluent_forms]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_fluent_forms']) ? $settings['dashboard']['remove_fluent_forms'] : false); ?> />
                                   Fluent Forms Latest Form Submissions</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_webappick_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_webappick_news']) ? $settings['dashboard']['remove_webappick_news'] : false); ?> />
                                   Latest News from WebAppick Blog</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_yith_blog_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_yith_blog_news']) ? $settings['dashboard']['remove_yith_blog_news'] : false); ?> />
                                   Latest news from YITH Blog</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_object_cache_pro]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_object_cache_pro']) ? $settings['dashboard']['remove_object_cache_pro'] : false); ?> />
                                   Object Cache Pro</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_pixelwars]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_pixelwars']) ? $settings['dashboard']['remove_pixelwars'] : false); ?> />
                                   Pixelwars</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_qode_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_qode_news']) ? $settings['dashboard']['remove_qode_news'] : false); ?> />
                                   Qode Interactive News</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_quadlayers_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_quadlayers_news']) ? $settings['dashboard']['remove_quadlayers_news'] : false); ?> />
                                   QuadLayers News</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_siteorigin_news]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_siteorigin_news']) ? $settings['dashboard']['remove_siteorigin_news'] : false); ?> />
                                   SiteOrigin Page Builder News</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_smash_balloon_feeds]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_smash_balloon_feeds']) ? $settings['dashboard']['remove_smash_balloon_feeds'] : false); ?> />
                                   Smash Balloon Feeds</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_themeisle]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_themeisle']) ? $settings['dashboard']['remove_themeisle'] : false); ?> />
                                   WordPress Guides/Tutorials</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_wp_mail_smtp]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_wp_mail_smtp']) ? $settings['dashboard']['remove_wp_mail_smtp'] : false); ?> />
                                   WP Mail SMTP</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_wpforms]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_wpforms']) ? $settings['dashboard']['remove_wpforms'] : false); ?> />
                                   WPForms</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_wpmet_stories]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_wpmet_stories']) ? $settings['dashboard']['remove_wpmet_stories'] : false); ?> />
                                   Wpmet Stories</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_yith_updates]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_yith_updates']) ? $settings['dashboard']['remove_yith_updates'] : false); ?> />
                                   YITH Latest Updates</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_yoast_posts_overview]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_yoast_posts_overview']) ? $settings['dashboard']['remove_yoast_posts_overview'] : false); ?> />
                                   Yoast SEO Posts Overview</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[dashboard][remove_yoast_wincher_overview]" value="1" 
                                         <?php checked(isset($settings['dashboard']['remove_yoast_wincher_overview']) ? $settings['dashboard']['remove_yoast_wincher_overview'] : false); ?> />
                                   Yoast SEO / Wincher Top Keyphrases</label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                </div>
                
            <?php elseif ($active_tab == 'optimizations'): ?>
                <div class="istodata-tab-content">
                <h3>WordPress</h3>
                <p>Ενεργοποιήστε τις επιθυμητές βελτιστοποιήσεις για το WordPress:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_attributes]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_attributes']) ? $settings['optimizations']['remove_attributes'] : false); ?> />
                                   Αφαίρεση Attributes</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_cookiebanner]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_cookiebanner']) ? $settings['optimizations']['remove_cookiebanner'] : false); ?> />
                                   Αφαίρεση Cookiebanner Metabox</label>
                        </td>
                    </tr>
                    <?php if ( defined('WP_ROCKET_VERSION') ) : ?>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_wp_rocket_options]" value="1"
                                         <?php checked(isset($settings['optimizations']['remove_wp_rocket_options']) ? $settings['optimizations']['remove_wp_rocket_options'] : false); ?> />
                                   Αφαίρεση WP Rocket Options</label>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_dashicons]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_dashicons']) ? $settings['optimizations']['remove_dashicons'] : false); ?> />
                                   Αφαίρεση Dashicons</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_format]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_format']) ? $settings['optimizations']['remove_format'] : false); ?> />
                                   Αφαίρεση Format Metabox</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_rss_feeds]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_rss_feeds']) ? $settings['optimizations']['remove_rss_feeds'] : false); ?> />
                                   Αφαίρεση RSS Feed Links</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_shortlink]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_shortlink']) ? $settings['optimizations']['remove_shortlink'] : false); ?> />
                                   Αφαίρεση Shortlink Meta</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_tags]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_tags']) ? $settings['optimizations']['remove_tags'] : false); ?> />
                                   Αφαίρεση Tags Metabox</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][remove_block_library_css]" value="1" 
                                         <?php checked(isset($settings['optimizations']['remove_block_library_css']) ? $settings['optimizations']['remove_block_library_css'] : false); ?> />
                                   Αφαίρεση WP Block Library CSS</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_search]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_search']) ? $settings['optimizations']['disable_search'] : false); ?> />
                                   Απενεργοποίηση Αναζήτησης</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_author_archives]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_author_archives']) ? $settings['optimizations']['disable_author_archives'] : false); ?> />
                                   Απενεργοποίηση Author Archives</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_auto_updates]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_auto_updates']) ? $settings['optimizations']['disable_auto_updates'] : false); ?> />
                                   Απενεργοποίηση αυτόματων ενημερώσεων</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_auto_update_emails]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_auto_update_emails']) ? $settings['optimizations']['disable_auto_update_emails'] : false); ?> />
                                   Απενεργοποίηση emails αυτόματων ενημερώσεων</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_emojis]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_emojis']) ? $settings['optimizations']['disable_emojis'] : false); ?> />
                                   Απενεργοποίηση Emojis</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_gutenberg]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_gutenberg']) ? $settings['optimizations']['disable_gutenberg'] : false); ?> />
                                   Απενεργοποίηση Gutenberg Editor</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][move_jquery_to_footer]" value="1" 
                                         <?php checked(isset($settings['optimizations']['move_jquery_to_footer']) ? $settings['optimizations']['move_jquery_to_footer'] : false); ?> />
                                   Μεταφορά jQuery στο footer</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_pingbacks]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_pingbacks']) ? $settings['optimizations']['disable_pingbacks'] : false); ?> />
                                   Απενεργοποίηση Pingbacks/Trackbacks</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_embeds]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_embeds']) ? $settings['optimizations']['disable_embeds'] : false); ?> />
                                   Απενεργοποίηση WordPress Embeds</label>
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
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][limit_post_revisions]" value="1" 
                                         <?php checked(isset($settings['optimizations']['limit_post_revisions']) ? $settings['optimizations']['limit_post_revisions'] : false); ?> />
                                   Περιορισμός Post Revisions (10 max)</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_comments]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_comments']) ? $settings['optimizations']['disable_comments'] : false); ?> />
                                   Πλήρης απενεργοποίηση σχολίων</label>
                        </td>
                    </tr>
                </table>
                
                <?php /* Elementor-specific optimizations moved to Elementor tab */ ?>
                
                <?php
                // Check if WPML is active
                if (is_plugin_active('sitepress-multilingual-cms/sitepress.php')):
                ?>
                <h3>WPML</h3>
                <p>Βελτιστοποιήσεις για το WPML:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_wpml_css]" value="1" 
                                         <?php checked(isset($settings['optimizations']['disable_wpml_css']) ? $settings['optimizations']['disable_wpml_css'] : false); ?> />
                                   Αφαίρεση WPML CSS</label>
                        </td>
                    </tr>
                </table>
                <?php endif; ?>
                
                <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                </div>
                
            <?php elseif ($active_tab == 'elementor'): ?>
                <?php
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                if (is_plugin_active('elementor/elementor.php')):
                ?>
                <div class="istodata-tab-content">
                    <h3>Elementor</h3>
                    <p>Ρυθμίσεις και λειτουργίες για το Elementor:</p>

                    <h4>Βελτιστοποιήσεις</h4>
                    <table class="form-table">
                        <tr>
                            <td>
                                <label><input type="checkbox" name="istodata_utilities_settings[optimizations][disable_elementor_upsells]" value="1"
                                             <?php checked(!empty($settings['optimizations']['disable_elementor_upsells'])); ?> />
                                       Απενεργοποίηση Elementor Upsells</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><input type="checkbox" name="istodata_utilities_settings[optimizations][elementor_remove_profile_ai_notes]" value="1"
                                             <?php checked(!empty($settings['optimizations']['elementor_remove_profile_ai_notes'])); ?> />
                                       Αφαίρεση Elementor AI και Notes από τη σελίδα του προφίλ</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><input type="checkbox" name="istodata_utilities_settings[optimizations][elementor_accordion_scroll_to_active]" value="1"
                                             <?php checked(!empty($settings['optimizations']['elementor_accordion_scroll_to_active'])); ?> />
                                       Accordion: Κύλιση στο ενεργό</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><input type="checkbox" name="istodata_utilities_settings[optimizations][elementor_animations]" value="1"
                                             <?php checked(!empty($settings['optimizations']['elementor_animations'])); ?> />
                                       Βελτίωση Animations</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><input type="checkbox" name="istodata_utilities_settings[optimizations][elementor_additional_animations]" value="1"
                                             <?php checked(!empty($settings['optimizations']['elementor_additional_animations'])); ?> />
                                       Πρόσθετα Animations</label>
                                <p class="description" style="color:#666; margin:4px 0 0;">Προσθέτει νέα Entrance Animations στο Elementor: Wipe Up, Wipe Down, Wipe Left, Wipe Right.</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><input type="checkbox" name="istodata_utilities_settings[optimizations][elementor_disable_mobile_animations]" value="1"
                                             <?php checked(!empty($settings['optimizations']['elementor_disable_mobile_animations'])); ?> />
                                       Mobile Animations: Καθολική απενεργοποίηση (όλα τα elements)</label>
                                </td>
                        </tr>
                        <?php $wp_rocket_active = (defined('WP_ROCKET_VERSION') || (function_exists('is_plugin_active') && is_plugin_active('wp-rocket/wp-rocket.php'))); ?>
                        <?php if ($wp_rocket_active): ?>
                        <tr>
                            <td>
                                <label><input type="checkbox" name="istodata_utilities_settings[optimizations][elementor_mobile_anim_per_element]" value="1"
                                             <?php checked(!empty($settings['optimizations']['elementor_mobile_anim_per_element'])); ?> />
                                       Mobile Animations: Έλεγχος ανά στοιχείο (Elementor)</label>
                                <p class="description" style="color:#666; margin:4px 0 0;">Προσθέτει διακόπτη σε όλα τα elements για να απενεργοποιείς το entrance animation μόνο στο κινητό.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <h4>Λειτουργίες</h4>
                    <table class="form-table">
                        <tr>
                            <td>
                                <?php
                                // Default ON when the key is not present yet (upgrade path)
                                $device_vis_enabled = !isset($settings['additional']['elementor_device_visibility']) || !empty($settings['additional']['elementor_device_visibility']);
                                ?>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_device_visibility]" value="1"
                                           <?php checked($device_vis_enabled); ?> />
                                    Ορατότητα Συσκευών <span style="color: #666; font-size: 12px;">(server‑side) </span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][scroll_to_top]" value="1"
                                           <?php checked(!empty($settings['additional']['scroll_to_top'])); ?> />
                                    Scroll To Top <span style="color: #666; font-size: 12px;">(Widget & Dynamic Tag) </span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php $elementor_pro_active = is_plugin_active('elementor-pro/elementor-pro.php'); ?>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_reading_time]" value="1"
                                           <?php checked(!empty($settings['additional']['elementor_reading_time'])); ?>
                                           <?php echo !$elementor_pro_active ? 'disabled' : ''; ?> />
                                    Χρόνος Ανάγνωσης <span style="color: #666; font-size: 12px;">(Dynamic Tag) </span>
                                    <?php if (!$elementor_pro_active): ?>
                                        <span style="color: #d63638;">(Απαιτείται Elementor Pro)</span>
                                    <?php endif; ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_google_maps_widget]" value="1"
                                           <?php checked(!empty($settings['additional']['elementor_google_maps_widget'])); ?> />
                                    Advanced Google Map Widget
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <?php $wpml_active = is_plugin_active('sitepress-multilingual-cms/sitepress.php'); ?>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_wpml_language_switcher]" value="1"
                                           <?php disabled(!$wpml_active); ?>
                                           <?php checked(!empty($settings['additional']['elementor_wpml_language_switcher'])); ?> />
                                    WPML Language Switcher
                                    <?php if (!$wpml_active): ?>
                                        <span style="color: #d63638;">(Απαιτείται WPML)</span>
                                    <?php endif; ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <?php $wpml_active = is_plugin_active('sitepress-multilingual-cms/sitepress.php'); ?>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_wpml_shortcode_widget]" value="1"
                                           <?php disabled(!$wpml_active); ?>
                                           <?php checked(!empty($settings['additional']['elementor_wpml_shortcode_widget'])); ?> />
                                    Multilingual Shortcode Widget
                                    <?php if (!$wpml_active): ?>
                                        <span style="color: #d63638;">(Απαιτείται WPML)</span>
                                    <?php endif; ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_social_share_widget]" value="1"
                                           <?php checked(
                                               (!empty($settings['additional']['elementor_social_share_widget'])) ||
                                               (!empty($settings['optimizations']['elementor_social_share_widget']))
                                           ); ?> />
                                    Social Share Widget
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_heading_group_widget]" value="1"
                                           <?php checked(!empty($settings['additional']['elementor_heading_group_widget'])); ?> />
                                    Heading Group Widget
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_typed_widget]" value="1"
                                           <?php checked(!empty($settings['additional']['elementor_typed_widget'])); ?> />
                                    Typed Widget
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_taxonomy_links_widget]" value="1"
                                           <?php checked(!empty($settings['additional']['elementor_taxonomy_links_widget'])); ?> />
                                    Taxonomy Links Widget
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_query_posts_widget]" value="1"
                                           <?php checked(!empty($settings['additional']['elementor_query_posts_widget'])); ?> />
                                    Query Posts Widget
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <?php $acf_active = iu_is_acf_available(); ?>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][acf_simple_repeater]" value="1"
                                           <?php disabled(!$acf_active); ?>
                                           <?php checked(!empty($settings['additional']['acf_simple_repeater']) && $acf_active); ?> />
                                    ACF Simple Repeater Field
                                    <?php if (!$acf_active): ?>
                                        <span style="color: #d63638;">(Απαιτείται ACF)</span>
                                    <?php endif; ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_simple_repeater_widget]" value="1"
                                           <?php disabled(!$acf_active); ?>
                                           <?php checked(!empty($settings['additional']['elementor_simple_repeater_widget']) && $acf_active); ?> />
                                    Simple Repeater Widget
                                    <?php if (!$acf_active): ?>
                                        <span style="color: #d63638;">(Απαιτείται ACF)</span>
                                    <?php endif; ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php
                                $elementor_pro_active = is_plugin_active('elementor-pro/elementor-pro.php');
                                $gallery_enabled = !empty($settings['additional']['elementor_image_gallery']);
                                ?>
                                <label>
                                    <input type="checkbox" name="istodata_utilities_settings[additional][elementor_image_gallery]" value="1"
                                           <?php checked($gallery_enabled); ?>
                                           <?php echo !$elementor_pro_active ? 'disabled' : ''; ?> />
                                    Post Gallery <span style="color: #666; font-size: 12px;">(Admin Section & Dynamic Tag) </span>
                                    <?php if (!$elementor_pro_active): ?>
                                        <span style="color: #d63638;">(Απαιτείται Elementor Pro)</span>
                                    <?php endif; ?>
                                </label>
                                <?php
                                // Post types list (public)
                                $selected_pts = isset($settings['additional']['elementor_image_gallery_post_types']) && is_array($settings['additional']['elementor_image_gallery_post_types'])
                                    ? $settings['additional']['elementor_image_gallery_post_types'] : array();
                                $pt_objects = get_post_types(array('public' => true), 'objects');
                                // Filter out unwanted types
                                unset($pt_objects['attachment']);
                                if (isset($pt_objects['elementor_library'])) { unset($pt_objects['elementor_library']); }
                                if (isset($pt_objects['e-floating-buttons'])) { unset($pt_objects['e-floating-buttons']); }
                                ?>
                                <div id="iu-elementor-gallery-pts" style="margin-top:8px; margin-left:25px; <?php echo $gallery_enabled ? '' : 'display:none;'; ?>">
                                    <span>Διαθέσιμο σε:</span>
                                    <div style="margin-top:6px; display:flex; flex-wrap: wrap; gap: 12px;">
                                        <?php foreach ($pt_objects as $pt => $obj): ?>
                                            <label style="display:inline-flex; align-items:center; gap:6px;">
                                                <input type="checkbox" name="istodata_utilities_settings[additional][elementor_image_gallery_post_types][]" value="<?php echo esc_attr($pt); ?>"
                                                    <?php checked(in_array($pt, $selected_pts, true)); ?> />
                                                <?php echo esc_html($obj->labels->name . ' (' . $pt . ')'); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <script>
                                    (function(){
                                        const chk = document.querySelector('input[name="istodata_utilities_settings[additional][elementor_image_gallery]"]');
                                        const box = document.getElementById('iu-elementor-gallery-pts');
                                        if (chk && box) {
                                            chk.addEventListener('change', function(){
                                                box.style.display = this.checked ? '' : 'none';
                                            });
                                        }
                                    })();
                                </script>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                </div>
                <?php endif; ?>

            <?php elseif ($active_tab == 'woocommerce'): ?>
                <?php
                // Show WooCommerce tab only if WooCommerce is active
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                if (is_plugin_active('woocommerce/woocommerce.php') || class_exists('WooCommerce')):
                ?>
            <div class="istodata-tab-content">
            <h3>Βελτιστοποιήσεις WooCommerce</h3>
            <p>Βελτιστοποιήσεις και λειτουργίες:</p>
            <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][disable_product_tags]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['disable_product_tags']) ? $settings['woocommerce']['disable_product_tags'] : false); ?> />
                                   Απενεργοποίηση Product tags</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][enable_attributes_in_menus]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['enable_attributes_in_menus']) ? $settings['woocommerce']['enable_attributes_in_menus'] : false); ?> />
                                   Ενεργοποίηση των WooCommerce attributes στο Appearance → Menus</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][hide_shipping_when_free]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['hide_shipping_when_free']) ? $settings['woocommerce']['hide_shipping_when_free'] : false); ?> />
                                   Απόκρυψη μεθόδων αποστολής όταν υπάρχει δωρεάν μεταφορά
                                   <span style="color: #666; font-size: 12px;">(Διατηρεί local pickup αν υπάρχει)</span></label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][enable_sku_search]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['enable_sku_search']) ? $settings['woocommerce']['enable_sku_search'] : false); ?> />
                                   Ενεργοποίηση Αναζήτησης με SKU
                                   <span style="color: #666; font-size: 12px;">(Επιτρέπει αναζήτηση προϊόντων με SKU κωδικό)</span></label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][enable_brand_custom_order]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['enable_brand_custom_order']) ? $settings['woocommerce']['enable_brand_custom_order'] : false); ?> />
                                   Χρήση custom σειράς εμφάνισης για τα Brands
                                   <span style="color: #666; font-size: 12px;">(Ακολουθεί το menu order που ορίζεται με drag & drop)</span></label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][remove_wc_blocks_css]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['remove_wc_blocks_css']) ? $settings['woocommerce']['remove_wc_blocks_css'] : false); ?> />
                                   Αφαίρεση WooCommerce Blocks CSS 
                                   <span style="color: #666; font-size: 12px;">(wc-blocks.css)</span></label>
                        </td>
                    </tr>
                    <?php if (function_exists('iu_is_husky_active') ? iu_is_husky_active() : false): ?>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][husky_archive_assets_only]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['husky_archive_assets_only']) ? $settings['woocommerce']['husky_archive_assets_only'] : false); ?> />
                                   Φόρτωση Husky (WOOF) assets μόνο σε Product Archives</label>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

                <h3>Catalog Mode</h3>
                <p>Λειτουργίες καταλόγου χωρίς αγορές/τιμές:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][catalog_remove_add_to_cart_archive]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['catalog_remove_add_to_cart_archive']) ? $settings['woocommerce']['catalog_remove_add_to_cart_archive'] : false); ?> />
                                   Αφαίρεση κουμπιού Add to Cart στη λίστα προϊόντων</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][catalog_remove_add_to_cart_single]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['catalog_remove_add_to_cart_single']) ? $settings['woocommerce']['catalog_remove_add_to_cart_single'] : false); ?> />
                                   Αφαίρεση κουμπιού Add to Cart στη σελίδα προϊόντος</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][catalog_hide_prices]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['catalog_hide_prices']) ? $settings['woocommerce']['catalog_hide_prices'] : false); ?> />
                                   Απόκρυψη τιμών</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][catalog_disable_cart_checkout]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['catalog_disable_cart_checkout']) ? $settings['woocommerce']['catalog_disable_cart_checkout'] : false); ?> />
                                   Απενεργοποίηση πρόσβασης στις σελίδες Καλαθιού & Checkout</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][catalog_disable_add_to_cart]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['catalog_disable_add_to_cart']) ? $settings['woocommerce']['catalog_disable_add_to_cart'] : false); ?> />
                                   Απενεργοποίηση προσθήκης στο καλάθι</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][catalog_remove_wc_js]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['catalog_remove_wc_js']) ? $settings['woocommerce']['catalog_remove_wc_js'] : false); ?> />
                                   Αφαίρεση WooCommerce JS</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><input type="checkbox" name="istodata_utilities_settings[woocommerce][catalog_remove_wc_css]" value="1" 
                                         <?php checked(isset($settings['woocommerce']['catalog_remove_wc_css']) ? $settings['woocommerce']['catalog_remove_wc_css'] : false); ?> />
                                   Αφαίρεση WooCommerce CSS <span style="color: #666; font-size: 12px;">(Μέγιστη απόδοση· ενδέχεται να επηρεάσει την εμφάνιση shop/pages)</span></label>                            
                        </td>
                    </tr>
                </table>
                <?php submit_button('Αποθήκευση Αλλαγών'); ?>
                </div>
                <?php else: ?>
                <div class="istodata-tab-content">
                    <p style="color: #d63638;">Το WooCommerce δεν είναι εγκατεστημένο ή ενεργοποιημένο.</p>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="istodata-tab-content">
                <h3>Πρόσθετες Λειτουργίες</h3>
                <p>Ενεργοποιήστε τις επιθυμητές πρόσθετες λειτουργίες:</p>
                <table class="form-table">
                    <tr>
                        <td>
                            <label>
                                <input type="checkbox" name="istodata_utilities_settings[additional][greeklish_permalinks]" value="1" 
                                       <?php checked(isset($settings['additional']['greeklish_permalinks']) ? $settings['additional']['greeklish_permalinks'] : false); ?> />
                                Greeklish Permalinks
                                <span style="color: #666; font-size: 12px;">(Αυτόματη μετατροπή ελληνικών χαρακτήρων σε λατινικούς στα URLs)</span>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            <label>
                                <input type="checkbox" name="istodata_utilities_settings[additional][duplicate_post_link]" value="1" 
                                       <?php checked(isset($settings['additional']['duplicate_post_link']) ? $settings['additional']['duplicate_post_link'] : false); ?> />
                                Duplicate Post/Page Link
                                <span style="color: #666; font-size: 12px;">(Προσθήκη "Duplicate" link στις ενέργειες posts/pages)</span>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            <label>
                                <input type="checkbox" name="istodata_utilities_settings[additional][protect_content]" value="1" 
                                       <?php checked(!empty($settings['additional']['protect_content'])); ?> />
                                Προστασία Περιεχομένου
                                <span style="color: #666; font-size: 12px;">(Απενεργοποίηση επιλογής κειμένου, δεξί κλικ, drag εικόνων. Μπορεί να επηρεάσει την προσβασιμότητα.)</span>
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
                            <?php if (!empty($settings['additional']['typed_js']) && (defined('WP_ROCKET_VERSION') || (function_exists('is_plugin_active') && is_plugin_active('wp-rocket/wp-rocket.php')))): ?>
                                <div style="margin-top: 8px;">
                                    <label>
                                        <input type="checkbox" name="istodata_utilities_settings[additional][typed_js_wp_rocket_exclude]" value="1"
                                               <?php checked(isset($settings['additional']['typed_js_wp_rocket_exclude']) ? $settings['additional']['typed_js_wp_rocket_exclude'] : false); ?> />
                                        Εξαίρεση από WP Rocket Delay JS (above the fold)
                                    </label>
                                    <p class="description" style="color:#666; margin: 4px 0 0;">Απενεργοποιεί το Delay JS για το Typed.js και τον loader του ώστε να εμφανίζεται άμεσα πάνω από το fold.</p>
                                </div>
                            <?php endif; ?>
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

// �������� ��� ������������� ��� ���������
observer.observe(targetElement);
</script></textarea>
                                <p style="font-size: 11px; color: #666; margin-top: 5px;">
                                    💡 Κάντε κλικ στο textarea για επιλογή όλου του κώδικα
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <?php /* Elementor features moved to the new Elementor tab */ ?>
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

// Frontend: Scroll To Top JS when enabled
add_action('wp_enqueue_scripts', function(){
    if (is_admin() || wp_doing_ajax()) { return; }
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    if (empty($additional['scroll_to_top'])) { return; }
    wp_enqueue_script(
        'iu-scroll-to-top',
        IU_PLUGIN_URL . 'assets/js/scroll-to-top.js',
        array(),
        IU_PLUGIN_VERSION,
        true
    );
}, 20);

// Frontend: Protect content (texts & images) from copy when enabled
add_action('wp_enqueue_scripts', function(){
    if (is_admin() || wp_doing_ajax()) { return; }
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    if (empty($additional['protect_content'])) { return; }
    wp_enqueue_style('iu-no-copy', IU_PLUGIN_URL . 'assets/css/no-copy.css', array(), IU_PLUGIN_VERSION);
    wp_enqueue_script('iu-no-copy', IU_PLUGIN_URL . 'assets/js/no-copy.js', array(), IU_PLUGIN_VERSION, true);
}, 20);

// Site suspension check
add_action('template_redirect', 'iu_check_site_suspension');

function iu_check_site_suspension() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    
    $settings = get_option('istodata_utilities_settings', array());
    $hosting = isset($settings['hosting']) ? $settings['hosting'] : array();
    
    if (!empty($hosting['site_suspended'])) {
        status_header(503);
        header('Retry-After: 3600');
        
        include IU_PLUGIN_PATH . 'templates/suspension-page.php';
        exit;
    }
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

    if (!empty($optimizations['move_jquery_to_footer'])) {
        // Move jQuery (core and migrate) to footer on the frontend
        add_action('wp_default_scripts', 'iu_move_jquery_to_footer');
        // Also try to move any enqueued scripts that depend on jQuery to the footer
        add_action('wp_enqueue_scripts', 'iu_move_jquery_dependents_to_footer', 1000);
        // In case wp_default_scripts already ran, force group change during enqueue phase
        add_action('wp_enqueue_scripts', 'iu_force_jquery_to_footer_group', 1);
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
    
    if (!empty($optimizations['disable_wpml_css'])) {
        if (!defined('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS')) {
            define('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS', true);
        }
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
        // Set WordPress default image sizes to 0
        update_option('thumbnail_size_h', 0);
        update_option('thumbnail_size_w', 0);
        update_option('medium_size_h', 0);
        update_option('medium_size_w', 0);
        update_option('large_size_h', 0);
        update_option('large_size_w', 0);
        
        // Disable default WordPress image sizes via filter (backup method)
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
    
    if (!empty($optimizations['remove_format'])) {
        add_action('admin_init', 'iu_remove_format_metabox');
        add_filter('hidden_meta_boxes', 'iu_hide_format_metabox', 10, 2);
    }
    
    if (!empty($optimizations['remove_tags'])) {
        add_action('admin_init', 'iu_remove_tags_metabox');
        add_filter('hidden_meta_boxes', 'iu_hide_tags_metabox', 10, 2);
    }
    
    if (!empty($optimizations['remove_cookiebanner'])) {
        // Remove after all meta boxes are registered
        add_action('add_meta_boxes', 'iu_remove_cookiebanner_metabox', 99, 2);
        add_filter('hidden_meta_boxes', 'iu_hide_cookiebanner_metabox', 10, 2);
        add_action('admin_head-post.php', 'iu_hide_cookiebanner_screen_options');
        add_action('admin_head-post-new.php', 'iu_hide_cookiebanner_screen_options');
    }

    if (!empty($optimizations['remove_wp_rocket_options']) && defined('WP_ROCKET_VERSION')) {
        // Remove WP Rocket Options metabox and hide from Screen Options
        add_action('add_meta_boxes', 'iu_remove_wprocket_metabox', 99, 2);
        add_filter('hidden_meta_boxes', 'iu_hide_wprocket_metabox', 10, 2);
        add_action('admin_head-post.php', 'iu_hide_wprocket_screen_options');
        add_action('admin_head-post-new.php', 'iu_hide_wprocket_screen_options');
    }
    
    if (!empty($optimizations['disable_auto_updates'])) {
        // Disable core auto-updates
        add_filter('auto_update_core', '__return_false');
        // Disable auto-updates for plugins
        add_filter('auto_update_plugin', '__return_false');
        // Disable auto-updates for themes
        add_filter('auto_update_theme', '__return_false');
    }

    if (!empty($optimizations['disable_auto_update_emails'])) {
        // Disable automatic update emails for plugins
        add_filter('auto_plugin_update_send_email', '__return_false');
        
        // Disable automatic update emails for themes
        add_filter('auto_theme_update_send_email', '__return_false');
        
        // Disable automatic update emails for core
        add_filter('auto_core_update_send_email', '__return_false');
    }
    
    if (!empty($optimizations['disable_search'])) {
        // Prevent search queries
        add_action('parse_query', function($query, $error = true) {
            if (is_search() && !is_admin()) {
                $query->is_search = false;
                $query->query_vars['s'] = false;
                $query->query['s'] = false;
                if (true === $error) {
                    $query->is_404 = true;
                }
            }
        }, 15, 2);
        
        // Remove the Search Widget
        add_action('widgets_init', function() {
            unregister_widget('WP_Widget_Search');
        });
        
        // Remove the search form
        add_filter('get_search_form', '__return_empty_string', 999);
        
        // Remove the core search block
        add_action('init', function() {
            if (!function_exists('unregister_block_type') || !class_exists('WP_Block_Type_Registry')) {
                return;
            }
            $block = 'core/search';
            if (WP_Block_Type_Registry::get_instance()->is_registered($block)) {
                unregister_block_type($block);
            }
        });
        
        // Remove admin bar menu search box
        add_action('admin_bar_menu', function($wp_admin_bar) {
            $wp_admin_bar->remove_menu('search');
        }, 11);
    }
    
    if (!empty($optimizations['disable_author_archives'])) {
        // Return a 404 page for author pages if accessed directly
        add_action('template_redirect', function() {
            if (is_author()) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
            }
        });
        
        // Remove the author links
        add_filter('author_link', '__return_empty_string', 1000);
        add_filter('the_author_posts_link', 'get_the_author', 1000, 0);
        
        // Remove the author pages from the WP 5.5+ sitemap
        add_filter('wp_sitemaps_add_provider', function($provider, $name) {
            if ('users' === $name) {
                return false;
            }
            return $provider;
        }, 10, 2);
        
        // Remove admin links in the list of users
        add_filter('user_row_actions', function($actions, $user) {
            unset($actions['view']);
            unset($actions['posts']);
            return $actions;
        }, 10, 2);
    }
    
    // Elementor optimizations will be handled separately
}

// Hide Elementor AI & Elementor Notes sections from user profile pages
function iu_hide_elementor_profile_ai_notes() {
    ?>
    <script>
    jQuery(function($){
        // 1) Hide by headings (various labels/localizations)
        [
            "h2:contains('Elementor - AI')",
            "h2:contains('Elementor – AI')",
            "h2:contains('Elementor AI')",
            "h2:contains('Elementor Notes')",
            "h2:contains('Elementor – Notes')",
            "h2:contains('Elementor Σημειώσεις')"
        ].forEach(function(sel){
            var $h = $(sel);
            if ($h.length) {
                $h.each(function(){
                    var $sectionHeading = $(this);
                    $sectionHeading.nextUntil('h2').filter('table.form-table').hide();
                    $sectionHeading.hide();
                });
            }
        });

        // 2) Hide specific known inputs rendered as profile rows
        // Elementor AI checkbox often uses id/name 'elementor_enable_ai'
        $("input#elementor_enable_ai, input[name='elementor_enable_ai'], input[id*='elementor'][id*='ai']").each(function(){
            $(this).closest('tr').hide();
        });

        // Elementor Notes related inputs (best-effort generic match)
        $("input[id*='elementor'][id*='note'], input[name*='elementor'][name*='note']").each(function(){
            $(this).closest('tr').hide();
        });
    });
    </script>
    <?php
}

// Handle Elementor optimizations separately
add_action('plugins_loaded', 'iu_handle_elementor_optimizations');
function iu_handle_elementor_optimizations() {
    $settings = get_option('istodata_utilities_settings', array());
    $optimizations = isset($settings['optimizations']) ? $settings['optimizations'] : array();
    
    if (!empty($optimizations['disable_elementor_upsells'])) {
        // Disable Elementor Upsells
        add_filter('elementor/admin/should_display_upsells', '__return_false');
        add_filter('elementor_pro/admin/should_display_upsells', '__return_false');
    }
    
    if (!empty($optimizations['elementor_remove_profile_ai_notes'])) {
        // Hide Elementor AI & Notes sections in user profile screens
        add_action('admin_footer-profile.php', 'iu_hide_elementor_profile_ai_notes');
        add_action('admin_footer-user-edit.php', 'iu_hide_elementor_profile_ai_notes');
    }
    
    if (!empty($optimizations['elementor_animations'])) {
        // Load enhanced animations CSS
        add_action('wp_enqueue_scripts', 'iu_enqueue_elementor_animations');
        // Safelist animation classes for WP Rocket Remove Unused CSS
        if (defined('WP_ROCKET_VERSION')) {
            add_filter('rocket_rucss_safelist', 'iu_add_animations_rucss_safelist');
        }
    }

    if (!empty($optimizations['elementor_additional_animations'])) {
        add_filter('elementor/controls/animations/additional_animations', 'iu_add_elementor_additional_animations');
        add_filter('elementor/controls/exit-animations/additional_animations', 'iu_add_elementor_additional_exit_animations');
        add_action('wp_enqueue_scripts', 'iu_enqueue_elementor_additional_transitions');
        add_action('elementor/preview/enqueue_styles', 'iu_enqueue_elementor_additional_transitions');
        if (defined('WP_ROCKET_VERSION')) {
            add_filter('rocket_rucss_safelist', 'iu_add_animations_rucss_safelist');
        }
    }

    // Accordion: scroll to active
    if (!empty($optimizations['elementor_accordion_scroll_to_active'])) {
        add_action('wp_enqueue_scripts', 'iu_enqueue_elementor_accordion_scroll_to_active');
    }

    // Disable Elementor entrance animations on mobile (inline JS+CSS only on mobile)
    if (!empty($optimizations['elementor_disable_mobile_animations'])) {
        if (did_action('elementor/loaded') || class_exists('Elementor\\Plugin') || defined('ELEMENTOR_VERSION')) {
            add_action('wp_head', 'iu_output_mobile_animation_kill_switch', 0);
            if (defined('WP_ROCKET_VERSION')) {
                // Ensure our inline JS is excluded from WP Rocket delay/minify
                add_filter('rocket_delay_js_exclusions', 'iu_exclude_inline_mobile_anim_kill_js');
                add_filter('rocket_excluded_inline_js_content', 'iu_exclude_inline_mobile_anim_kill_js');
                add_filter('rocket_minify_excluded_inline_js_content', 'iu_exclude_inline_mobile_anim_kill_js');
                // Attempt to exclude inline CSS by id/pattern (harmless if not used by WP Rocket)
                add_filter('rocket_excluded_inline_css', 'iu_exclude_inline_mobile_anim_kill_css');
            }
        }
    }
}

// Function to enqueue Elementor animations CSS
function iu_enqueue_elementor_animations() {
    static $enqueued = false;
    if ($enqueued) {
        return;
    }
    $enqueued = true;
    $version = defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null;
    // If mobile animations are disabled, avoid loading the CSS on mobile devices
    $settings = get_option('istodata_utilities_settings', array());
    $optimizations = isset($settings['optimizations']) ? $settings['optimizations'] : array();
    $disable_mobile = !empty($optimizations['elementor_disable_mobile_animations']);
    if ($disable_mobile && function_exists('wp_is_mobile') && wp_is_mobile()) {
        return;
    }
    wp_enqueue_style(
        'iu-elementor-animations',
        plugin_dir_url(__FILE__) . 'assets/css/animations.css',
        array(),
        $version
    );
}

function iu_enqueue_elementor_additional_transitions() {
    static $enqueued = false;
    if ($enqueued) {
        return;
    }
    $enqueued = true;
    $version = defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null;
    $settings = get_option('istodata_utilities_settings', array());
    $optimizations = isset($settings['optimizations']) ? $settings['optimizations'] : array();
    $disable_mobile = !empty($optimizations['elementor_disable_mobile_animations']);
    if ($disable_mobile && function_exists('wp_is_mobile') && wp_is_mobile()) {
        return;
    }
    wp_enqueue_style(
        'iu-elementor-additional-transitions',
        plugin_dir_url(__FILE__) . 'assets/css/elementor-additional-transitions.css',
        array(),
        $version
    );
}

function iu_add_elementor_additional_animations($animations) {
    if (!is_array($animations)) {
        $animations = array();
    }

    $animations['Custom Animations'] = array(
        'wipeUp' => __('Wipe Up', 'istodata-utilities'),
        'wipeDown' => __('Wipe Down', 'istodata-utilities'),
        'wipeLeft' => __('Wipe Left', 'istodata-utilities'),
        'wipeRight' => __('Wipe Right', 'istodata-utilities'),
        'circleSwoopIn' => __('Circle Swoop In', 'istodata-utilities'),
    );

    return $animations;
}

function iu_add_elementor_additional_exit_animations($animations) {
    if (!is_array($animations)) {
        $animations = array();
    }

    $animations['Custom Animations'] = array(
        'circleSwoopOut' => __('Circle Swoop Out', 'istodata-utilities'),
    );

    return $animations;
}

// Inline JS to scroll the view to the just-opened accordion item (supports Elementor + new Nested Accordion)
function iu_enqueue_elementor_accordion_scroll_to_active() {
    if (is_admin()) return;
    wp_enqueue_script(
        'iu-accordion-scroll',
        plugin_dir_url(__FILE__) . 'assets/js/accordion-scroll.js',
        array(),
        '1.1.0',
        true
    );
}

// WP Rocket RUCSS safelist for animations CSS
function iu_add_animations_rucss_safelist($list) {
    if (!is_array($list)) {
        $list = array();
    }

    $selectors = array(
        '.fadeInDown',
        '.fadeInLeft',
        '.fadeInRight',
        '.fadeInUp',
        '.elementor-element.fadeInDown',
        '.elementor-element.fadeInLeft',
        '.elementor-element.fadeInRight',
        '.elementor-element.fadeInUp',
        '.wipeUp',
        '.wipeDown',
        '.wipeLeft',
        '.wipeRight',
        '.circleSwoopIn',
        '.circleSwoopOut',
        '.animated',
        '.animated-slow',
        '.elementor-animated',
    );

    $keyframes = array(
        'fadeDown',
        'fadeLeft',
        'fadeRight',
        'fadeUp',
        '@keyframes fadeDown',
        '@keyframes fadeLeft',
        '@keyframes fadeRight',
        '@keyframes fadeUp',
        'animationWipeUp',
        'animationWipeDown',
        'animationWipeLeft',
        'animationWipeRight',
        'animationCircleSwoopIn',
        'animationCircleSwoopOut',
        '@keyframes animationWipeUp',
        '@keyframes animationWipeDown',
        '@keyframes animationWipeLeft',
        '@keyframes animationWipeRight',
        '@keyframes animationCircleSwoopIn',
        '@keyframes animationCircleSwoopOut',
    );

    $files = array(
        '(.*)istodata-utilities/assets/css/animations.css(.*)',
        '(.*)istodata-utilities/assets/css/elementor-additional-transitions.css(.*)',
    );

    return array_values(array_unique(array_merge($list, $selectors, $keyframes, $files)));
}

// Inline mobile-only JS/CSS to disable Elementor entrance animations on mobile
function iu_output_mobile_animation_kill_switch() {
    if (is_admin()) return;
    // Only print on mobile devices (best-effort via UA sniffing)
    if (!function_exists('wp_is_mobile') || !wp_is_mobile()) return;
    static $printed = false; if ($printed) return; $printed = true;
    $breakpoint = (int) apply_filters('iu_elementor_mobile_breakpoint', 767);
    ?>
    <script id="noMobileElementorEntrance">(function(){try{if(window.matchMedia&&window.matchMedia('(max-width: <?php echo (int) $breakpoint; ?>px)').matches){document.documentElement.classList.add('no-el-entrance');}}catch(e){}})();</script>
    <style id="noMobileElementorEntranceCSS" media="(max-width: <?php echo (int) $breakpoint; ?>px)">
    .no-el-entrance .elementor-invisible{visibility:visible!important;opacity:1!important;transform:none!important}
    .no-el-entrance .animated,.no-el-entrance .elementor-animated{animation:none!important;transition:none!important}
    </style>
    <?php
}

// Exclude our inline mobile kill-switch from WP Rocket delay/minify
function iu_exclude_inline_mobile_anim_kill_js($patterns){
    if (!is_array($patterns)) $patterns = array();
    $patterns[] = 'noMobileElementorEntrance';
    return $patterns;
}

function iu_exclude_inline_mobile_anim_kill_css($patterns){
    if (!is_array($patterns)) $patterns = array();
    $patterns[] = 'noMobileElementorEntranceCSS';
    return $patterns;
}

// WooCommerce optimizations
add_action('init', 'iu_apply_woocommerce_optimizations');
// Ensure attribute menus tweak can catch taxonomy registration in time
add_action('registered_taxonomy', 'iu_wc_maybe_enable_attributes_in_menus', 0, 3);
function iu_apply_woocommerce_optimizations() {
    // Only apply if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    $settings = get_option('istodata_utilities_settings', array());
    $woocommerce = isset($settings['woocommerce']) ? $settings['woocommerce'] : array();
    
    if (!empty($woocommerce['hide_shipping_when_free'])) {
        add_filter('woocommerce_package_rates', 'iu_hide_shipping_when_free_available', 10, 2);
    }
    
    if (!empty($woocommerce['enable_sku_search'])) {
        add_action('pre_get_posts', 'iu_woocommerce_sku_search');
    }

    // Custom order for product_brand terms
    if (!empty($woocommerce['enable_brand_custom_order'])) {
        // Apply globally to get_terms for product_brand
        add_filter('get_terms_args', 'iu_brand_terms_custom_order', 20, 2);
        // Apply in Elementor Loop only if Elementor is available
        if (did_action('elementor/loaded') || class_exists('Elementor\\Plugin') || defined('ELEMENTOR_VERSION')) {
            add_filter('elementor/loop_taxonomy/args', 'iu_elementor_brand_custom_order', 20, 3);
        }
    }

    // Enable WooCommerce attribute taxonomies in Appearance β†’ Menus
    // The handler is hooked globally via 'registered_taxonomy' to catch early registrations
    // Here we do nothing extra; the handler checks the option at runtime.

    // Catalog Mode behaviors
    // 1) Remove Add to Cart in product archives (loop)
    if (!empty($woocommerce['catalog_remove_add_to_cart_archive'])) {
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
        add_filter('woocommerce_loop_add_to_cart_link', '__return_empty_string', 99);
    }

    // 2) Remove Add to Cart on single product page
    if (!empty($woocommerce['catalog_remove_add_to_cart_single'])) {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    }

    // 3) Hide prices across catalog and single
    if (!empty($woocommerce['catalog_hide_prices'])) {
        add_filter('woocommerce_get_price_html', '__return_empty_string', 99);
        // Remove price/offers from structured data while prices are hidden
        add_filter('woocommerce_structured_data_product', 'iu_catalog_remove_product_offers', 99, 1);
        add_filter('woocommerce_structured_data_product_offer', '__return_empty_array', 99, 1);
        add_filter('woocommerce_structured_data', 'iu_catalog_remove_product_offers_generic', 99, 2);
    }

    // 4) Disable access to Cart & Checkout pages (redirect to homepage)
    if (!empty($woocommerce['catalog_disable_cart_checkout'])) {
        add_action('template_redirect', 'iu_catalog_mode_redirect_cart_checkout');
        // Fail-safe: disable gateways and shipping if someone reaches checkout
        add_filter('woocommerce_available_payment_gateways', 'iu_catalog_disable_payment_gateways', 99);
        add_filter('woocommerce_package_rates', 'iu_catalog_disable_shipping_rates', 999, 2);
        // Noindex cart/checkout/account pages
        add_filter('wp_robots', 'iu_catalog_mode_noindex_pages');
        // Remove mini-cart fragments & hide cart widget/icons
        add_action('wp_enqueue_scripts', 'iu_catalog_mode_dequeue_cart_fragments', 100);
        add_filter('woocommerce_add_to_cart_fragments', '__return_empty_array', 999);
        add_filter('woocommerce_widget_cart_is_hidden', '__return_true');
        // Disable misc scripts not needed
        add_action('wp_enqueue_scripts', 'iu_catalog_mode_disable_misc_scripts', 101);
        // Block WooCommerce Store API/REST endpoints for cart/checkout
        add_filter('rest_request_before_callbacks', 'iu_catalog_mode_block_store_api', 10, 3);
        // Also remove price/offers from structured data in Catalog Mode
        add_filter('woocommerce_structured_data_product', 'iu_catalog_remove_product_offers', 99, 1);
        add_filter('woocommerce_structured_data_product_offer', '__return_empty_array', 99, 1);
        add_filter('woocommerce_structured_data', 'iu_catalog_remove_product_offers_generic', 99, 2);
    }

    // 5) Disable Add to Cart functionality and silence success message
    if (!empty($woocommerce['catalog_disable_add_to_cart'])) {
        add_filter('woocommerce_is_purchasable', '__return_false', 99);
        add_filter('woocommerce_add_to_cart_validation', '__return_false', 10, 3);
        add_filter('wc_add_to_cart_message_html', '__return_empty_string', 99);

        // Block add-to-cart via URL and AJAX entirely
        remove_action('wp_loaded', array('WC_Form_Handler', 'add_to_cart_action'), 20);
        // WooCommerce AJAX endpoints (wc-ajax)
        remove_action('wc_ajax_add_to_cart', array('WC_AJAX', 'add_to_cart'));
        remove_action('wc_ajax_nopriv_add_to_cart', array('WC_AJAX', 'add_to_cart'));
        // Legacy WP AJAX fallbacks (if any theme/plugin still uses them)
        remove_action('wp_ajax_woocommerce_add_to_cart', array('WC_AJAX', 'add_to_cart'));
        remove_action('wp_ajax_nopriv_woocommerce_add_to_cart', array('WC_AJAX', 'add_to_cart'));
    }

    // 6) Remove WooCommerce assets globally based on selection
    if (!empty($woocommerce['catalog_remove_wc_css']) || !empty($woocommerce['catalog_remove_wc_js'])) {
        add_action('wp_enqueue_scripts', 'iu_catalog_mode_dequeue_woo_assets', 100);
    }

    // 7) Disable WooCommerce Product Tags taxonomy and UI
    if (!empty($woocommerce['disable_product_tags'])) {
        // Unregister taxonomy after WooCommerce registers it
        add_action('init', 'iu_wc_unregister_product_tags', 100);
        // Hide menu/submenu entry for Product tags
        add_action('admin_menu', 'iu_wc_hide_product_tags_menu', 999);
        // Remove Product tags meta box from product edit screen
        add_action('admin_init', 'iu_wc_remove_product_tags_metabox');
    }
}

// Helper: Detect Husky (WOOF) plugin active/install states robustly
function iu_is_husky_active() {
    if (!function_exists('is_plugin_active')) { @include_once(ABSPATH . 'wp-admin/includes/plugin.php'); }
    if (class_exists('WOOF') || defined('WOOF_VERSION')) {
        return true;
    }
    if (function_exists('is_plugin_active')) {
        if (is_plugin_active('woocommerce-products-filter/woocommerce-products-filter.php') ||
            is_plugin_active('woocommerce-products-filter/index.php')) {
            return true;
        }
    }
    return false;
}

// Husky (WOOF) assets: load only on product archives if enabled
add_action('wp_enqueue_scripts', function(){
    if (is_admin() || wp_doing_ajax()) { return; }
    $settings = get_option('istodata_utilities_settings', array());
    $wc = isset($settings['woocommerce']) ? $settings['woocommerce'] : array();
    if (empty($wc['husky_archive_assets_only'])) { return; }

    // Determine if current is a product archive context
    $is_product_archive = false;
    if (function_exists('is_shop') && function_exists('is_product_taxonomy') && function_exists('is_post_type_archive')) {
        $is_product_archive = is_shop() || is_product_taxonomy() || is_post_type_archive('product');
    }
    if ($is_product_archive) { return; }

    global $wp_scripts, $wp_styles;

    $should_remove = function($handle, $src) {
        if (!$src) return false;
        if (strpos($src, 'woocommerce-products-filter') !== false) return true; // Husky plugin path
        if (stripos($handle, 'woof') === 0) return true; // Husky handles typically start with woof_
        return false;
    };

    // Dequeue + deregister Husky-related scripts (and avoid inline extras printing)
    if ($wp_scripts instanceof WP_Scripts) {
        // Check both queued and registered handles
        $handles = array_unique(array_merge((array) $wp_scripts->queue, array_keys((array) $wp_scripts->registered)));
        foreach ($handles as $handle) {
            if (!isset($wp_scripts->registered[$handle])) continue;
            $src = $wp_scripts->registered[$handle]->src;
            if ($should_remove($handle, $src)) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
            }
        }
    }

    // Dequeue + deregister Husky-related styles
    if ($wp_styles instanceof WP_Styles) {
        $handles = array_unique(array_merge((array) $wp_styles->queue, array_keys((array) $wp_styles->registered)));
        foreach ($handles as $handle) {
            if (!isset($wp_styles->registered[$handle])) continue;
            $src = $wp_styles->registered[$handle]->src;
            if ($should_remove($handle, $src)) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
            }
        }
    }
}, 10000);

// Hard block Husky assets output on non-archive pages (handles late enqueues and inline extras)
add_filter('style_loader_tag', function($html, $handle, $href){
    $settings = get_option('istodata_utilities_settings', array());
    $wc = isset($settings['woocommerce']) ? $settings['woocommerce'] : array();
    if (empty($wc['husky_archive_assets_only'])) { return $html; }
    // Determine if current is a product archive context
    $is_product_archive = false;
    if (function_exists('is_shop') && function_exists('is_product_taxonomy') && function_exists('is_post_type_archive')) {
        $is_product_archive = is_shop() || is_product_taxonomy() || is_post_type_archive('product');
    }
    if ($is_product_archive) { return $html; }
    // Match Husky assets
    $src = is_string($href) ? $href : '';
    if ((stripos($handle, 'woof') === 0) || (is_string($src) && strpos($src, 'woocommerce-products-filter') !== false)) {
        return '';
    }
    return $html;
}, 10, 3);

add_filter('script_loader_tag', function($tag, $handle, $src){
    $settings = get_option('istodata_utilities_settings', array());
    $wc = isset($settings['woocommerce']) ? $settings['woocommerce'] : array();
    if (empty($wc['husky_archive_assets_only'])) { return $tag; }
    // Determine if current is a product archive context
    $is_product_archive = false;
    if (function_exists('is_shop') && function_exists('is_product_taxonomy') && function_exists('is_post_type_archive')) {
        $is_product_archive = is_shop() || is_product_taxonomy() || is_post_type_archive('product');
    }
    if ($is_product_archive) { return $tag; }
    // Match Husky assets
    $s = is_string($src) ? $src : '';
    if ((stripos($handle, 'woof') === 0) || (is_string($s) && strpos($s, 'woocommerce-products-filter') !== false)) {
        return '';
    }
    return $tag;
}, 10, 3);

// Remove WooCommerce Blocks stylesheet when enabled
add_action('wp_enqueue_scripts', function(){
    $settings = get_option('istodata_utilities_settings', array());
    $wc = isset($settings['woocommerce']) ? $settings['woocommerce'] : array();
    if (empty($wc['remove_wc_blocks_css'])) { return; }
    // The handle is 'wc-blocks-style' (WordPress outputs id='wc-blocks-style-css')
    wp_dequeue_style('wc-blocks-style');
    wp_deregister_style('wc-blocks-style');
}, 100);

function iu_wc_unregister_product_tags() {
    if (taxonomy_exists('product_tag')) {
        unregister_taxonomy('product_tag');
    }
}

function iu_wc_hide_product_tags_menu() {
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_tag&post_type=product');
}

function iu_wc_remove_product_tags_metabox() {
    remove_meta_box('tagsdiv-product_tag', 'product', 'side');
}

// Hide shipping rates when free shipping is available, but keep "Local pickup"
function iu_hide_shipping_when_free_available($rates, $package) {
    $new_rates = array();
    foreach ($rates as $rate_id => $rate) {
        // Only modify rates if free_shipping is present
        if ('free_shipping' === $rate->method_id) {
            $new_rates[$rate_id] = $rate;
            break;
        }
    }

    if (!empty($new_rates)) {
        // Save local pickup if it's present
        foreach ($rates as $rate_id => $rate) {
            if ('local_pickup' === $rate->method_id) {
                $new_rates[$rate_id] = $rate;
                break;
            }
        }
        return $new_rates;
    }

    return $rates;
}

// Catalog Mode: Block access to cart/checkout
function iu_catalog_mode_redirect_cart_checkout() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    // Core pages
    if (function_exists('is_cart') && function_exists('is_checkout') && (is_cart() || is_checkout())) {
        wp_redirect(home_url('/'));
        exit;
    }
    // Order-related endpoints under My Account (e.g., order-pay, order-received, view-order, add-payment-method, edit-address)
    if (function_exists('is_account_page') && function_exists('is_wc_endpoint_url') && is_account_page()) {
        $blocked_endpoints = array(
            'view-order',
            'order-pay',
            'order-received',
            'add-payment-method',
            'payment-methods',
            'edit-address',
        );
        foreach ($blocked_endpoints as $ep) {
            if (is_wc_endpoint_url($ep)) {
                wp_redirect(home_url('/'));
                exit;
            }
        }
    }
}

// Fail-safe: disable all gateways and shipping during Catalog Mode
function iu_catalog_disable_payment_gateways($gateways) {
    if (is_admin()) {
        return $gateways;
    }
    return array();
}

function iu_catalog_disable_shipping_rates($rates, $package) {
    if (is_admin()) {
        return $rates;
    }
    return array();
}

// Catalog Mode: add noindex to cart/checkout/account when disabled
function iu_catalog_mode_noindex_pages($robots) {
    if (function_exists('is_cart') && is_cart()) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    if (function_exists('is_checkout') && is_checkout()) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
}

// Catalog Mode: dequeue cart fragments globally when cart/checkout are disabled
function iu_catalog_mode_dequeue_cart_fragments() {
    wp_dequeue_script('wc-cart-fragments');
    wp_deregister_script('wc-cart-fragments');
}

// Catalog Mode: disable miscellaneous scripts that aren’t needed
function iu_catalog_mode_disable_misc_scripts() {
    wp_dequeue_script('wc-password-strength-meter');
    wp_deregister_script('wc-password-strength-meter');
    wp_dequeue_script('jquery-blockui');
    wp_dequeue_script('wc-checkout');
    wp_dequeue_script('wc-address-i18n');
    wp_dequeue_script('wc-country-select');
    wp_dequeue_script('wc-cart');
}

// Catalog Mode: block WooCommerce Store API / REST for cart/checkout
function iu_catalog_mode_block_store_api($response, $handler, $request) {
    $route = method_exists($request, 'get_route') ? $request->get_route() : '';
    if (empty($route)) {
        return $response;
    }
    // Block common Store API routes for cart/checkout and generic WC REST cart/checkout paths
    $patterns = array(
        '#^/wc/store(/v\d+)?/cart#',
        '#^/wc/store(/v\d+)?/checkout#',
        '#^/wc/v\d+/(cart|checkout)#'
    );
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $route)) {
            return new WP_Error('rest_forbidden', __('Catalog Mode: cart/checkout are disabled.', 'istodata-utilities'), array('status' => 403));
        }
    }
    return $response;
}

// Catalog Mode: remove price/offers from structured data
function iu_catalog_remove_product_offers($markup) {
    if (is_array($markup)) {
        unset($markup['offers']);
        // Defensive: remove price keys if present at top-level
        unset($markup['price']);
        unset($markup['priceCurrency']);
    }
    return $markup;
}

function iu_catalog_remove_product_offers_generic($markup, $type) {
    if ($type === 'product' && is_array($markup)) {
        unset($markup['offers']);
        unset($markup['price']);
        unset($markup['priceCurrency']);
    }
    return $markup;
}

// Catalog Mode: Dequeue WooCommerce assets on non-WC pages
function iu_catalog_mode_dequeue_woo_assets() {
    $settings = get_option('istodata_utilities_settings', array());
    $woocommerce = isset($settings['woocommerce']) ? $settings['woocommerce'] : array();

    // Styles
    if (!empty($woocommerce['catalog_remove_wc_css'])) {
        wp_dequeue_style('woocommerce-layout');
        wp_dequeue_style('woocommerce-smallscreen');
        wp_dequeue_style('woocommerce-general');
        wp_dequeue_style('woocommerce-inline');
        wp_dequeue_style('wc-blocks-style');
        wp_dequeue_style('wc-blocks-style-all-products');
        wp_dequeue_style('wc-blocks-vendors-style');
    }

    // Scripts
    if (!empty($woocommerce['catalog_remove_wc_js'])) {
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('woocommerce');
        wp_dequeue_script('wc-add-to-cart');
        wp_dequeue_script('wc-add-to-cart-variation');
        wp_dequeue_script('wc-single-product');
        wp_dequeue_script('jquery-blockui');
        wp_dequeue_script('js-cookie');
    }
}

// WooCommerce: enable attribute taxonomies in Appearance β†’ Menus when option is on
function iu_wc_maybe_enable_attributes_in_menus($taxonomy, $object_type, $args) {
    // Only target product attribute taxonomies (prefix 'pa_')
    if (strpos($taxonomy, 'pa_') !== 0) {
        return;
    }

    // Check setting
    $settings = get_option('istodata_utilities_settings', array());
    $woocommerce = isset($settings['woocommerce']) ? $settings['woocommerce'] : array();
    if (empty($woocommerce['enable_attributes_in_menus'])) {
        return;
    }

    global $wp_taxonomies;
    if (isset($wp_taxonomies[$taxonomy])) {
        $wp_taxonomies[$taxonomy]->show_in_nav_menus = true;
    }
}

// Enable SKU search in WooCommerce
function iu_woocommerce_sku_search($query) {
    if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
        return;
    }

    $search_term = $query->get('s');
    if (empty($search_term)) {
        return;
    }

    // Αναζήτηση με βάση το SKU
    $args = array(
        'post_type'  => 'product',
        'meta_query' => array(
            array(
                'key'     => '_sku',
                'value'   => $search_term,
                'compare' => 'LIKE'
            )
        )
    );

    $sku_query = new WP_Query($args);

    if ($sku_query->have_posts()) {
        $ids = wp_list_pluck($sku_query->posts, 'ID');
        $query->set('post__in', $ids);
    }
}

/**
 * Elementor Loop Grid – WooCommerce product_brand custom order
 * Applies the term menu_order as defined via drag & drop ordering.
 */
function iu_elementor_brand_custom_order($args, $settings, $display_settings) {
    // Work only for the Brands taxonomy
    if (isset($args['taxonomy']) && $args['taxonomy'] === 'product_brand') {
        $args['orderby'] = 'menu_order';
        $args['order']   = 'ASC';
    }
    return $args;
}

/**
 * Force custom order for product_brand terms in generic term queries.
 * Attempts to use 'menu_order' so it aligns with Elementor behavior and
 * Brand plugins that support drag & drop ordering.
 */
function iu_brand_terms_custom_order($args, $taxonomies) {
    // Normalize $taxonomies to array
    $tax_list = is_array($taxonomies) ? $taxonomies : array($taxonomies);
    if (!in_array('product_brand', $tax_list, true)) {
        return $args;
    }

    // Respect explicit orderby if set; otherwise apply our default
    if (empty($args['orderby']) || $args['orderby'] === 'name') {
        $args['orderby'] = 'menu_order';
    }
    if (empty($args['order'])) {
        $args['order'] = 'ASC';
    }
    return $args;
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
    
    if (!empty($dashboard['remove_woocommerce_recent_reviews'])) {
        remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
    }
    
    if (!empty($dashboard['remove_woocommerce_status'])) {
        remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
    }
    
    // Keep Elementor's dashboard registration intact so each widget can be removed independently.
    if (!empty($dashboard['remove_elementor_overview'])) {
        remove_meta_box('e-dashboard-overview', 'dashboard', 'normal');
        remove_meta_box('e-dashboard-overview', 'dashboard', 'side');
    }

    if (!empty($dashboard['remove_elementor_accessibility'])) {
        remove_meta_box('e-dashboard-ally', 'dashboard', 'normal');
        remove_meta_box('e-dashboard-ally', 'dashboard', 'side');
    }

    if (!empty($dashboard['remove_elementor_manage_dashboard'])) {
        remove_meta_box('elementor-manage-dashboard', 'dashboard', 'normal');
        remove_meta_box('elementor-manage-dashboard', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_qode_news'])) {
        remove_meta_box('qode_interactive_dashboard_widget', 'dashboard', 'side');
        remove_meta_box('qi_addons_for_elementor_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('qi_addons_for_elementor_dashboard_widget', 'dashboard', 'side');
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
    
    if (!empty($dashboard['remove_wpmet_stories'])) {
        // Remove Wpmet Stories dashboard widget
        remove_meta_box('wpmet-stories', 'dashboard', 'normal');
        remove_meta_box('wpmet-stories', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_object_cache_pro'])) {
        // Remove Object Cache Pro dashboard widget
        remove_meta_box('dashboard_objectcache', 'dashboard', 'normal');
        remove_meta_box('dashboard_objectcache', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_cookie_compliance'])) {
        // Remove Cookie Compliance dashboard widget
        remove_meta_box('cn_dashboard_stats', 'dashboard', 'normal');
        remove_meta_box('cn_dashboard_stats', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_yoast_posts_overview'])) {
        // Remove Yoast SEO Posts Overview dashboard widget
        remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'normal');
        remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_yoast_wincher_overview'])) {
        // Remove Yoast SEO / Wincher Top Keyphrases dashboard widget
        remove_meta_box('wpseo-wincher-dashboard-overview', 'dashboard', 'normal');
        remove_meta_box('wpseo-wincher-dashboard-overview', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_wp_mail_smtp'])) {
        // Remove WP Mail SMTP dashboard widget
        remove_meta_box('wp_mail_smtp_reports_widget_lite', 'dashboard', 'normal');
        remove_meta_box('wp_mail_smtp_reports_widget_lite', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_wpforms'])) {
        // Remove WPForms dashboard widget
        remove_meta_box('wpforms_reports_widget_lite', 'dashboard', 'normal');
        remove_meta_box('wpforms_reports_widget_lite', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_yith_updates'])) {
        // Remove YITH Latest Updates dashboard widget
        remove_meta_box('yith_dashboard_products_news', 'dashboard', 'normal');
        remove_meta_box('yith_dashboard_products_news', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_yith_blog_news'])) {
        // Remove Latest news from YITH Blog dashboard widget
        remove_meta_box('yith_dashboard_blog_news', 'dashboard', 'normal');
        remove_meta_box('yith_dashboard_blog_news', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_fluent_forms'])) {
        // Remove Fluent Forms Latest Form Submissions dashboard widget
        remove_meta_box('fluentform_stat_widget', 'dashboard', 'normal');
        remove_meta_box('fluentform_stat_widget', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_pixelwars'])) {
        // Remove Pixelwars dashboard widget
        remove_meta_box('pixelwars_core__add_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('pixelwars_core__add_dashboard_widget', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_siteorigin_news'])) {
        // Remove SiteOrigin Page Builder News dashboard widget
        remove_meta_box('so-dashboard-news', 'dashboard', 'normal');
        remove_meta_box('so-dashboard-news', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_themeisle'])) {
        // Remove WordPress Guides/Tutorials (ThemeIsle) dashboard widget
        remove_meta_box('themeisle', 'dashboard', 'normal');
        remove_meta_box('themeisle', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_webappick_news'])) {
        // Remove Latest News from WebAppick Blog dashboard widget
        remove_meta_box('aaaa_webappick_latest_news_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('aaaa_webappick_latest_news_dashboard_widget', 'dashboard', 'side');
    }
    
    if (!empty($dashboard['remove_quadlayers_news'])) {
        // Remove QuadLayers News dashboard widget
        remove_meta_box('wp-dashboard-widget-news', 'dashboard', 'normal');
        remove_meta_box('wp-dashboard-widget-news', 'dashboard', 'side');
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

        if (!empty($dashboard['remove_elementor_overview'])) {
            echo '<style>
                #e-dashboard-overview, [id*="e-dashboard-overview"], [class*="e-dashboard-overview"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#e-dashboard-overview, [id*=\'e-dashboard-overview\'], [class*=\'e-dashboard-overview\']").remove();
                });
            </script>';
        }

        if (!empty($dashboard['remove_elementor_accessibility'])) {
            echo '<style>
                #e-dashboard-ally, [id*="e-dashboard-ally"], [class*="e-dashboard-ally"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#e-dashboard-ally, [id*=\'e-dashboard-ally\'], [class*=\'e-dashboard-ally\']").remove();
                });
            </script>';
        }

        if (!empty($dashboard['remove_elementor_manage_dashboard'])) {
            echo '<style>
                #elementor-manage-dashboard, [id*="elementor-manage-dashboard"], [class*="elementor-manage-dashboard"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#elementor-manage-dashboard, [id*=\'elementor-manage-dashboard\'], [class*=\'elementor-manage-dashboard\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_rank_math_overview'])) {
            echo '<style>
                #rank_math_dashboard_widget,
                #rank-math-dashboard-widget,
                #rankmath_dashboard_widget,
                [id^="rank_math_dashboard"],
                [id^="rank-math-dashboard"] { display: none !important; }
            </style>';
            echo "<script>
                jQuery(document).ready(function($) {
                    $('#rank_math_dashboard_widget, #rank-math-dashboard-widget, #rankmath_dashboard_widget, [id^=\"rank_math_dashboard\"], [id^=\"rank-math-dashboard\"]').remove();
                });
            </script>";
        }
        
        if (!empty($dashboard['remove_wpmet_stories'])) {
            echo '<style>
                #wpmet-stories, [id*="wpmet-stories"], [class*="wpmet-stories"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#wpmet-stories, [id*=\'wpmet-stories\'], [class*=\'wpmet-stories\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_object_cache_pro'])) {
            echo '<style>
                #dashboard_objectcache, [id*="dashboard_objectcache"], [class*="objectcache"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#dashboard_objectcache, [id*=\'dashboard_objectcache\'], [class*=\'objectcache\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_cookie_compliance'])) {
            echo '<style>
                #cn_dashboard_stats, [id*="cn_dashboard_stats"], [class*="cookie-compliance"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#cn_dashboard_stats, [id*=\'cn_dashboard_stats\'], [class*=\'cookie-compliance\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_yoast_posts_overview'])) {
            echo '<style>
                #wpseo-dashboard-overview, [id*="wpseo-dashboard-overview"], [class*="yoast-dashboard"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#wpseo-dashboard-overview, [id*=\'wpseo-dashboard-overview\'], [class*=\'yoast-dashboard\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_yoast_wincher_overview'])) {
            echo '<style>
                #wpseo-wincher-dashboard-overview, [id*="wpseo-wincher-dashboard-overview"], [class*="wincher-dashboard"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#wpseo-wincher-dashboard-overview, [id*=\'wpseo-wincher-dashboard-overview\'], [class*=\'wincher-dashboard\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_wp_mail_smtp'])) {
            echo '<style>
                #wp_mail_smtp_reports_widget_lite, [id*="wp_mail_smtp_reports"], [class*="wp-mail-smtp"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#wp_mail_smtp_reports_widget_lite, [id*=\'wp_mail_smtp_reports\'], [class*=\'wp-mail-smtp\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_wpforms'])) {
            echo '<style>
                #wpforms_reports_widget_lite, [id*="wpforms_reports"], [class*="wpforms-dashboard"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#wpforms_reports_widget_lite, [id*=\'wpforms_reports\'], [class*=\'wpforms-dashboard\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_yith_updates'])) {
            echo '<style>
                #yith_dashboard_products_news, [id*="yith_dashboard_products"], [class*="yith-updates"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#yith_dashboard_products_news, [id*=\'yith_dashboard_products\'], [class*=\'yith-updates\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_yith_blog_news'])) {
            echo '<style>
                #yith_dashboard_blog_news, [id*="yith_dashboard_blog"], [class*="yith-blog"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#yith_dashboard_blog_news, [id*=\'yith_dashboard_blog\'], [class*=\'yith-blog\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_fluent_forms'])) {
            echo '<style>
                #fluentform_stat_widget, [id*="fluentform_stat"], [class*="fluent-forms"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#fluentform_stat_widget, [id*=\'fluentform_stat\'], [class*=\'fluent-forms\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_pixelwars'])) {
            echo '<style>
                #pixelwars_core__add_dashboard_widget, [id*="pixelwars_core"], [class*="pixelwars"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#pixelwars_core__add_dashboard_widget, [id*=\'pixelwars_core\'], [class*=\'pixelwars\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_siteorigin_news'])) {
            echo '<style>
                #so-dashboard-news, [id*="so-dashboard"], [class*="siteorigin"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#so-dashboard-news, [id*=\'so-dashboard\'], [class*=\'siteorigin\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_themeisle'])) {
            echo '<style>
                #themeisle, [id*="themeisle"], [class*="themeisle"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#themeisle, [id*=\'themeisle\'], [class*=\'themeisle\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_webappick_news'])) {
            echo '<style>
                #aaaa_webappick_latest_news_dashboard_widget, [id*="webappick_latest_news"], [class*="webappick"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#aaaa_webappick_latest_news_dashboard_widget, [id*=\'webappick_latest_news\'], [class*=\'webappick\']").remove();
                });
            </script>';
        }
        
        if (!empty($dashboard['remove_quadlayers_news'])) {
            echo '<style>
                #wp-dashboard-widget-news, [id*="dashboard-widget-news"], [class*="quadlayers"] { display: none !important; }
            </style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#wp-dashboard-widget-news, [id*=\'dashboard-widget-news\'], [class*=\'quadlayers\']").remove();
                });
            </script>';
        }
    });
    
    // Add ISTODATA widgets if hosting is enabled
    $settings = get_option('istodata_utilities_settings', array());
    if (!empty($settings['hosting']['istodata_hosted'])) {
        // Simple locale detection for widget titles
        $user_locale = get_user_locale();
        $is_greek = (strpos($user_locale, 'el') === 0);
        
        $dashboard_icon = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/images/favicon.svg') . '" alt="" aria-hidden="true" style="width:21px;height:21px;object-fit:contain;display:block;flex:0 0 21px;" />';
        $support_title_text = $is_greek ? 'ISTODATA Τεχνική Υποστήριξη' : 'ISTODATA Technical Support';
        $support_title = '<span style="display:inline-flex;align-items:center;gap:8px;">' . $dashboard_icon . esc_html($support_title_text) . '</span>';
        $storage_title_text = $is_greek ? 'Αποθηκευτικός Χώρος' : 'Storage Space';
        $storage_title = '<span style="display:inline-flex;align-items:center;gap:8px;">' . $dashboard_icon . esc_html($storage_title_text) . '</span>';
        
        wp_add_dashboard_widget(
            'iu_support_widget',
            $support_title,
            'iu_support_widget_content'
        );
        
        wp_add_dashboard_widget(
            'iu_storage_widget',
            $storage_title,
            'iu_storage_widget_content'
        );
    }
}

function iu_support_widget_content() {
    // Simple locale detection for support widget
    $user_locale = get_user_locale();
    $is_greek = (strpos($user_locale, 'el') === 0);
    
    if ($is_greek) {
        $intro = 'Φροντίζουμε καθημερινά την ομαλή λειτουργία της ιστοσελίδας σας.<br>Αν χρειαστείτε βοήθεια, μπορείτε να υποβάλετε το αίτημά σας μέσω της φόρμας υποστήριξης.<br>Κάθε αίτημα αξιολογείται και δρομολογείται με βάση τη φύση και τον αντίκτυπό του.';
    } else {
        $intro = 'We look after your website every day to keep it running smoothly.<br>If you need help, you can submit your request via the support form.<br>Each request is reviewed and routed based on its nature and impact.';
    }
    // Build dynamic ticket URL with current domain
    $home_url = home_url();
    $site_host = parse_url($home_url, PHP_URL_HOST);
    if (empty($site_host) && !empty($_SERVER['HTTP_HOST'])) {
        $site_host = wp_unslash($_SERVER['HTTP_HOST']);
    }
    // Build support URL with prefilled params
    $base_support_url = 'https://www.istodata.com/support/';
    $query_args = array(
        'website' => $site_host,
    );
    // Try to prefill user info
    $current_user = wp_get_current_user();
    if ($current_user && $current_user->exists()) {
        if (!empty($current_user->user_email)) {
            $query_args['email'] = $current_user->user_email;
        }
        // Prefer profile fields; fallback to user meta if empty
        $first_name = !empty($current_user->user_firstname) ? $current_user->user_firstname : get_user_meta($current_user->ID, 'first_name', true);
        $last_name  = !empty($current_user->user_lastname) ? $current_user->user_lastname : get_user_meta($current_user->ID, 'last_name', true);
        if (!empty($first_name)) {
            $query_args['firstname'] = $first_name;
        }
        if (!empty($last_name)) {
            $query_args['lastname'] = $last_name;
        }
    }
    $ticket_url = add_query_arg($query_args, $base_support_url);

    ?>
    <p><?php echo $intro; ?></p>
    <?php
    // CTA group: Create Ticket (primary) + Documentation (secondary, if set)
    $settings = get_option('istodata_utilities_settings', array());
    $doc_url = isset($settings['dashboard']['documentation_url']) ? trim($settings['dashboard']['documentation_url']) : '';
    $doc_label = $is_greek ? 'Προβολή Οδηγού Διαχείρισης' : 'View Documentation';
    $create_ticket_label = $is_greek ? 'Αίτημα Υποστήριξης' : 'Support Request';
    ?>
    <hr style="border:0;border-top:1px solid #eee;margin:12px 0;" />
    <p>
        <a class="button button-primary" target="_blank" rel="noopener" href="<?php echo esc_url($ticket_url); ?>"><?php echo esc_html($create_ticket_label); ?></a>
        <?php if (!empty($doc_url)): ?>
            <a class="button button-secondary" style="margin-left:6px;" target="_blank" rel="noopener" href="<?php echo esc_url($doc_url); ?>"><?php echo esc_html($doc_label); ?></a>
        <?php endif; ?>
    </p>
    <?php
}

function iu_storage_widget_content() {
    // Simple locale detection for storage widget
    $user_locale = get_user_locale();
    $is_greek = (strpos($user_locale, 'el') === 0);
    
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
            <td><strong><?php echo $is_greek ? 'Όριο:' : 'Limit:'; ?></strong></td>
            <td><?php echo esc_html($limit); ?> GB</td>
        </tr>
        <tr>
            <td><strong><?php echo $is_greek ? 'Σε Χρήση:' : 'In Use:'; ?></strong></td>
            <td><?php echo iu_format_bytes($breakdown['total']); ?></td>
        </tr>
        <tr>
            <td><strong><?php echo $is_greek ? 'Διαθέσιμο:' : 'Available:'; ?></strong></td>
            <td><?php echo iu_format_bytes($available_bytes); ?></td>
        </tr>
    </table>
    
    <div style="margin: 15px 0;">
        <div style="background: #f5f5f5; height: 20px; border-radius: 10px; overflow: hidden;">
            <div style="background: <?php echo $color; ?>; height: 100%; width: <?php echo min(100, $percentage); ?>%; transition: width 0.3s;"></div>
        </div>
        <p style="text-align: center; margin: 5px 0 0 0;"><strong><?php echo number_format($percentage, 1); ?>%</strong> <?php echo $is_greek ? 'του διαθέσιμου χώρου' : 'of available space'; ?></p>
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
    
    // Calculate files with dynamic overhead
    $overhead_multiplier = iu_calculate_overhead_multiplier($files_raw_bytes);
    $files_with_overhead = $files_raw_bytes * $overhead_multiplier;
    
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
// Calculate dynamic overhead multiplier based on file size
function iu_calculate_overhead_multiplier($bytes) {
    $gb = $bytes / (1024 * 1024 * 1024);
    
    if ($gb < 0.5) return 1.25;      // 25%
    if ($gb < 1) return 1.20;        // 20%
    if ($gb < 2) return 1.15;        // 15%
    if ($gb < 4) return 1.12;        // 12%
    if ($gb < 6) return 1.10;        // 10%
    if ($gb < 8) return 1.08;        // 8%
    if ($gb < 10) return 1.07;       // 7%
    if ($gb < 12) return 1.06;       // 6%
    if ($gb < 15) return 1.05;       // 5%
    return 1.04;                     // 4% για >=15GB
}

function iu_get_storage_breakdown() {
    $files_with_overhead = get_option('iu_storage_files', 0);
    $database = get_option('iu_storage_database', 0);
    $total = get_option('iu_storage_used', 0);
    
    // Calculate original file size and overhead percentage for display
    $files_raw = $files_with_overhead;
    $overhead_percent = 0;
    
    // Use iterative approach to find original size
    for ($i = 0; $i < 10; $i++) {
        $multiplier = iu_calculate_overhead_multiplier($files_raw);
        $calculated_with_overhead = $files_raw * $multiplier;
        
        if (abs($calculated_with_overhead - $files_with_overhead) < 1024) {
            $overhead_percent = round(($multiplier - 1) * 100);
            break;
        }
        $files_raw = $files_with_overhead / $multiplier;
    }
    
    return array(
        'files_with_overhead' => $files_with_overhead,
        'database' => $database,
        'total' => $total,
        'overhead_percent' => $overhead_percent
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
    
    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) return;
    
    // Get user's dismissal data
    $dismissed_data = get_user_meta($user_id, 'iu_storage_warning_dismissed', true);
    if (!is_array($dismissed_data)) {
        $dismissed_data = array();
    }
    
    $message = '';
    $class = '';
    $warning_level = '';
    
    if ($percentage >= 100) {
        $message = 'Έχετε εξαντλήσει τον αποθηκευτικό σας χώρο. Παρακαλούμε επικοινωνήστε με το Helpdesk για να αναβαθμίσετε το πακέτο φιλοξενίας σας.';
        $class = 'notice-error';
        $warning_level = 'critical';
        
    } elseif ($percentage >= 90) {
        $message = sprintf('Χρησιμοποιείται το %.1f%% του αποθηκευτικού χώρου σας!', $percentage);
        $class = 'notice-error';
        $warning_level = 'high';
        
    } elseif ($percentage >= 80) {
        $message = sprintf('Χρησιμοποιείται το %.1f%% του αποθηκευτικού χώρου σας!', $percentage);
        $class = 'notice-warning';
        $warning_level = 'medium';
        
    } else {
        return; // No warning needed
    }
    
    // Check if we should show the warning based on dismissal rules
    if (!iu_should_show_storage_warning($dismissed_data, $warning_level, $percentage)) {
        return;
    }
    
    if ($message) {
        echo '<div class="notice ' . $class . ' is-dismissible" data-dismissible="iu-storage-warning-' . $warning_level . '">';
        echo '<p><strong>⚠️ ΠΡΟΣΟΧΗ:</strong> ' . $message . '</p>';
        echo '</div>';
    }
}

// Check if storage warning should be shown based on dismissal rules
function iu_should_show_storage_warning($dismissed_data, $warning_level, $current_percentage) {
    if (empty($dismissed_data[$warning_level])) {
        return true; // Never dismissed, show it
    }
    
    $dismissed_info = $dismissed_data[$warning_level];
    $dismissed_time = isset($dismissed_info['time']) ? $dismissed_info['time'] : 0;
    $dismissed_percentage = isset($dismissed_info['percentage']) ? $dismissed_info['percentage'] : 0;
    
    $now = time();
    $time_passed = $now - $dismissed_time;
    
    // Define time limits for each warning level
    $time_limits = array(
        'critical' => 0,        // Always show critical warnings (β‰¥100%)
        'high' => 24 * 3600,    // 24 hours (90-99%)
        'medium' => 7 * 24 * 3600  // 1 week (80-89%)
    );
    
    $time_limit = isset($time_limits[$warning_level]) ? $time_limits[$warning_level] : 24 * 3600;
    
    // Show warning if:
    // 1. Situation got worse (percentage increased significantly)
    // 2. Time limit has passed
    if ($current_percentage > $dismissed_percentage + 2 || $time_passed > $time_limit) {
        return true;
    }
    
    return false;
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
add_action('admin_enqueue_scripts', 'iu_admin_scripts', 999);
function iu_admin_scripts($hook) {
    // Load CSS on all admin pages with high priority to load last
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
    
    
    if (!empty($additional['greeklish_permalinks'])) {
        iu_init_greeklish_permalinks();
    }
    
    if (!empty($additional['duplicate_post_link'])) {
        iu_init_duplicate_post_link();
    }
    
    if (!empty($additional['elementor_reading_time'])) {
        iu_init_elementor_reading_time();
    }
    
    if (!empty($additional['rank_math_remove_categories'])) {
        iu_init_rank_math_remove_categories();
    }
    
    if (!empty($additional['typed_js'])) {
        add_action('wp_enqueue_scripts', 'iu_enqueue_typed_js');
    }

    // If requested, exclude Typed.js from WP Rocket's Delay JS
    if (!empty($additional['typed_js']) && !empty($additional['typed_js_wp_rocket_exclude']) && defined('WP_ROCKET_VERSION')) {
        add_filter('rocket_delay_js_exclusions', 'iu_wp_rocket_exclude_typed_js');
    }

    // Initialize Elementor Image Gallery (metabox + dynamic tag integration side)
    if (!empty($additional['elementor_image_gallery'])) {
        add_action('add_meta_boxes', 'iu_register_isto_gallery_metabox');
        add_action('save_post', 'iu_save_isto_gallery_meta');
        add_action('admin_enqueue_scripts', 'iu_enqueue_isto_gallery_admin_assets');
    }
}

// Render and handle the ISTO Gallery metabox
function iu_register_isto_gallery_metabox() {
    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    $pts = isset($additional['elementor_image_gallery_post_types']) && is_array($additional['elementor_image_gallery_post_types'])
        ? $additional['elementor_image_gallery_post_types'] : array();

    if (empty($pts)) {
        return;
    }

    foreach ($pts as $pt) {
        if ($pt === 'e-floating-buttons') { continue; }
        add_meta_box(
            'iu_isto_image_gallery',
            __('Image Gallery', 'istodata-utilities'),
            'iu_render_isto_gallery_metabox',
            $pt,
            'normal',
            'default'
        );
    }
}

function iu_render_isto_gallery_metabox($post) {
    wp_nonce_field('iu_isto_gallery_save', 'iu_isto_gallery_nonce');
    $ids = get_post_meta($post->ID, '_isto_gallery_ids', true);
    if (!is_array($ids)) {
        // support stored as CSV if ever needed
        $ids = is_string($ids) ? array_filter(array_map('absint', explode(',', $ids))) : array();
    }
    echo '<div id="iu-isto-gallery-metabox">';
    echo '<input type="hidden" id="iu_isto_gallery_ids" name="iu_isto_gallery_ids" value="' . esc_attr(implode(',', $ids)) . '" />';
    echo '<button type="button" class="button" id="iu_isto_gallery_select">' . esc_html__('Επιλογή εικόνων', 'istodata-utilities') . '</button> ';
    echo '<button type="button" class="button" id="iu_isto_gallery_clear" style="margin-left:6px;">' . esc_html__('Καθαρισμός', 'istodata-utilities') . '</button>';
    echo '<ul id="iu_isto_gallery_list" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:10px;">';
    if (!empty($ids)) {
        foreach ($ids as $id) {
            $thumb = wp_get_attachment_image_url($id, 'thumbnail');
            if (!$thumb) { continue; }
            echo '<li class="iu-isto-gallery-item" data-id="' . esc_attr($id) . '" style="width:90px; position:relative; cursor:move;">';
            echo '<img src="' . esc_url($thumb) . '" style="width:100%; height:auto; display:block; border:1px solid #ccd0d4; border-radius:2px;" />';
            echo '<a href="#" class="iu-isto-remove" title="' . esc_attr__('Αφαίρεση', 'istodata-utilities') . '" style="position:absolute; top:4px; right:4px; background:#b32d2e; color:#fff; text-decoration:none; border-radius:2px; padding:0 5px; line-height:20px;">×</a>';
            echo '</li>';
        }
    }
    echo '</ul>';
    echo '<p style="color:#666;">' . esc_html__('Σύρετε για αλλαγή σειράς. Χρησιμοποιήστε το κουμπί για να προσθέσετε/ενημερώσετε εικόνες.', 'istodata-utilities') . '</p>';
    echo '</div>';
}

function iu_save_isto_gallery_meta($post_id) {
    // Nonce and capability checks
    if (!isset($_POST['iu_isto_gallery_nonce']) || !wp_verify_nonce($_POST['iu_isto_gallery_nonce'], 'iu_isto_gallery_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    $post_type = get_post_type($post_id);
    $pto = get_post_type_object($post_type);
    if (!$pto || !current_user_can($pto->cap->edit_post, $post_id)) {
        return;
    }

    $raw = isset($_POST['iu_isto_gallery_ids']) ? wp_unslash($_POST['iu_isto_gallery_ids']) : '';
    $ids = array();
    if (is_string($raw) && $raw !== '') {
        foreach (explode(',', $raw) as $maybe) {
            $v = absint($maybe);
            if ($v > 0) { $ids[] = $v; }
        }
    }

    if (!empty($ids)) {
        update_post_meta($post_id, '_isto_gallery_ids', $ids);
    } else {
        delete_post_meta($post_id, '_isto_gallery_ids');
    }
}

function iu_enqueue_isto_gallery_admin_assets($hook) {
    // Only load on post editor screens
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    $screen = get_current_screen();
    if (!$screen) { return; }

    $settings = get_option('istodata_utilities_settings', array());
    $additional = isset($settings['additional']) ? $settings['additional'] : array();
    if (empty($additional['elementor_image_gallery'])) { return; }
    $pts = isset($additional['elementor_image_gallery_post_types']) && is_array($additional['elementor_image_gallery_post_types'])
        ? $additional['elementor_image_gallery_post_types'] : array();
    if (empty($pts) || !in_array($screen->post_type, $pts, true) || $screen->post_type === 'e-floating-buttons') {
        return;
    }

    // Enqueue media and sortable
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
    // Our admin script
    wp_enqueue_script(
        'iu-isto-gallery-metabox',
        plugin_dir_url(__FILE__) . 'assets/js/iu-gallery-metabox.js',
        array('jquery', 'jquery-ui-sortable'),
        IU_PLUGIN_VERSION,
        true
    );
}

// Initialize Greeklish Permalinks functionality
function iu_init_greeklish_permalinks() {
    // Main hook for title sanitization - this catches slugs for posts, pages, taxonomies
    add_filter('sanitize_title', 'iu_greeklish_sanitize_title', 9, 3);
}

// Convert Greek characters to Latin (Greeklish) in sanitize_title
function iu_greeklish_sanitize_title($title, $raw_title, $context) {
    return iu_convert_to_greeklish($title);
}

// Main Greeklish conversion function
function iu_convert_to_greeklish($text) {
    if (empty($text)) {
        return $text;
    }
    
    // Greek to Latin character mapping
    $greek_to_latin = array(
        // Lowercase
        'α' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'i', 'θ' => 'th',
        'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'π' => 'p',
        'ρ' => 'r', 'σ' => 's', 'ς' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'ch', 'ψ' => 'ps', 'ω' => 'o',
        
        // Uppercase
        'Α' => 'A', 'Β' => 'V', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'I', 'Θ' => 'TH',
        'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => 'X', 'Ο' => 'O', 'Π' => 'P',
        'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'CH', 'Ψ' => 'PS', 'Ω' => 'O',
        
        // Accented characters
        'ά' => 'a', 'έ' => 'e', 'ή' => 'i', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ώ' => 'o',
        'Ά' => 'A', 'Έ' => 'E', 'Ή' => 'I', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ώ' => 'O',
        'ΐ' => 'i', 'ΰ' => 'y', 'Ϊ' => 'I', 'Ϋ' => 'Y'
    );
    
    // Double character combinations (must be processed first)
    $double_chars = array(
        'ου' => 'ou', 'ού' => 'ou',
        'ΟΥ' => 'OU', 'Ού' => 'Ou', 'Ου' => 'Ou',
        'μπ' => 'mp', 'ντ' => 'nt', 'γκ' => 'gk', 'γγ' => 'gg', 'τσ' => 'ts', 'τζ' => 'tz',
        'ΜΠ' => 'MP', 'ΝΤ' => 'NT', 'ΓΚ' => 'GK', 'ΓΓ' => 'GG', 'ΤΣ' => 'TS', 'ΤΖ' => 'TZ',
        'Μπ' => 'Mp', 'Ντ' => 'Nt', 'Γκ' => 'Gk', 'Γγ' => 'Gg', 'Τσ' => 'Ts', 'Τζ' => 'Tz'
    );
    
    // First process double character combinations
    $text = str_replace(array_keys($double_chars), array_values($double_chars), $text);
    
    // Then process single characters
    $text = str_replace(array_keys($greek_to_latin), array_values($greek_to_latin), $text);
    
    return $text;
}

// Initialize Duplicate Post/Page Link functionality
function iu_init_duplicate_post_link() {
    // Add duplicate button to post/page list of actions
    add_filter('post_row_actions', 'iu_duplicate_post_link', 10, 2);
    add_filter('page_row_actions', 'iu_duplicate_post_link', 10, 2);
    
    // Handle the custom action when clicking the duplicate button
    add_action('admin_action_iu_duplicate_post', 'iu_handle_duplicate_post_action');
}

// Add duplicate link to post/page actions
function iu_duplicate_post_link($actions, $post) {
    // Don't add action if the current user can't create posts of this post type
    $post_type_object = get_post_type_object($post->post_type);
    
    if (null === $post_type_object || !current_user_can($post_type_object->cap->create_posts)) {
        return $actions;
    }
    
    $url = wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'iu_duplicate_post',
                'post_id' => $post->ID,
            ),
            'admin.php'
        ),
        'iu_duplicate_post_' . $post->ID,
        'iu_duplicate_nonce'
    );
    
    $actions['iu_duplicate'] = '<a href="' . $url . '" title="Duplicate item" rel="permalink">Duplicate</a>';
    
    return $actions;
}

// Handle the duplicate post action
function iu_handle_duplicate_post_action() {
    if (empty($_GET['post_id'])) {
        wp_die('No post id set for the duplicate action.');
    }
    
    $post_id = absint($_GET['post_id']);
    
    // Check the nonce specific to the post we are duplicating
    if (!isset($_GET['iu_duplicate_nonce']) || !wp_verify_nonce($_GET['iu_duplicate_nonce'], 'iu_duplicate_post_' . $post_id)) {
        wp_die('The link you followed has expired, please try again.');
    }
    
    // Load the post we want to duplicate
    $post = get_post($post_id);
    
    if ($post) {
        $current_user = wp_get_current_user();
        $new_post = array(
            'comment_status' => $post->comment_status,
            'menu_order' => $post->menu_order,
            'ping_status' => $post->ping_status,
            'post_author' => $current_user->ID,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_name' => $post->post_name,
            'post_parent' => $post->post_parent,
            'post_password' => $post->post_password,
            'post_status' => 'draft',
            'post_title' => $post->post_title . ' (copy)', // Add "(copy)" to the title
            'post_type' => $post->post_type,
            'to_ping' => $post->to_ping,
        );
        
        // Create the new post
        $duplicate_id = wp_insert_post($new_post);
        
        // Copy the taxonomy terms
        $taxonomies = get_object_taxonomies(get_post_type($post));
        if ($taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms($duplicate_id, $post_terms, $taxonomy);
            }
        }
        
        // Copy all the custom fields
        $post_meta = get_post_meta($post_id);
        if ($post_meta) {
            foreach ($post_meta as $meta_key => $meta_values) {
                if ('_wp_old_slug' === $meta_key) { // skip old slug
                    continue;
                }
                foreach ($meta_values as $meta_value) {
                    add_post_meta($duplicate_id, $meta_key, $meta_value);
                }
            }
        }
        
        // Redirect to edit the new post
        wp_safe_redirect(
            add_query_arg(
                array(
                    'action' => 'edit',
                    'post' => $duplicate_id
                ),
                admin_url('post.php')
            )
        );
        exit;
    } else {
        wp_die('Error loading post for duplication, please try again.');
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

// Enqueue Typed.js only when an element with id "typed" exists on the page
function iu_enqueue_typed_js() {
    $typed_src = esc_url( plugins_url('assets/js/typed.js', __FILE__) );

    // Create a lightweight inline loader printed in the footer that
    // appends Typed.js only if the element with id "typed" is present.
    // This avoids loading the library on pages where it's not used.
    wp_register_script('iu-typed-conditional-loader', '', array(), null, true);
    wp_enqueue_script('iu-typed-conditional-loader');

    $inline = "/* iu-typed-loader */\n".
        "(function(){\n".
        "  var injected = false;\n".
        "  function inject() {\n".
        "    if (injected) return;\n".
        "    if (!document.getElementById('typed')) return;\n".
        "    injected = true;\n".
        "    var s = document.createElement('script');\n".
        "    s.src = '" . $typed_src . "';\n".
        "    s.async = true;\n".
        "    document.head.appendChild(s);\n".
        "  }\n".
        "  function checkNow(){ inject(); }\n".
        "  if (document.readyState === 'loading') {\n".
        "    document.addEventListener('DOMContentLoaded', checkNow);\n".
        "  } else {\n".
        "    checkNow();\n".
        "  }\n".
        "  // Also observe DOM mutations briefly in case #typed is injected late (e.g., by a builder).\n".
        "  var mo;\n".
        "  try {\n".
        "    mo = new MutationObserver(checkNow);\n".
        "    mo.observe(document.documentElement, { childList: true, subtree: true });\n".
        "    setTimeout(function(){ if (mo) mo.disconnect(); }, 5000);\n".
        "  } catch(e) { /* MutationObserver not available; ignore */ }\n".
        "})();";

    wp_add_inline_script('iu-typed-conditional-loader', $inline);
}

// Optionally exclude Typed.js from WP Rocket Delay JS when enabled
function iu_wp_rocket_exclude_typed_js($patterns) {
    // Add common patterns to prevent delaying the library and its usage
    $patterns[] = 'typed.js';                 // the library file
    $patterns[] = 'new Typed';                // inline usage
    $patterns[] = 'iu-typed-loader';          // our inline loader marker
    return $patterns;
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
            'icons' => array(
                '1x' => IU_PLUGIN_URL . 'assets/images/icon-128x128.png',
                '2x' => IU_PLUGIN_URL . 'assets/images/icon-256x256.png',
            ),
        );
    }
    
    return $transient;
}

// Get remote version information from GitHub
function iu_get_remote_version() {
    // Check cache first
    $cache_key = 'iu_remote_version_' . md5(IU_GITHUB_API_URL);
    $fallback_key = 'iu_remote_version_fallback_' . md5(IU_GITHUB_API_URL);
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
                'name' => 'ISTODATA Kit',
                'slug' => IU_PLUGIN_SLUG,
                'author' => 'ISTODATA',
                'homepage' => IU_GITHUB_REPO_URL,
                'requires' => '5.0',
                'tested' => '6.8.1',
                'requires_php' => '7.4',
                'last_updated' => $github_data->published_at,
                'description' => 'Εργαλεία διαχείρισης, βελτιστοποιήσεις και πρόσθετες λειτουργίες από την ISTODATA.',
                'changelog' => isset($github_data->body) ? $github_data->body : 'See GitHub release notes.',
                'download_url' => $download_url
            );
            
            // Cache successful release metadata to reduce GitHub API usage across shared-IP hosting.
            set_transient($cache_key, $data, DAY_IN_SECONDS);
            update_option($fallback_key, $data, false);
            return $data;
        }
    }

    // If GitHub is temporarily unavailable or rate-limited, reuse the last known good release metadata.
    $fallback_data = get_option($fallback_key, false);
    if ($fallback_data !== false) {
        set_transient($cache_key, $fallback_data, 6 * HOUR_IN_SECONDS);
        return $fallback_data;
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
            $version_after = defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : 'unknown';
            
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
            $message = __('Auto-updates enabled for ISTODATA Kit.');
        } else {
            $auto_updates = array_diff($auto_updates, array($plugin));
            update_site_option('auto_update_plugins', $auto_updates);
            $message = __('Auto-updates disabled for ISTODATA Kit.');
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

// Handle AJAX request for dismissing storage warnings
add_action('wp_ajax_iu_dismiss_storage_warning', 'iu_handle_dismiss_storage_warning');
function iu_handle_dismiss_storage_warning() {
    // Check nonce for security
    if (!check_ajax_referer('iu_dismiss_storage_warning', 'nonce', false)) {
        wp_die('Security check failed');
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_die('User not logged in');
    }
    
    $warning_level = sanitize_text_field($_POST['warning_level']);
    $current_percentage = floatval($_POST['current_percentage']);
    
    // Get existing dismissal data
    $dismissed_data = get_user_meta($user_id, 'iu_storage_warning_dismissed', true);
    if (!is_array($dismissed_data)) {
        $dismissed_data = array();
    }
    
    // Store dismissal info
    $dismissed_data[$warning_level] = array(
        'time' => time(),
        'percentage' => $current_percentage
    );
    
    update_user_meta($user_id, 'iu_storage_warning_dismissed', $dismissed_data);
    
    wp_die('Success');
}

// Add JavaScript for handling dismissible storage warnings
add_action('admin_footer', 'iu_storage_warning_script');
function iu_storage_warning_script() {
    $settings = get_option('istodata_utilities_settings', array());
    
    if (!isset($settings['hosting']['istodata_hosted']) || !$settings['hosting']['istodata_hosted']) {
        return;
    }
    
    // Get current storage percentage for JavaScript
    $breakdown = iu_get_storage_breakdown();
    $limit = isset($settings['hosting']['storage_limit']) ? $settings['hosting']['storage_limit'] : 5.0;
    $limit_bytes = $limit * 1024 * 1024 * 1024;
    $percentage = $limit_bytes > 0 ? min(100, ($breakdown['total'] / $limit_bytes) * 100) : 0;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle dismissal of storage warnings
        $(document).on('click', '.notice[data-dismissible^="iu-storage-warning-"] .notice-dismiss', function() {
            var notice = $(this).closest('.notice');
            var dismissibleData = notice.attr('data-dismissible');
            var warningLevel = dismissibleData.replace('iu-storage-warning-', '');
            
            $.post(ajaxurl, {
                action: 'iu_dismiss_storage_warning',
                warning_level: warningLevel,
                current_percentage: <?php echo $percentage; ?>,
                nonce: '<?php echo wp_create_nonce('iu_dismiss_storage_warning'); ?>'
            });
        });
    });
    </script>
    <?php
}

// Remove Format metabox
function iu_remove_format_metabox() {
    remove_meta_box('formatdiv', 'post', 'side');
}

// Remove Tags metabox
function iu_remove_tags_metabox() {
    remove_meta_box('tagsdiv-post_tag', 'post', 'side');
}

// Remove Cookiebanner metabox
function iu_remove_cookiebanner_metabox($post_type = null, $post = null) {
    $types = get_post_types(array('public' => true), 'names');
    foreach ($types as $pt) {
        remove_meta_box('cmplz_hide_banner_meta_box', $pt, 'side');
        remove_meta_box('cmplz_hide_banner_meta_box', $pt, 'normal');
        remove_meta_box('cmplz_hide_banner_meta_box', $pt, 'advanced');
    }
}

// Hide Format metabox from Screen Options
function iu_hide_format_metabox($hidden, $screen) {
    if ($screen->id === 'post') {
        $hidden[] = 'formatdiv';
    }
    return $hidden;
}

// Hide Tags metabox from Screen Options
function iu_hide_tags_metabox($hidden, $screen) {
    if ($screen->id === 'post') {
        $hidden[] = 'tagsdiv-post_tag';
    }
    return $hidden;
}

// Hide Cookiebanner metabox from Screen Options
function iu_hide_cookiebanner_metabox($hidden, $screen) {
    // Hide from Screen Options on all post edit screens
    $hidden[] = 'cmplz_hide_banner_meta_box';
    return $hidden;
}

// Additional CSS to hide cookiebanner from screen options
function iu_hide_cookiebanner_screen_options() {
    echo '<style>
        #cmplz_hide_banner_meta_box-hide { display: none !important; }
        label[for="cmplz_hide_banner_meta_box-hide"] { display: none !important; }
    </style>';
}

// Move jQuery to footer on frontend
function iu_move_jquery_to_footer($wp_scripts) {
    if (is_admin()) {
        return;
    }
    $handles = array('jquery', 'jquery-core', 'jquery-migrate');
    foreach ($handles as $h) {
        if (isset($wp_scripts->registered[$h])) {
            $wp_scripts->add_data($h, 'group', 1);
        }
    }
}

function iu_move_jquery_dependents_to_footer() {
    if (is_admin()) {
        return;
    }
    $wp_scripts = wp_scripts();
    if (!$wp_scripts || empty($wp_scripts->registered)) {
        return;
    }
    foreach ($wp_scripts->registered as $handle => $obj) {
        if (!empty($obj->deps) && (in_array('jquery', $obj->deps, true) || in_array('jquery-core', $obj->deps, true))) {
            $wp_scripts->add_data($handle, 'group', 1);
        }
    }
}

function iu_force_jquery_to_footer_group() {
    if (is_admin()) {
        return;
    }
    $wp_scripts = wp_scripts();
    if (!$wp_scripts) {
        return;
    }
    foreach (array('jquery', 'jquery-core', 'jquery-migrate') as $h) {
        if (isset($wp_scripts->registered[$h])) {
            $wp_scripts->add_data($h, 'group', 1);
        }
    }
}

// Remove WP Rocket Options metabox
function iu_remove_wprocket_metabox($post_type = null, $post = null) {
    $types = get_post_types(array('public' => true), 'names');
    foreach ($types as $pt) {
        remove_meta_box('rocket_post_exclude', $pt, 'side');
        remove_meta_box('rocket_post_exclude', $pt, 'normal');
        remove_meta_box('rocket_post_exclude', $pt, 'advanced');
    }
}

// Hide WP Rocket Options metabox from Screen Options
function iu_hide_wprocket_metabox($hidden, $screen) {
    $hidden[] = 'rocket_post_exclude';
    return $hidden;
}

// Additional CSS to hide WP Rocket metabox from screen options
function iu_hide_wprocket_screen_options() {
    echo '<style>
        #rocket_post_exclude-hide { display: none !important; }
        label[for="rocket_post_exclude-hide"] { display: none !important; }
    </style>';
}

