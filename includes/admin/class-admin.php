<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main admin class
 */
class WC_Installments_Manager_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        // Add admin notices
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }
    
    /**
     * Display admin notices from transients
     */
    public function display_admin_notices() {
        $notice = get_transient('wc_installments_admin_notice');
        if ($notice) {
            echo '<div class="notice notice-' . esc_attr($notice['type']) . ' is-dismissible"><p>' . wp_kses_post($notice['message']) . '</p></div>';
            delete_transient('wc_installments_admin_notice');
        }
    }
}