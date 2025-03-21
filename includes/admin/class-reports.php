<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reports class
 */
class WC_Installments_Manager_Reports {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'register_reports_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_post_export_installments_csv', [$this, 'handle_export_csv']);
    }
    
    /**
     * Register reports page
     */
    public function register_reports_page() {
        add_submenu_page(
            'create-installment-plan',
            __('Installment Reports', 'wc-installments'),
            __('Reports', 'wc-installments'),
            'manage_woocommerce',
            'installment-reports',
            [$this, 'render_reports_page']
        );
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'installment-plans_page_installment-reports') {
            return;
        }
        
        wp_enqueue_style('wc-installments-admin', WC_INSTALLMENTS_URL . 'assets/css/admin.css', [], WC_INSTALLMENTS_VERSION);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.1', true);
        wp_enqueue_script('wc-installments-reports', WC_INSTALLMENTS_URL . 'assets/js/reports.js', ['jquery', 'chart-js'], WC_INSTALLMENTS_VERSION, true);
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-installments'));
        }
        
        // Get report type from URL parameters
        $report_type = isset($_GET['report']) ? sanitize_text_field($_GET['report']) : 'overview';
        
        // Load appropriate template
        include WC_INSTALLMENTS_PATH . 'templates/admin/reports.php';
    }
    
    /**
     * Handle CSV export
     */
    public function handle_export_csv() {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-installments'));
        }
        
        // Verify nonce
        if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'export_installments_csv')) {
            wp_die(__('Security check failed.', 'wc-installments'));
        }
        
        // Get all installment orders
        $orders = wc_get_orders([
            'meta_key' => '_installment_number',
            'limit' => -1
        ]);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="installments-' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            __('Order ID', 'wc-installments'),
            __('Date', 'wc-installments'),
            __('Customer ID', 'wc-installments'),
            __('Customer Name', 'wc-installments'),
            __('Customer Email', 'wc-installments'),
            __('Status', 'wc-installments'),
            __('Amount', 'wc-installments'),
            __('Installment', 'wc-installments'),
            __('Total Installments', 'wc-installments'),
            __('Plan ID', 'wc-installments')
        ]);
        
        // Add data rows
        foreach ($orders as $order) {
            $customer_id = $order->get_customer_id();
            $customer = get_user_by('id', $customer_id);
            
            fputcsv($output, [
                $order->get_id(),
                $order->get_date_created()->date('Y-m-d H:i:s'),
                $customer_id,
                $customer ? $customer->display_name : __('Guest', 'wc-installments'),
                $customer ? $customer->user_email : '',
                $order->get_status(),
                $order->get_total(),
                $order->get_meta('_installment_number'),
                $order->get_meta('_total_installments'),
                $order->get_meta('_installment_plan_id')
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Render overview report
     */
    public function render_overview_report() {
        include WC_INSTALLMENTS_PATH . 'templates/admin/reports-overview.php';
    }
    
    /**
     * Render customers report
     */
    public function render_customers_report() {
        include WC_INSTALLMENTS_PATH . 'templates/admin/reports-customers.php';
    }
    
    /**
     * Render payments report
     */
    public function render_payments_report() {
        include WC_INSTALLMENTS_PATH . 'templates/admin/reports-payments.php';
    }
    
    /**
     * Get report data for overview
     */
    public function get_overview_data() {
        $installment_status = str_replace('wc-', '', wc_installments_get_status());
        
        // Active orders
        $active_orders = wc_get_orders([
            'status' => [$installment_status],
            'meta_key' => '_installment_number',
            'return' => 'ids'
        ]);
        
        $total_active = count($active_orders);
        
        // Completed orders
        $completed_orders = wc_get_orders([
            'status' => ['completed'],
            'meta_key' => '_installment_number',
            'return' => 'ids'
        ]);
        
        $total_completed = count($completed_orders);
        
        // Calculate total amounts
        $active_total = 0;
        $completed_total = 0;
        
        foreach ($active_orders as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $active_total += $order->get_total();
            }
        }
        
        foreach ($completed_orders as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $completed_total += $order->get_total();
            }
        }
        
        // Count customers
        $total_customers = count(get_users([
            'meta_key' => '_installment_payment_logs',
            'fields' => 'ids'
        ]));
        
        // Recent orders
        $recent_orders = wc_get_orders([
            'meta_key' => '_installment_number',
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        return [
            'active_orders' => $total_active,
            'completed_orders' => $total_completed,
            'active_total' => $active_total,
            'completed_total' => $completed_total,
            'total_customers' => $total_customers,
            'recent_orders' => $recent_orders
        ];
    }
}