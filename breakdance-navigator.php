<?php
/*
Plugin Name: Breakdance Navigator
Plugin URI: https://github.com/beamkiller/breakdance-navigator
Description: Adds a quick-access navigator to the WordPress admin bar. It allows easy access to Breakdance templates, headers, footers, global blocks, popups, and pages edited with Breakdance, along with other essential settings.
Version: 1.0.1
Author: Peter Kulcsár
Author URI: https://peterkulcsar.dev/
Text Domain: breakdance-navigator
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Breakdance_Navigator' ) ) {

    class Breakdance_Navigator {

        public function __construct() {
            add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_bar_styles' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_admin_bar_styles' ) ); // For front-end
        }

        /**
         * Enqueue custom styles for the admin bar.
         */
        public function enqueue_admin_bar_styles() {
            if ( is_admin_bar_showing() ) {
                wp_add_inline_style( 'admin-bar', '
                    /* Style for the separator */
                    #wp-admin-bar-bdn-breakdance-navigator > .ab-sub-wrapper #wp-admin-bar-bdn-settings {
                        border-bottom: 1px solid #ccc;
                        margin: 0 0 5px 0;
                    }
                ' );
            }
        }

        /**
         * Adds the main Breakdance menu and its submenus to the admin bar.
         *
         * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
         */
        public function add_admin_bar_menu( $wp_admin_bar ) {
            if ( ! current_user_can( 'edit_posts' ) ) {
                return;
            }

            // Add the parent menu item with an icon
            $icon_url   = plugin_dir_url( __FILE__ ) . 'images/breakdance-icon.png';
            $title_html = '<img src="' . esc_url( $icon_url ) . '" style="width:16px;height:16px;padding-right:6px;vertical-align:middle;" alt="">' . esc_html__( 'Breakdance Nav', 'breakdance-navigator' );
            $title_html = wp_kses( $title_html, array(
                'img' => array(
                    'src'   => array(),
                    'style' => array(),
                    'alt'   => array(),
                ),
            ) );

            $wp_admin_bar->add_node( array(
                'id'    => 'bdn-breakdance-navigator',
                'title' => $title_html,
                'href'  => '#',
            ) );

            // Add submenus
            $this->add_pages_submenu( $wp_admin_bar );
            $this->add_templates_submenu( $wp_admin_bar );
            $this->add_headers_submenu( $wp_admin_bar );
            $this->add_footers_submenu( $wp_admin_bar );
            $this->add_global_blocks_submenu( $wp_admin_bar );
            $this->add_popups_submenu( $wp_admin_bar );
            $this->add_form_submissions_submenu( $wp_admin_bar );
            $this->add_design_library_submenu( $wp_admin_bar );
            $this->add_settings_submenu( $wp_admin_bar );
            $this->add_headspin_submenu( $wp_admin_bar );
            $this->add_links_submenu( $wp_admin_bar );
            $this->add_about_submenu( $wp_admin_bar );
        }

        // Add Pages submenu
        private function add_pages_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-pages',
                'title'  => esc_html__( 'Pages', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'edit.php?post_type=page' ) ),
                'parent' => 'bdn-breakdance-navigator',
            ) );

            $this->add_breakdance_pages_to_admin_bar( $wp_admin_bar );
        }

        private function add_breakdance_pages_to_admin_bar( $wp_admin_bar ) {
            $pages = $this->get_breakdance_pages();

            if ( $pages ) {
                foreach ( $pages as $page ) {
                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $page->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdn-page-' . intval( $page->ID ),
                        'title'  => esc_html( $page->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdn-pages',
                    ) );
                }
            }
        }

        private function get_breakdance_pages() {
            $args = array(
                'post_type'      => 'page',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'meta_query'     => array(
                    array(
                        'key'     => '_breakdance_data',
                        'compare' => 'EXISTS',
                    ),
                ),
            );
            return get_posts( $args );
        }

        // Add Templates submenu
        private function add_templates_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-templates',
                'title'  => esc_html__( 'Templates', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'admin.php?page=breakdance_template' ) ),
                'parent' => 'bdn-breakdance-navigator',
            ) );

            $this->add_templates_to_admin_bar( $wp_admin_bar );
        }

        private function add_templates_to_admin_bar( $wp_admin_bar ) {
            $templates = $this->get_breakdance_templates();

            if ( $templates ) {
                foreach ( $templates as $template ) {
                    if ( strpos( $template->post_title, 'Fallback: ' ) === 0 ) {
                        continue;
                    }

                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $template->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdn-template-' . intval( $template->ID ),
                        'title'  => esc_html( $template->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdn-templates',
                    ) );
                }
            }
        }

        private function get_breakdance_templates() {
            $args = array(
                'post_type'      => 'breakdance_template',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
        }

        // Add Headers submenu
        private function add_headers_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-headers',
                'title'  => esc_html__( 'Headers', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'admin.php?page=breakdance_header' ) ),
                'parent' => 'bdn-breakdance-navigator',
            ) );

            $this->add_headers_to_admin_bar( $wp_admin_bar );
        }

        private function add_headers_to_admin_bar( $wp_admin_bar ) {
            $headers = $this->get_breakdance_headers();

            if ( $headers ) {
                foreach ( $headers as $header ) {
                    if ( strpos( $header->post_title, 'Fallback: ' ) === 0 ) {
                        continue;
                    }

                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $header->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdn-header-' . intval( $header->ID ),
                        'title'  => esc_html( $header->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdn-headers',
                    ) );
                }
            }
        }

        private function get_breakdance_headers() {
            $args = array(
                'post_type'      => 'breakdance_header',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
        }

        // Add Footers submenu
        private function add_footers_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-footers',
                'title'  => esc_html__( 'Footers', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'admin.php?page=breakdance_footer' ) ),
                'parent' => 'bdn-breakdance-navigator',
            ) );

            $this->add_footers_to_admin_bar( $wp_admin_bar );
        }

        private function add_footers_to_admin_bar( $wp_admin_bar ) {
            $footers = $this->get_breakdance_footers();

            if ( $footers ) {
                foreach ( $footers as $footer ) {
                    if ( strpos( $footer->post_title, 'Fallback: ' ) === 0 ) {
                        continue;
                    }

                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $footer->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdn-footer-' . intval( $footer->ID ),
                        'title'  => esc_html( $footer->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdn-footers',
                    ) );
                }
            }
        }

        private function get_breakdance_footers() {
            $args = array(
                'post_type'      => 'breakdance_footer',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
        }

        // Add Global Blocks submenu
        private function add_global_blocks_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-global-blocks',
                'title'  => esc_html__( 'Global Blocks', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'admin.php?page=breakdance_block' ) ),
                'parent' => 'bdn-breakdance-navigator',
            ) );

            $this->add_global_blocks_to_admin_bar( $wp_admin_bar );
        }

        private function add_global_blocks_to_admin_bar( $wp_admin_bar ) {
            $blocks = $this->get_breakdance_global_blocks();

            if ( $blocks ) {
                foreach ( $blocks as $block ) {
                    if ( strpos( $block->post_title, 'Fallback: ' ) === 0 ) {
                        continue;
                    }

                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $block->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdn-block-' . intval( $block->ID ),
                        'title'  => esc_html( $block->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdn-global-blocks',
                    ) );
                }
            }
        }

        private function get_breakdance_global_blocks() {
            $args = array(
                'post_type'      => 'breakdance_block',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
        }

        // Add Popups submenu
        private function add_popups_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-popups',
                'title'  => esc_html__( 'Popups', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'admin.php?page=breakdance_popup' ) ),
                'parent' => 'bdn-breakdance-navigator',
            ) );

            $this->add_popups_to_admin_bar( $wp_admin_bar );
        }

        private function add_popups_to_admin_bar( $wp_admin_bar ) {
            $popups = $this->get_breakdance_popups();

            if ( $popups ) {
                foreach ( $popups as $popup ) {
                    if ( strpos( $popup->post_title, 'Fallback: ' ) === 0 ) {
                        continue;
                    }

                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $popup->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdn-popup-' . intval( $popup->ID ),
                        'title'  => esc_html( $popup->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdn-popups',
                    ) );
                }
            }
        }

        private function get_breakdance_popups() {
            $args = array(
                'post_type'      => 'breakdance_popup',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
        }

        // Add Form Submissions submenu
        private function add_form_submissions_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-form-submissions',
                'title'  => esc_html__( 'Form Submissions', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'edit.php?post_type=breakdance_form_res' ) ),
                'parent' => 'bdn-breakdance-navigator',
            ) );
        }

        // Add Design Library submenu
        private function add_design_library_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-design-library',
                'title'  => esc_html__( 'Design Library', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'admin.php?page=breakdance_design_library' ) ),
                'parent' => 'bdn-breakdance-navigator',
            ) );
        }

        // Add Settings submenu
        private function add_settings_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-settings',
                'title'  => esc_html__( 'Settings', 'breakdance-navigator' ),
                'href'   => esc_url( admin_url( 'admin.php?page=breakdance_settings' ) ),
                'parent' => 'bdn-breakdance-navigator',
                'meta'   => array( 'class' => 'bdn-settings-separator' ),
            ) );

            $settings_submenus = array(
                'license'          => __( 'License', 'breakdance-navigator' ),
                'global_styles'    => __( 'Global Styles', 'breakdance-navigator' ),
                'theme_disabler'   => __( 'Theme', 'breakdance-navigator' ),
                'woocommerce'      => __( 'WooCommerce', 'breakdance-navigator' ),
                'permissions'      => __( 'User Access', 'breakdance-navigator' ),
                'maintenance-mode' => __( 'Maintenance', 'breakdance-navigator' ),
                'bloat_eliminator' => __( 'Performance', 'breakdance-navigator' ),
                'api_keys'         => __( 'API Keys', 'breakdance-navigator' ),
                'post_types'       => __( 'Post Types', 'breakdance-navigator' ),
                'advanced'         => __( 'Advanced', 'breakdance-navigator' ),
                'privacy'          => __( 'Privacy', 'breakdance-navigator' ),
                'design_library'   => __( 'Design Library', 'breakdance-navigator' ),
                'header_footer'    => __( 'Custom Code', 'breakdance-navigator' ),
                'tools'            => __( 'Tools', 'breakdance-navigator' ),
                'ai'               => __( 'AI Assistant', 'breakdance-navigator' ),
            );

            foreach ( $settings_submenus as $tab => $title ) {
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdn-settings-' . sanitize_key( $tab ),
                    'title'  => esc_html( $title ),
                    'href'   => esc_url( admin_url( 'admin.php?page=breakdance_settings&tab=' . urlencode( $tab ) ) ),
                    'parent' => 'bdn-settings',
                ) );
            }
        }

        // Add Headspin submenu if the plugin is active
        private function add_headspin_submenu( $wp_admin_bar ) {
            if ( ! function_exists( 'is_plugin_active' ) ) {
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if ( is_plugin_active( 'headspinui/headspinui.php' ) ) {
                $icon_url   = plugin_dir_url( __FILE__ ) . 'images/headspin-icon.png';
                $title_html = '<img src="' . esc_url( $icon_url ) . '" style="width:16px;height:16px;padding-right:6px;vertical-align:middle;" alt="">' . esc_html__( 'Headspin', 'breakdance-navigator' );
                $title_html = wp_kses( $title_html, array(
                    'img' => array(
                        'src'   => array(),
                        'style' => array(),
                        'alt'   => array(),
                    ),
                ) );

                $wp_admin_bar->add_node( array(
                    'id'     => 'bdn-headspin',
                    'title'  => $title_html,
                    'href'   => esc_url( admin_url( 'admin.php?page=headspin' ) ),
                    'parent' => 'bdn-breakdance-navigator',
                ) );
            }
        }

        // Add Links submenu
        private function add_links_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-links',
                'title'  => esc_html__( 'Links', 'breakdance-navigator' ),
                'href'   => '#',
                'parent' => 'bdn-breakdance-navigator',
            ) );

            $links = array(
                'breakdance'       => array(
                    'title' => __( 'Breakdance', 'breakdance-navigator' ),
                    'url'   => 'https://breakdance.com/ref/325/',
                ),
                'breakdance-fb-group' => array(
                    'title' => __( 'Breakdance FB Group', 'breakdance-navigator' ),
                    'url'   => 'https://www.facebook.com/groups/5118076864894234',
                ),
                /* 'breakdance-discord' => array(
                    'title' => __( 'Breakdance Unofficial Discord', 'breakdance-navigator' ),
                    'url'   => 'https://discord.gg/FZRMzkps',
                ), */
                'headspin'  => array(
                    'title' => __( 'Headspin', 'breakdance-navigator' ),
                    'url'   => 'https://headspinui.com/',
                ),
                'moreblocks' => array(
                    'title' => __( 'Moreblocks', 'breakdance-navigator' ),
                    'url'   => 'https://moreblocks.com/',
                ),
                'breakerblocks' => array(
                    'title' => __( 'Breakerblocks', 'breakdance-navigator' ),
                    'url'   => 'https://breakerblocks.com/',
                ),
                'bdlibraryawesome' => array(
                    'title' => __( 'BD Library Awesome', 'breakdance-navigator' ),
                    'url'   => 'https://bdlibraryawesome.com/',
                ),
                'flowmattic' => array(
                   'title'  =>  __( 'Flowmattic', 'breakdance-navigator' ),
                    'url'   => 'https://flowmattic.com/integrations/?aff=97',
                ),
            );

            foreach ( $links as $id => $info ) {
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdn-links-' . sanitize_key( $id ),
                    'title'  => esc_html( $info['title'] ),
                    'href'   => esc_url( $info['url'] ),
                    'parent' => 'bdn-links',
                    'meta'   => array( 'target' => '_blank' ),
                ) );
            }
        }

        // Add About submenu
        private function add_about_submenu( $wp_admin_bar ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdn-about',
                'title'  => esc_html__( 'About', 'breakdance-navigator' ),
                'href'   => '#',
                'parent' => 'bdn-breakdance-navigator',
            ) );

            $about_links = array(
                'author'       => array(
                    'title' => __( 'Author: Peter Kulcsár', 'breakdance-navigator' ),
                    'url'   => 'https://peterkulcsar.dev/',
                ),
                'github'       => array(
                    'title' => __( 'Plugin on GitHub', 'breakdance-navigator' ),
                    'url'   => 'https://github.com/beamkiller/breakdance-navigator',
                ),
                'buymeacoffee' => array(
                    'title' => __( 'Buy Me a Coffee', 'breakdance-navigator' ),
                    'url'   => 'https://buymeacoffee.com/peter.kulcsar',
                ),
            );

            foreach ( $about_links as $id => $info ) {
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdn-about-' . sanitize_key( $id ),
                    'title'  => esc_html( $info['title'] ),
                    'href'   => esc_url( $info['url'] ),
                    'parent' => 'bdn-about',
                    'meta'   => array( 'target' => '_blank' ),
                ) );
            }
        }
    }

    new Breakdance_Navigator();
}