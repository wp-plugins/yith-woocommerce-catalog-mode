<?php
/*
Plugin Name: YITH WooCommerce Catalog Mode
Plugin URI: http://yithemes.com/themes/plugins/yith-woocommerce-catalog-mode/
Description: YITH Woocommerce Catalog Mode allows you to disable shop functions.
Author: Yithemes
Text Domain: ywctm
Version: 1.0.5
Author URI: http://yithemes.com/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

function ywctm_install_woocommerce_admin_notice() {
    ?>
    <div class="error">
        <p><?php _e( 'YITH WooCommerce Catalog Mode is enabled but not effective. It requires Woocommerce in order to work.', 'ywctm' ); ?></p>
    </div>
    <?php
}

function ywctm_install_free_admin_notice() {
    ?>
    <div class="error">
        <p><?php _e( 'You can\'t activate the free version of YITH WooCommerce Catalog Mode while you are using the premium one.', 'ywctm' ); ?></p>
    </div>
    <?php
}

if ( ! defined( 'YWCTM_VERSION' ) ) {
    define( 'YWCTM_VERSION', '1.0.5' );
}

if ( ! defined( 'YWCTM_FREE_INIT' ) ) {
    define( 'YWCTM_FREE_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YWCTM_FILE' ) ) {
    define( 'YWCTM_FILE', __FILE__ );
}

if ( ! defined( 'YWCTM_DIR' ) ) {
    define( 'YWCTM_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YWCTM_URL' ) ) {
    define( 'YWCTM_URL', plugins_url( '/', __FILE__ ) );
}

if ( ! defined( 'YWCTM_ASSETS_URL' ) ) {
    define( 'YWCTM_ASSETS_URL', YWCTM_URL . 'assets/' );
}

if ( ! defined( 'YWCTM_TEMPLATE_PATH' ) ) {
    define( 'YWCTM_TEMPLATE_PATH', YWCTM_DIR . 'templates/' );
}

function ywctm_init() {

    /* Load YWCTM text domain */
    load_plugin_textdomain( 'ywctm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    global $YITH_WC_Catalog_Mode;
    $YITH_WC_Catalog_Mode = new YITH_WC_Catalog_Mode();
}
add_action( 'ywctm_init', 'ywctm_init' );

function ywctm_install() {

    if ( ! function_exists( 'WC' ) ) {
        add_action( 'admin_notices', 'ywctm_install_woocommerce_admin_notice' );
    }
    elseif ( defined( 'YWCTM_PREMIUM' ) ) {
        add_action( 'admin_notices', 'ywctm_install_free_admin_notice' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
    else {
        do_action( 'ywctm_init' );
    }
}
add_action( 'plugins_loaded', 'ywctm_install', 11 );

/**
 * Init default plugin settings
 */
if ( !function_exists( 'yith_plugin_registration_hook' ) ) {
    require_once 'plugin-fw/yit-plugin-registration-hook.php';
}

require_once( YWCTM_DIR . 'class.yith-woocommerce-catalog-mode.php' );

register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );
register_activation_hook( __FILE__, 'ywctm_plugin_activation' );

function ywctm_plugin_activation () {

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



