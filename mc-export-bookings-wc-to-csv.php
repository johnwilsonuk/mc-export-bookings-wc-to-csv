<?php
/**
 * Plugin Name: MC Export Bookings WC to CSV
 * Plugin URI: https://github.com/MarieComet/mc-export-bookings-wc-to-csv
 * Description: MC Export Bookings WC to CSV provides user ability to Export WooCommerce Bookings to CSV
 * Author: Marie Comet
 * Author URI: https://www.mariecomet.fr
 * Version: 1.0.3
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: export-bookings-to-csv
 * Domain Path: /languages/
 * WC requires at least: 2.2
 * WC tested up to: 3.4.4
 * WC Booking tested up to : 1.11.1
 */
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

define( 'MC_WCB_CSV', plugin_dir_url( __FILE__ ) );
// i18n

load_plugin_textdomain( 'export-bookings-to-csv', false, basename( dirname( __FILE__ ) ) . '/languages/' );

/* Register activation hook. */
register_activation_hook( __FILE__, 'mc_wcb_csv_activation_hook' );

/**
 * Runs only when the plugin is activated.
 * @since 1.0.2
 */
function mc_wcb_csv_activation_hook() {
    /* Create transient data */
    set_transient( 'mc-wcb-admin-notice', true, 5 );
    $upload_dir = wp_upload_dir();
    $make_dir_export = wp_mkdir_p( $upload_dir['basedir'] . '/woocommerce-bookings-exports/' );
}

add_action( 'admin_notices', 'mc_wcb_csv_missing_notice' );

/**
* Admin Notice on Activation.
* @since 1.0.2
*/
function mc_wcb_csv_missing_notice() {
 
    /* Check transient, if available display notice */
    if ( get_transient( 'mc-wcb-admin-notice' )
    	&& !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )
    	|| get_transient( 'mc-wcb-admin-notice' )
    	&& !in_array( 'woocommerce-bookings/woocommerce-bookings.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'MC Export Bookings WC to CSV plugin needs "WooCommerce" and "WooCommerce Bookings" to run. Please download and activate it', 'export-bookings-to-csv' ); ?></p>
        </div>
        <?php
        /* Delete transient, only display this notice once. */
        delete_transient( 'mc-wcb-admin-notice' );
    }
}
/*
* Load main class
*/
add_action( 'plugins_loaded', 'mc_wcb_csv_load' );
function mc_wcb_csv_load() {
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) 
    && in_array( 'woocommerce-bookings/woocommerce-bookings.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        include_once('includes/class-mc-export-bookings.php');
    }
}