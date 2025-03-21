<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Installation related functions
 */
class WC_Installments_Manager_Install {
    /**
     * Install the plugin
     */
    public static function install() {
        // Create/update database tables if needed
        self::create_tables();
        
        // Create custom order status
        self::create_order_status();
        
        // Set default options
        self::set_default_options();
        
        // Set version
        update_option('wc_installments_version', WC_INSTALLMENTS_VERSION);
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        // Create any necessary database tables here
        global $wpdb;
        
        $wpdb->hide_errors();
        
        $collate = '';
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }
        
        // Example: Create a custom table if needed
        /*
        $table_name = $wpdb->prefix . 'wc_installment_plans';
        $sql = "CREATE TABLE $table_name (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          customer_id bigint(20) NOT NULL,
          status varchar(20) NOT NULL,
          total decimal(19,4) NOT NULL DEFAULT 0,
          installments int(11) NOT NULL,
          date_created datetime NOT NULL,
          PRIMARY KEY  (id)
        ) $collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        */
    }
    
    /**
     * Create custom order status
     */
    private static function create_order_status() {
        // Register custom order status if not already registered
        if (!get_term_by('slug', 'wc-installment', 'shop_order_status')) {
            wp_insert_term(
                'installment',
                'shop_order_status',
                [
                    'description' => __('Installment payment pending', 'wc-installments'),
                    'slug' => 'wc-installment',
                ]
            );
        }
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        // General options
        add_option('wc_installments_debug_mode', 'no');
        add_option('wc_installments_default_status', 'wc-pending');
        
        // WP Fusion options
        add_option('wc_installments_wpf_tag', '537');
        add_option('wc_installments_remove_tag', 'yes');
        
        // Email options
        add_option('wc_installments_enable_emails', 'yes');
        add_option('wc_installments_notify_admin', 'yes');
        
        // Email templates
        if (!get_option('wc_installments_email_template')) {
            add_option('wc_installments_email_template', wc_installments_get_default_email_template());
        }
        
        if (!get_option('wc_installments_admin_email_template')) {
            add_option('wc_installments_admin_email_template', wc_installments_get_default_admin_email_template());
        }
        
        // Reminder options
        add_option('wc_installments_enable_reminders', 'yes');
        add_option('wc_installments_reminder_days', '3');
        
        if (!get_option('wc_installments_reminder_template')) {
            add_option('wc_installments_reminder_template', wc_installments_get_default_reminder_template());
        }
    }
}