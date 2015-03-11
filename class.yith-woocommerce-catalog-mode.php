<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Implements features of YITH WooCommerce Catalog Mode plugin
 *
 * @class   YITH_WC_Catalog_Mode
 * @package Yithemes
 * @since   1.0.0
 * @author  Your Inspiration Themes
 */
class YITH_WC_Catalog_Mode {

    /**
     * @var $_panel Panel Object
     */
    protected $_panel;

    /**
     * @var $_premium string Premium tab template file name
     */
    protected $_premium = 'premium.php';

    /**
     * @var string Premium version landing link
     */
    protected $_premium_landing = 'http://yithemes.com/themes/plugins/yith-woocommerce-catalog-mode/';

    /**
     * @var string Plugin official documentation
     */
    protected $_official_documentation = 'http://yithemes.com/docs-plugins/yith-woocommerce-catalog-mode/';

    /**
     * @var string Yith WooCommerce Catalog Mode panel page
     */
    protected $_panel_page = 'yith_wc_catalog_mode_panel';

    /**
     * Constructor
     *
     * Initialize plugin and registers actions and filters to be used
     *
     * @since  1.0
     * @author Alberto Ruggiero
     */
    public function __construct() {
        if ( ! function_exists( 'WC' ) ) {
            return;
        }

        // Load Plugin Framework
        add_action( 'after_setup_theme', array( $this, 'plugin_fw_loader' ), 1 );

        //Add action links
        add_filter( 'plugin_action_links_' . plugin_basename( YWCTM_DIR . '/' . basename( YWCTM_FILE ) ), array(
            $this,
            'action_links'
        ) );

        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );

        //  Add stylesheets and scripts files
        add_action( 'admin_menu', array( $this, 'add_menu_page' ), 5 );
        add_action( 'yith_catalog_mode_premium', array( $this, 'premium_tab' ) );

        if ( get_option( 'ywctm_enable_plugin' ) == 'yes' ){

            if ( $this->check_user_admin_enable() ){

                add_action( 'init', array( $this, 'check_pages_status' ) );

                if ( ! is_admin() ) {

                    add_action( 'woocommerce_single_product_summary', array( $this, 'hide_add_to_cart_single' ), 10 );
                    add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'hide_add_to_cart_loop' ), 5 );
                    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

                }

            }

        } else {

            self::reactivate_hidden_pages();

        }

    }

    /**
     * Check if catalog mode is enabled for administrator
     *
     * @since   1.0.2
     * @author  Alberto Ruggiero
     * @return  bool
     */
    public function check_user_admin_enable() {

        return !( current_user_can( 'administrator' ) && is_user_logged_in() &&  get_option( 'ywctm_admin_view' ) == 'no' );

    }

    /**
     * Checks if "Cart & Checkout pages" needs to be hidden
     *
     * @since   1.0.2
     * @author  Alberto Ruggiero
     * @return  bool
     */
    public function check_hide_cart_checkout_pages() {

        return  get_option( 'ywctm_enable_plugin' ) == 'yes' && $this->check_user_admin_enable() && get_option('ywctm_hide_cart_header') == 'yes';

    }

    /**
     * Hides "Add to cart" button from single product page
     *
     * @since   1.0.0
     * @author  Alberto Ruggiero
     * @return  void
     */
    public function hide_add_to_cart_single() {

        $priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );

        if( $this->check_add_to_cart_single( $priority ) ) {

            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', $priority );

        }

    }

    /**
     * Checks if "Add to cart" needs to be hidden
     *
     * @since   1.0.2
     * @author  Alberto Ruggiero
     * @param   $priority
     * @return  bool
     */
    public function check_add_to_cart_single( $priority = true ) {

        $hide = false;

        if ( get_option( 'ywctm_enable_plugin' ) == 'yes' && $this->check_user_admin_enable() && get_option( 'ywctm_hide_add_to_cart_single' ) == 'yes' ) {

            global $post;

            $exclude_catalog  = get_post_meta( $post->ID, '_ywctm_exclude_catalog_mode', true );
            $enable_exclusion = get_option( 'ywctm_exclude_hide_add_to_cart' );

            if ( $priority ) {

                if ( $enable_exclusion == '' || $enable_exclusion == 'no' ) {

                    $hide = true;

                } else {

                    if ( $exclude_catalog == '' || $exclude_catalog == 'no' ) {

                        $hide = true;

                    }
                }
            }
        }

        return $hide;

    }

    /**
     * Hides "Add to cart" button, if not excluded, from loop page
     *
     * @since   1.0.0
     * @author  Alberto Ruggiero
     * @return  void
     */
    public function hide_add_to_cart_loop() {

        if ( get_option( 'ywctm_hide_add_to_cart_loop' ) == 'yes' ) {

            global $post;

            $exclude_catalog  = get_post_meta( $post->ID, '_ywctm_exclude_catalog_mode', true );
            $enable_exclusion = get_option( 'ywctm_exclude_hide_add_to_cart' );

            if ( $enable_exclusion == '' || $enable_exclusion == 'no' ) {
                remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
            } else {
                if ( $exclude_catalog == '' || $exclude_catalog == 'no' ) {
                    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
                } else {
                    add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
                }
            }
        }

    }

    /**
     * Enqueue css file
     *
     * @since   1.0.0
     * @author  Alberto Ruggiero
     * @return  void
     */
    public function enqueue_styles() {
        if ( get_option( 'ywctm_hide_cart_header' ) == 'yes' ) {
            wp_enqueue_style( 'ywctm-style', YWCTM_ASSETS_URL . '/css/yith-catalog-mode.css' );
        }
    }

    /**
     * Hides Cart and Checkout pages if option selected, otherwise shows them
     *
     * @since   1.0.0
     * @author  Alberto Ruggiero
     * @return  void
     */
    public function check_pages_status() {

        $pages_to_check = array(
            get_option( 'woocommerce_cart_page_id' ),
            get_option( 'woocommerce_checkout_page_id' )
        );

        if ( get_option( 'ywctm_hide_cart_header' ) == 'yes' ) {

            foreach ( $pages_to_check as $page_id ) {
                if ( get_post_status( $page_id ) != 'draft' ) {
                    $page = array(
                        'ID'          => $page_id,
                        'post_status' => 'draft'
                    );

                    wp_update_post( $page );
                }
            }

        } else {

            foreach ( $pages_to_check as $page_id ) {
                if ( get_post_status ( $page_id ) != 'publish' ) {
                    $page = array(
                        'ID'            => $page_id,
                        'post_status'   => 'publish'
                    );

                    wp_update_post( $page );
                }
            }

        }

    }

    /**
     * Enqueue css file
     *
     * @since  1.0
     * @access public
     * @return void
     * @author Andrea Grillo <andrea.grillo@yithemes.com>
     */
    public function plugin_fw_loader() {
        if ( ! defined( 'YIT' ) || ! defined( 'YIT_CORE_PLUGIN' ) ) {
            require_once( 'plugin-fw/yit-plugin.php' );
        }
    }

    /**
     * Add a panel under YITH Plugins tab
     *
     * @return   void
     * @since    1.0
     * @author   Andrea Grillo <andrea.grillo@yithemes.com>
     * @use     /Yit_Plugin_Panel class
     * @see      plugin-fw/lib/yit-plugin-panel.php
     */
    public function add_menu_page() {
        if ( ! empty( $this->_panel ) ) {
            return;
        }

        $admin_tabs = array(
            'settings'      => __( 'Settings', 'ywctm' ),
        );

        if ( defined( 'YWCTM_PREMIUM' ) ) {
            $admin_tabs['premium'] = __( 'Premium Settings', 'ywctm' );
            $admin_tabs['exclusions'] = __( 'Exclusion List', 'ywctm' );
        } else {
            $admin_tabs['premium-landing'] = __( 'Premium Version', 'ywctm' );
        }

        $args = array(
            'create_menu_page' => true,
            'parent_slug'      => '',
            'page_title'       => __( 'Catalog Mode', 'ywctm' ),
            'menu_title'       => __( 'Catalog Mode', 'ywctm' ),
            'capability'       => 'manage_options',
            'parent'           => '',
            'parent_page'      => 'yit_plugin_panel',
            'page'             => $this->_panel_page,
            'admin-tabs'       => $admin_tabs,
            'options-path'     => YWCTM_DIR . '/plugin-options'
        );

        $this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );
    }

    /**
     * Premium Tab Template
     *
     * Load the premium tab template on admin page
     *
     * @since   1.0.0
     * @author  Andrea Grillo <andrea.grillo@yithemes.com>
     * @return  void
     */
    public function premium_tab() {
        $premium_tab_template = YWCTM_TEMPLATE_PATH . '/admin/' . $this->_premium;
        if ( file_exists( $premium_tab_template ) ) {
            include_once( $premium_tab_template );
        }
    }

    /**
     * Get the premium landing uri
     *
     * @since   1.0.0
     * @author  Andrea Grillo <andrea.grillo@yithemes.com>
     * @return  string The premium landing link
     */
    public function get_premium_landing_uri(){
        return defined( 'YITH_REFER_ID' ) ? $this->_premium_landing . '?refer_id=' . YITH_REFER_ID : $this->_premium_landing;
    }

    /**
     * Action Links
     *
     * add the action links to plugin admin page
     *
     * @param $links | links plugin array
     *
     * @return   mixed Array
     * @since    1.0
     * @author   Andrea Grillo <andrea.grillo@yithemes.com>
     * @return mixed
     * @use plugin_action_links_{$plugin_file_name}
     */
    public function action_links( $links ) {

        $links[] = '<a href="' . admin_url( "admin.php?page={$this->_panel_page}" ) . '">' . __( 'Settings', 'ywctm' ) . '</a>';

        if ( defined( 'YWCTM_FREE_INIT' ) ) {
            $links[] = '<a href="' . $this->get_premium_landing_uri() . '" target="_blank">' . __( 'Premium Version', 'ywctm' ) . '</a>';
        }

        return $links;
    }

    /**
     * plugin_row_meta
     *
     * add the action links to plugin admin page
     *
     * @param $plugin_meta
     * @param $plugin_file
     * @param $plugin_data
     * @param $status
     *
     * @return   Array
     * @since    1.0
     * @author   Andrea Grillo <andrea.grillo@yithemes.com>
     * @use plugin_row_meta
     */
    public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
        if ( ( defined( 'YWCTM_INIT' ) && ( YWCTM_INIT == $plugin_file ) ) ||
            ( defined( 'YWCTM_FREE_INIT' ) && ( YWCTM_FREE_INIT == $plugin_file ) )
        ) {

            $plugin_meta[] = '<a href="' . $this->_official_documentation . '" target="_blank">' . __( 'Plugin Documentation', 'ywctm' ) . '</a>';
        }

        return $plugin_meta;
    }

    /**
     * On plugin deactivation, publish cart and checkout pages if se to draft
     *
     * @since   1.0.0
     * @author  Alberto Ruggiero
     * @return  void
     */
    static function reactivate_hidden_pages () {

        $pages_to_check = array(
            get_option( 'woocommerce_cart_page_id' ),
            get_option( 'woocommerce_checkout_page_id' )
        );

        foreach ( $pages_to_check as $page_id ) {
            if ( get_post_status ( $page_id ) != 'publish' ) {
                $page = array(
                    'ID'            => $page_id,
                    'post_status'   => 'publish'
                );

                wp_update_post( $page );
            }
        }
    }

}