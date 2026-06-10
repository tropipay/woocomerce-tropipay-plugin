<?php

/**
 * TropiPay WooCommerce Gateway Uninstall
 *
 * Runs when the plugin is deleted from the WordPress administrative dashboard.
 * Deletes all stored configuration options and cache databases.
 *
 * @package WooCommerce\Gateways
 * @version 5.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete options from WordPress options table
delete_option( 'woocommerce_tropipay_settings' );
delete_option( 'wc_tropipay_accounts' );
delete_option( 'wc_tropipay_is_marketplace' );

// Clean up any potential multisite options as well if multisite is active
if ( is_multisite() ) {
    $tropipay_sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
    foreach ( $tropipay_sites as $tropipay_blog_id ) {
        switch_to_blog( $tropipay_blog_id );
        delete_option( 'woocommerce_tropipay_settings' );
        delete_option( 'wc_tropipay_accounts' );
        delete_option( 'wc_tropipay_is_marketplace' );
        restore_current_blog();
    }
}
