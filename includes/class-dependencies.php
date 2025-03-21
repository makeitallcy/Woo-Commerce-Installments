<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dependency checker
 */
class WC_Installments_Manager_Dependencies {
    /**
     * Check if plugin dependencies are available
     */
    public static function check_dependencies() {
        // Check for WooCommerce
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Show missing dependencies notice
     */
    public static function show_missing_dependencies() {
        $missing = [];
        
        if (!class_exists('WooCommerce')) {
            $missing[] = '<a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>';
        }
        
        if (empty($missing)) {
            return;
        }
        
        echo '<div class="error"><p>';
        printf(
            __('WooCommerce Installments Manager requires the following plugins to be installed and activated: %s', 'wc-installments'),
            implode(', ', $missing)
        );
        echo '</p></div>';
    }
}