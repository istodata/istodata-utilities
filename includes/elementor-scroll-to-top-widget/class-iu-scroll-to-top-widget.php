<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Icons_Manager;

if (!class_exists('IU_Scroll_To_Top_Widget')) {
    class IU_Scroll_To_Top_Widget extends Widget_Base {
        public function get_name() { return 'iu_scroll_to_top'; }
        public function get_title() { return __('Scroll To Top', 'istodata-utilities'); }
        public function get_icon() { return 'eicon-arrow-up'; }
        public function get_categories() { return [ 'istodata-kit' ]; }

        public function get_style_depends() {
            if (!wp_style_is('iu-scroll-to-top', 'registered')) {
                wp_register_style('iu-scroll-to-top', IU_PLUGIN_URL . 'assets/css/scroll-to-top.css', array(), defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null);
            }
            return [ 'iu-scroll-to-top' ];
        }

        protected function register_controls() {
            $this->start_controls_section('section_content', [ 'label' => __('Περιεχόμενο', 'istodata-utilities'), 'tab' => Controls_Manager::TAB_CONTENT ]);
            $this->add_control('icon', [
                'label' => __('Εικονίδιο', 'istodata-utilities'),
                'type' => Controls_Manager::ICONS,
                'default' => [ 'value' => 'eicon-arrow-up', 'library' => 'eicons' ],
            ]);
            $this->end_controls_section();

            $this->start_controls_section('section_style', [ 'label' => __('Στυλ', 'istodata-utilities'), 'tab' => Controls_Manager::TAB_STYLE ]);
            $this->add_responsive_control('btn_width', [
                'label' => __('Πλάτος κουμπιού (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 24, 'max' => 200 ] ],
                'default' => [ 'size' => 48, 'unit' => 'px' ],
                'selectors' => [ '{{WRAPPER}} .iu-stt' => 'width: {{SIZE}}{{UNIT}};' ],
            ]);
            $this->add_responsive_control('btn_height', [
                'label' => __('Ύψος κουμπιού (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 24, 'max' => 200 ] ],
                'default' => [ 'size' => 48, 'unit' => 'px' ],
                'selectors' => [ '{{WRAPPER}} .iu-stt' => 'height: {{SIZE}}{{UNIT}};' ],
            ]);
            $this->add_responsive_control('icon_size', [
                'label' => __('Μέγεθος εικονιδίου (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 8, 'max' => 96 ] ],
                'default' => [ 'size' => 24, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-stt__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .iu-stt__icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]);
            $this->start_controls_tabs('tabs_colors');
            $this->start_controls_tab('tab_normal', [ 'label' => __('Κανονικό', 'istodata-utilities') ]);
            $this->add_control('icon_color', [
                'label' => __('Χρώμα εικονιδίου', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-stt' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-stt .iu-stt__icon' => 'color: {{VALUE}};',
                ],
            ]);
            $this->end_controls_tab();
            $this->start_controls_tab('tab_hover', [ 'label' => __('Hover', 'istodata-utilities') ]);
            $this->add_control('icon_color_hover', [
                'label' => __('Χρώμα εικονιδίου (hover)', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-stt:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-stt:hover .iu-stt__icon' => 'color: {{VALUE}};',
                ],
            ]);
            $this->end_controls_tab();
            $this->end_controls_tabs();
            $this->end_controls_section();
        }

        protected function render() {
            $s = $this->get_settings_for_display();
            $this->add_render_attribute('a', 'class', 'iu-stt');
            $this->add_render_attribute('a', 'href', '#iu-scroll-top');
            $this->add_render_attribute('a', 'aria-label', __('Scroll to top', 'istodata-utilities'));
            echo '<a ' . $this->get_render_attribute_string('a') . '>';
            if (!empty($s['icon'])) {
                echo '<span class="iu-stt__icon">';
                ob_start();
                Icons_Manager::render_icon($s['icon'], [ 'aria-hidden' => 'true', 'class' => 'iu-stt__svg' ]);
                $icon_html = ob_get_clean();
                $icon_html = $this->iu_svg_normalize($icon_html);
                echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</span>';
            }
            echo '</a>';
        }
        private function iu_svg_normalize($svg_html) {
            if (!is_string($svg_html) || stripos($svg_html, '<svg') === false) return $svg_html;
            // remove width/height attributes on root svg
            $svg_html = preg_replace_callback('/<svg\b([^>]*)>/i', function($m){
                $attrs = $m[1];
                // drop width/height
                $attrs = preg_replace('/\swidth\s*=\s*"[^"]*"/i', '', $attrs);
                $attrs = preg_replace('/\sheight\s*=\s*"[^"]*"/i', '', $attrs);
                // clean inline style width/height
                $attrs = preg_replace_callback('/\sstyle\s*=\s*"([^"]*)"/i', function($sm){
                    $style = preg_replace('/(?:^|;|\s)(width|height)\s*:\s*[^;]+;?/i', ';', $sm[1]);
                    $style = preg_replace('/;{2,}/', ';', trim($style));
                    $style = trim($style, '; ');
                    return $style === '' ? '' : ' style="' . esc_attr($style) . '"';
                }, $attrs);
                return '<svg' . $attrs . '>';
            }, $svg_html, 1);
            return $svg_html;
        }
    }
}
