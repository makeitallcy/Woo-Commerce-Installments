<?php
/**
 * WooCommerce Installments Manager Uninstall
 *
 * Uninstalling WooCommerce Installments Manager deletes user roles, options, tables, and pages.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include necessary WooCommerce files
include_once dirname(dirname(__FILE__)) . '/woocommerce/includes/class-wc-install.php';
include_once dirname(dirname(__FILE__)) . '/woocommerce/includes/wc-core-functions.php';

// Only run if WooCommerce is active
if (!is_callable('WC')) {
    return;
}

global $wpdb;

// Delete options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wc_installments_%'");

// Clear scheduled hooks
wp_clear_scheduled_hook('wc_installments_send_reminders');

// Remove user meta
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = '_installment_payment_logs'");

// Remove post meta (optional - you might want to keep this data for historical records)
// $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '\_installment\_%'");

// Flush rewrite rules
flush_rewrite_rules();