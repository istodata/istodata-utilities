<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (!class_exists('IU_Google_Maps_Widget')) {
class IU_Google_Maps_Widget extends Widget_Base {
        public function get_name() {
            return 'iu_google_maps';
        }

        public function get_title() {
            return __('Advanced Google Map', 'istodata-utilities');
        }

        public function get_icon() {
            return 'eicon-google-maps';
        }

        public function get_categories() {
            return [ 'istodata-kit' ];
        }

        public function get_style_depends() {
            if (!wp_style_is('iu-elementor-google-maps', 'registered')) {
                wp_register_style(
                    'iu-elementor-google-maps',
                    IU_PLUGIN_URL . 'assets/css/elementor-google-maps.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }
            return [ 'iu-elementor-google-maps' ];
        }

        public function get_script_depends() {
            // Ensure the initializer script loads in both frontend and editor iframe
            if (!wp_script_is('iu-elementor-google-maps', 'registered')) {
                wp_register_script(
                    'iu-elementor-google-maps',
                    IU_PLUGIN_URL . 'assets/js/elementor-google-maps.js',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : '1.0.0',
                    true
                );
            }
            return [ 'iu-elementor-google-maps' ];
        }

        protected function register_controls() {
            $this->start_controls_section('section_content', [
                'label' => __('Ρυθμίσεις Χάρτη', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_control('address', [
                'label' => __('Διεύθυνση', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Π.χ. "Σταδίου 1, Αθήνα"', 'istodata-utilities'),
                'description' => __('Αν δοθεί διεύθυνση, τα Latitude/Longtitute είναι προαιρετικά.', 'istodata-utilities'),
                'dynamic' => [ 'active' => true ],
            ]);

            $this->add_control('latitude', [
                'label' => __('Latitude', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'default' => '',
            ]);

            $this->add_control('longitude', [
                'label' => __('Longitude', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'default' => '',
            ]);

            $this->add_control('zoom', [
                'label' => __('Zoom', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 1, 'max' => 21 ] ],
                'default' => [ 'size' => 14 ],
            ]);

            // Styling mode: Default / Monochrome / Custom (Map ID)
            $this->add_control('style_mode', [
                'label' => __('Χρωματισμός Χάρτη', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default' => __('Default', 'istodata-utilities'),
                    'monochrome' => __('Ασπρόμαυρο', 'istodata-utilities'),
                    'custom' => __('Custom (Map ID)', 'istodata-utilities'),
                ],
            ]);

            // Map ID (Cloud styling) directly under style selector
            $this->add_control('map_id', [
                'label' => __('Map ID (Cloud styling)', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('π.χ. 1234abcd5678efgh', 'istodata-utilities'),
                'description' => __('Google Cloud Console → Google Maps Platform → Map Styles → Create style → Associate/Create Map ID', 'istodata-utilities'),
                'condition' => [ 'style_mode' => 'custom' ],
            ]);

            // Zoom controls before mouse wheel zoom for logical grouping
            $this->add_control('zoom_controls', [
                'label' => __('Κουμπιά Zoom (+/-)', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]);

            $this->add_control('scrollwheel', [
                'label' => __('Zoom με ρόδα ποντικιού', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'no',
            ]);

            $this->add_control('draggable', [
                'label' => __('Μετακίνηση χάρτη (drag)', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'no',
            ]);

            // (moved up)

            $this->add_control('marker_title', [
                'label' => __('Τίτλος Marker', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Κείμενο tooltip (εμφανίζεται σε hover/click)', 'istodata-utilities'),
                'dynamic' => [ 'active' => true ],
            ]);

            $this->add_control('marker_svg', [
                'label' => __('Custom Marker (PNG)', 'istodata-utilities'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'description' => __('Ανεβάστε PNG εικονίδιο για το marker.', 'istodata-utilities'),
                'media_types' => ['image'],
            ]);

            $this->add_responsive_control('marker_size', [
                'label' => __('Μέγεθος Marker (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 8, 'max' => 128 ] ],
                'default' => [ 'size' => 64, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-google-map' => '--iu-marker-size: {{SIZE}}{{UNIT}};',
                ],
            ]);

            $this->add_responsive_control('map_height', [
                'label' => __('Ύψος Χάρτη (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 150, 'max' => 1200 ] ],
                'default' => [ 'size' => 360, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-google-map__canvas' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]);

            $this->add_control('api_key', [
                'label' => __('API Key (προαιρετικό)', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Αν έχει οριστεί στο Elementor → Settings → Integrations, αφήστε το κενό.', 'istodata-utilities'),
            ]);
            // Map ID control removed; use Style ID above

            // Language is auto-detected from WPML/Polylang/site locale; no manual control

            $this->end_controls_section();
        }

        protected function render() {
            $s = $this->get_settings_for_display();

            $lat = isset($s['latitude']) && $s['latitude'] !== '' ? floatval($s['latitude']) : null;
            $lng = isset($s['longitude']) && $s['longitude'] !== '' ? floatval($s['longitude']) : null;
            $address = !empty($s['address']) ? $s['address'] : '';
            $has_coords = (is_finite((float)$lat) && is_finite((float)$lng));
            if (!$has_coords && !$address) {
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    echo '<div style="padding:12px;background:#fff3cd;border:1px solid #ffeeba;color:#856404;">' . esc_html__('Δώστε διεύθυνση ή Lat/Long για να εμφανιστεί ο χάρτης.', 'istodata-utilities') . '</div>';
                }
                return;
            }

            // Read API key: try widget override, then Elementor Kit setting, then common options
            $api_key = '';
            if (!empty($s['api_key'])) { $api_key = $s['api_key']; }
            if (empty($api_key)) {
                try {
                    if (class_exists('Elementor\\Plugin')) {
                        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
                        if ($kit) {
                            $kit_key = $kit->get_settings('google_map_api_key');
                            if (!empty($kit_key)) { $api_key = $kit_key; }
                        }
                    }
                } catch (\Throwable $e) {}
            }
            if (empty($api_key)) {
                $opt_key = get_option('elementor_google_maps_api_key');
                if (!empty($opt_key)) { $api_key = $opt_key; }
            }
            if (empty($api_key)) {
                $opt_key = get_option('elementor_google_maps_key');
                if (!empty($opt_key)) { $api_key = $opt_key; }
            }

            // Prepare marker icon as data URL if PNG is provided
            $icon_data_url = '';
            $icon_size = isset($s['marker_size']['size']) ? intval($s['marker_size']['size']) : 64;
            $marker_warning = '';
            $marker_att_id = isset($s['marker_svg']['id']) ? intval($s['marker_svg']['id']) : 0;
            if ($marker_att_id) {
                $file = get_attached_file($marker_att_id);
                $type = $file ? wp_check_filetype($file) : array('ext' => '', 'type' => '');
                $ext = !empty($type['ext']) ? strtolower($type['ext']) : '';
                if ($ext === 'png') {
                    $url = wp_get_attachment_image_url($marker_att_id, 'full');
                    if ($url) { $icon_data_url = esc_url_raw($url); }
                } else {
                    $marker_warning = __('Το Custom Marker πρέπει να είναι PNG. Το επιλεγμένο αρχείο δεν είναι PNG.', 'istodata-utilities');
                }
            }

            $zoom = isset($s['zoom']['size']) ? intval($s['zoom']['size']) : 14;
            $title = !empty($s['marker_title']) ? $s['marker_title'] : (!empty($s['address']) ? $s['address'] : '');
            // Language: auto-detect from WPML/Polylang/site locale
            $lang = '';
            // WPML
            if (defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE) {
                $lang = ICL_LANGUAGE_CODE;
            // Polylang
            } elseif (function_exists('pll_current_language')) {
                $pll = pll_current_language('slug');
                if (!empty($pll)) { $lang = $pll; }
            }
            // Fallback to site locale
            if (empty($lang)) {
                $locale = function_exists('get_locale') ? get_locale() : '';
                if (!empty($locale)) {
                    $lang = strtolower(substr($locale, 0, 2));
                }
            }

            // Region bias: derive from locale if possible; default GR for Greek
            $region = '';
            $locale = function_exists('get_locale') ? get_locale() : '';
            if (!empty($locale) && strpos($locale, '_') !== false) {
                $parts = explode('_', $locale, 2);
                if (!empty($parts[1])) { $region = strtoupper($parts[1]); }
            }
            if (empty($region) && $lang === 'el') { $region = 'GR'; }
            $scrollwheel = (!empty($s['scrollwheel']) && $s['scrollwheel'] === 'yes') ? '1' : '0';
            $draggable = (!empty($s['draggable']) && $s['draggable'] === 'yes') ? '1' : '0';
            $zoom_controls = (!empty($s['zoom_controls']) && $s['zoom_controls'] === 'yes') ? '1' : '0';

            // Script is declared as a dependency via get_script_depends()

            $attrs = array(
                'class' => 'iu-google-map',
            );
            if ($has_coords) {
                $attrs['data-lat'] = $lat;
                $attrs['data-lng'] = $lng;
            }
            if (!empty($address)) {
                $attrs['data-address'] = $address;
            }
            $attrs += array(
                'data-zoom' => $zoom,
                'data-style' => (!empty($s['style_mode']) ? $s['style_mode'] : 'default'),
                'data-scrollwheel' => $scrollwheel,
                'data-draggable' => $draggable,
                'data-zoom-controls' => $zoom_controls,
                'data-title' => esc_attr($title),
            );
            // Provide placeholder image URL for GDPR placeholder background
            $placeholder_img = trailingslashit(IU_PLUGIN_URL) . 'assets/images/map-placeholder.jpg';
            $attrs['data-placeholder-image'] = esc_url($placeholder_img);
            if (!empty($api_key)) { $attrs['data-api-key'] = esc_attr($api_key); }
            // Back-compat: accept both map_id and (legacy) style_id; only when style_mode is custom
            if (!empty($s['style_mode']) && $s['style_mode'] === 'custom') {
                $map_id_val = '';
                if (!empty($s['map_id'])) { $map_id_val = $s['map_id']; }
                elseif (!empty($s['style_id'])) { $map_id_val = $s['style_id']; }
                if (!empty($map_id_val)) { $attrs['data-map-id'] = esc_attr($map_id_val); }
            }
            if (!empty($lang)) { $attrs['data-lang'] = esc_attr($lang); }
            if (!empty($region)) { $attrs['data-region'] = esc_attr($region); }
            if (!empty($icon_data_url)) { $attrs['data-icon'] = $icon_data_url; }
            // Responsive size is applied via CSS variable; keep data as fallback for older browsers
            if (!empty($icon_size)) { $attrs['data-icon-size'] = intval($icon_size); }

            // No CSS overlay; styling handled via style_mode (monochrome) or Map ID (custom)

            echo '<div ' . $this->html_attrs($attrs) . '>'; // wrapper
            if ($marker_warning && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div style="margin-bottom:8px;padding:8px 10px;background:#fff3cd;border:1px solid #ffeeba;color:#856404;border-radius:3px;font-size:12px;">' . esc_html($marker_warning) . '</div>';
            }
            echo '<div class="iu-google-map__canvas"></div>';
            echo '</div>';
        }

        private function html_attrs($attrs) {
            $out = [];
            foreach ($attrs as $k => $v) {
                $out[] = $k . '="' . esc_attr($v) . '"';
            }
            return implode(' ', $out);
        }

        private function get_inline_svg_from_attachment($attachment_id) {
            $file = get_attached_file($attachment_id);
            if (!$file || !file_exists($file)) return '';
            $type = wp_check_filetype($file);
            if (empty($type['ext']) || strtolower($type['ext']) !== 'svg') return '';
            $content = file_get_contents($file);
            if (!$content) return '';
            // Basic sanitization similar to Social Share widget
            $content = $this->normalize_root_svg_tag($content);
            $allowed_tags = [
                'svg' => [
                    'class' => true, 'xmlns' => true, 'width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'style' => true, 'aria-hidden' => true, 'role' => true, 'focusable' => true, 'preserveaspectratio' => true,
                ],
                'g' => [ 'class' => true, 'fill' => true, 'stroke' => true, 'style' => true, 'transform' => true ],
                'path' => [ 'class' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'd' => true, 'style' => true, 'opacity' => true, 'transform' => true ],
                'circle' => [ 'class' => true, 'fill' => true, 'stroke' => true, 'cx' => true, 'cy' => true, 'r' => true, 'style' => true, 'transform' => true ],
                'rect' => [ 'class' => true, 'fill' => true, 'stroke' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'style' => true, 'transform' => true ],
                'polygon' => [ 'class' => true, 'fill' => true, 'stroke' => true, 'points' => true, 'style' => true, 'transform' => true ],
                'polyline' => [ 'class' => true, 'fill' => true, 'stroke' => true, 'points' => true, 'style' => true, 'transform' => true ],
                'line' => [ 'class' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'style' => true, 'transform' => true ],
                'ellipse' => [ 'class' => true, 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'style' => true, 'transform' => true ],
                'defs' => [ 'class' => true ],
                'lineargradient' => [ 'id' => true, 'gradientunits' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ],
                'radialgradient' => [ 'id' => true, 'gradientunits' => true, 'cx' => true, 'cy' => true, 'r' => true, 'fx' => true, 'fy' => true ],
                'stop' => [ 'offset' => true, 'stop-color' => true, 'stop-opacity' => true ],
                'title' => [ ],
                'desc' => [ ],
                'symbol' => [ 'id' => true, 'viewbox' => true ],
                'use' => [ 'href' => true, 'xlink:href' => true ],
                'clippath' => [ 'id' => true ],
                'mask' => [ 'id' => true ],
                'filter' => [ 'id' => true ],
            ];
            $svg = wp_kses($content, $allowed_tags);
            return $svg;
        }

        private function normalize_root_svg_tag($svg_str) {
            // Normalize only the first <svg ...> tag
            return preg_replace_callback('/<svg\b([^>]*)>/', function($m){
                $attrs = $m[1];
                $width = null; $height = null; $has_viewbox = false;

                if (preg_match('/\swidth\s*=\s*"([^"]*)"/i', $attrs, $wm)) {
                    $width = $this->to_number($wm[1]);
                }
                if (preg_match('/\sheight\s*=\s*"([^"]*)"/i', $attrs, $hm)) {
                    $height = $this->to_number($hm[1]);
                }
                if (preg_match('/\sviewBox\s*=\s*"([^"]*)"/i', $attrs)) {
                    $has_viewbox = true;
                }

                // Remove width/height attributes
                $attrs = preg_replace('/\swidth\s*=\s*"[^"]*"/i', '', $attrs);
                $attrs = preg_replace('/\sheight\s*=\s*"[^"]*"/i', '', $attrs);

                // Remove width/height from inline style
                $attrs = preg_replace_callback('/\sstyle\s*=\s*"([^"]*)"/i', function($sm){
                    $style = $sm[1];
                    $style = preg_replace('/(?:^|;|\s)(width|height)\s*:\s*[^;]+;?/i', ';', $style);
                    $style = preg_replace('/;{2,}/', ';', trim($style));
                    $style = trim($style, '; ');
                    if ($style === '') { return ''; }
                    return ' style="' . esc_attr($style) . '"';
                }, $attrs);

                // If no viewBox, add one using width/height if available, else 24x24
                if (!$has_viewbox) {
                    $vw = $width ? $width : 24;
                    $vh = $height ? $height : 24;
                    $attrs .= ' viewBox="0 0 ' . intval($vw) . ' ' . intval($vh) . '" preserveAspectRatio="xMidYMid meet"';
                }

                return '<svg' . $attrs . '>';
            }, $svg_str, 1);
        }

        private function to_number($val) {
            if ($val === null || $val === '') return null;
            if (is_numeric($val)) return (float)$val;
            if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $val, $m)) {
                return (float)$m[1];
            }
            return null;
        }
    }
}

// Editor preview tweaks for marker image were removed; keep default Elementor behavior
