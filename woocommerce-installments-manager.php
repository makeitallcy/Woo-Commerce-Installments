<?php
/**
 * Plugin Name: WooCommerce Installments Manager
 * Description: Manages installment orders and displays them in admin and customer dashboard
 * Version: 2.0.0
 * Author: Ascend Ranks LTD
 * Text Domain: wc-installments
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 4.0
 * WC tested up to: 7.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WC_INSTALLMENTS_VERSION', '2.0.0');
define('WC_INSTALLMENTS_FILE', __FILE__);
define('WC_INSTALLMENTS_PATH', plugin_dir_path(__FILE__));
define('WC_INSTALLMENTS_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
final class WC_Installments_Manager {
    /**
     * @var WC_Installments_Manager Single instance
     */
    private static $instance = null;

    /**
     * @var WC_Installments_Manager_Admin Admin instance
     */
    public $admin = null;

    /**
     * @var WC_Installments_Manager_Settings Settings instance
     */
    public $settings = null;

    /**
     * @var WC_Installments_Manager_Plan_Manager Plan manager instance
     */
    public $plan_manager = null;

    /**
     * Main plugin instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
        $this->init_classes();
        
        do_action('wc_installments_loaded');
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(WC_INSTALLMENTS_FILE, [$this, 'activate']);
        register_deactivation_hook(WC_INSTALLMENTS_FILE, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
    }

    /**
     * Include required files
     */
    private function includes() {
        // Include core files
        require_once WC_INSTALLMENTS_PATH . 'includes/functions.php';
        
        // Include admin files
        if (is_admin()) {
            require_once WC_INSTALLMENTS_PATH . 'includes/admin/class-admin.php';
            require_once WC_INSTALLMENTS_PATH . 'includes/admin/class-settings.php';
            require_once WC_INSTALLMENTS_PATH . 'includes/admin/class-plan-manager.php';
        }
    }

    /**
     * Initialize plugin classes
     */
    private function init_classes() {
        // Initialize admin classes
        if (is_admin()) {
            $this->settings = new WC_Installments_Manager_Settings();
            $this->plan_manager = new WC_Installments_Manager_Plan_Manager();
            $this->admin = new WC_Installments_Manager_Admin();
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Ensure rewrite rules are flushed
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up here if needed
    }

    /**
     * On plugins loaded
     */
    public function on_plugins_loaded() {
        // Define debug mode
        if (!defined('WC_INSTALLMENTS_DEBUG')) {
            define('WC_INSTALLMENTS_DEBUG', get_option('wc_installments_debug_mode', 'no') === 'yes');
        }
    }
}

/**
 * Main instance of plugin
 *
 * @return WC_Installments_Manager
 */
function WC_Installments() {
    return WC_Installments_Manager::instance();
}

// Initialize the plugin
add_action('plugins_loaded', 'WC_Installments', 10);