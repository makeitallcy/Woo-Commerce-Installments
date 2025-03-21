<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customer manager class
 */
class WC_Installments_Manager_Customer_Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Add customer display hooks
        add_action('show_user_profile', [$this, 'display_installment_payment_logs']);
        add_action('edit_user_profile', [$this, 'display_installment_payment_logs']);
        
        // Add installment orders column to admin users list
        add_filter('manage_users_columns', [$this, 'add_installment_orders_column']);
        add_filter('manage_users_custom_column', [$this, 'show_installment_orders_column_content'], 10, 3);
        add_filter('manage_users_sortable_columns', [$this, 'make_installment_orders_column_sortable']);
    }
    
    /**
     * Display installment payment logs in admin user profile
     */
    public function display_installment_payment_logs($user) {
        $payment_logs = wc_installments_get_payment_logs($user->ID);
        
        if (empty($payment_logs)) {
            return;
        }
        
        include WC_INSTALLMENTS_PATH . 'templates/admin/user-payment-logs.php';
    }
    
    /**
     * Add installment orders column to admin users list
     */
    public function add_installment_orders_column($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'email') {
                $new_columns['installment_orders'] = __('Installment Orders', 'wc-installments');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display installment orders count in admin users list
     */
    public function show_installment_orders_column_content($value, $column_name, $user_id) {
        if ($column_name === 'installment_orders') {
            $installment_status = str_replace('wc-', '', wc_installments_get_status());
            $orders = wc_get_orders([
                'customer' => $user_id,
                'status' => [$installment_status],
                'return' => 'ids',
                'meta_key' => '_total_installments',
            ]);
            
            if (!empty($orders)) {
                $count = count($orders);
                $url = admin_url('edit.php?post_type=shop_order&_customer_user=' . $user_id . '&post_status=' . wc_installments_get_status());
                return '<a href="' . esc_url($url) . '">' . $count . ' ' . __('order(s)', 'wc-installments') . '</a>';
            }
            return '0';
        }
        return $value;
    }
    
    /**
     * Make the column sortable
     */
    public function make_installment_orders_column_sortable($columns) {
        $columns['installment_orders'] = 'installment_orders';
        return $columns;
    }
    
    /**
     * Get customer installment summary
     */
    public function get_customer_installment_summary($customer_id) {
        $installment_status = str_replace('wc-', '', wc_installments_get_status());
        $active_orders = wc_get_orders([
            'customer' => $customer_id,
            'status' => [$installment_status],
            'meta_key' => '_installment_number',
        ]);
        
        $payment_logs = wc_installments_get_payment_logs($customer_id);
        
        $active_total = array_sum(array_map(function($order) {
            return $order->get_total();
        }, $active_orders));
        
        $completed_total = array_sum(array_map(function($log) {
            return $log['amount'];
        }, $payment_logs));
        
        return [
            'active_orders' => count($active_orders),
            'completed_payments' => count($payment_logs),
            'active_total' => $active_total,
            'completed_total' => $completed_total,
            'total_due' => $active_total,
        ];
    }
}