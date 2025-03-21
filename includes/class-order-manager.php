<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order manager class
 */
class WC_Installments_Manager_Order_Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register custom order status
        add_action('init', [$this, 'register_custom_order_status']);
        
        // Add custom status to WooCommerce order statuses
        add_filter('wc_order_statuses', [$this, 'add_custom_order_status']);
        
        // Handle order status changes
        add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_change'], 10, 4);
        
        // Add custom bulk actions
        add_filter('bulk_actions-edit-shop_order', [$this, 'add_custom_bulk_actions']);
        add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handle_custom_bulk_actions'], 10, 3);
        
        // Add order meta box
        add_action('add_meta_boxes', [$this, 'add_order_meta_box']);
    }
    
    /**
     * Register custom order status
     */
    public function register_custom_order_status() {
        register_post_status('wc-installment', [
            'label' => __('Installment', 'wc-installments'),
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop(
                'Installment <span class="count">(%s)</span>',
                'Installments <span class="count">(%s)</span>',
                'wc-installments'
            )
        ]);
    }
    
    /**
     * Add custom order status to WooCommerce
     */
    public function add_custom_order_status($order_statuses) {
        $order_statuses['wc-installment'] = __('Installment', 'wc-installments');
        return $order_statuses;
    }
    
    /**
     * Handle order status changes
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        // Skip if no status change
        if ($old_status === $new_status) {
            return;
        }

        // Check if this is an installment order
        if (!$order->get_meta('_installment_number')) {
            return;
        }

        // Process when status changes to completed
        if ($new_status === 'completed') {
            $this->process_installment_payment($order);
            
            // Add note about how the order was completed
            if ($order->get_date_paid()) {
                $order->add_order_note(__('Installment payment automatically processed through payment gateway.', 'wc-installments'));
            } else {
                $order->add_order_note(__('Installment payment manually marked as completed.', 'wc-installments'));
            }
        }
    }
    
    /**
     * Process installment payment
     */
    public function process_installment_payment($order) {
        // Log the payment
        $this->log_installment_payment($order->get_id());
        
        $customer_id = $order->get_customer_id();
        
        // Check if all installments are paid
        if ($this->check_all_installments_paid($customer_id)) {
            // Handle WP Fusion tags
            $this->handle_wp_fusion_on_completion($customer_id, $order);
            
            // Add completion notes and trigger actions
            $related_orders = $this->get_customer_installment_orders($customer_id);
            foreach ($related_orders as $related_order) {
                $related_order->add_order_note(__('All installments for this plan have been paid.', 'wc-installments'));
            }
            
            // Send notifications if enabled
            if (get_option('wc_installments_enable_emails', 'yes') === 'yes') {
                $this->send_completion_notifications($customer_id, $related_orders);
            }
            
            do_action('wc_installments_plan_completed', $customer_id, $related_orders);
        }
        
        // Trigger action for payment processed
        do_action('wc_installment_payment_processed', $order, $customer_id);
    }
    
    /**
     * Check if all installments are paid for a customer
     */
    public function check_all_installments_paid($customer_id) {
        $installment_status = str_replace('wc-', '', get_option('wc_installments_default_status', 'wc-pending'));
        
        // Get all installment orders for this customer
        $orders = wc_get_orders([
            'customer' => $customer_id,
            'status' => [$installment_status],
            'meta_key' => '_total_installments',
            'return' => 'ids'
        ]);

        // If no installment orders found in pending status, return true (all paid)
        return empty($orders);
    }
    
    /**
     * Get all installment orders for a customer
     */
    public function get_customer_installment_orders($customer_id) {
        return wc_get_orders([
            'customer' => $customer_id,
            'meta_key' => '_total_installments',
            'limit' => -1
        ]);
    }
    
    /**
     * Handle WP Fusion tags on plan completion
     */
    private function handle_wp_fusion_on_completion($customer_id, $order) {
        if (function_exists('wp_fusion') && method_exists(wp_fusion()->user, 'remove_tags') && 
            get_option('wc_installments_remove_tag', 'yes') === 'yes') {
            
            $tag_id = absint(get_option('wc_installments_wpf_tag', 537));
            wp_fusion()->user->remove_tags([$tag_id], $customer_id);
            
            $order->add_order_note(sprintf(
                __('All installments paid - Removed WP Fusion tag ID: %d from customer ID: %d', 'wc-installments'),
                $tag_id,
                $customer_id
            ));
        }
    }
    
    /**
     * Log installment payment
     */
    public function log_installment_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        // Check if this is an installment order
        $installment_number = $order->get_meta('_installment_number');
        $total_installments = $order->get_meta('_total_installments');
        
        if (!$installment_number || !$total_installments) {
            return false;
        }

        // Add payment log
        $payment_log = [
            'payment_date' => current_time('mysql'),
            'amount' => $order->get_total(),
            'installment_number' => $installment_number,
            'total_installments' => $total_installments,
            'payment_method' => $order->get_payment_method_title(),
            'is_manual' => !$order->get_date_paid()
        ];

        // Store payment log in customer meta
        $customer_id = $order->get_customer_id();
        $payment_logs = get_user_meta($customer_id, '_installment_payment_logs', true);
        if (!is_array($payment_logs)) {
            $payment_logs = [];
        }
        $payment_logs[$order_id] = $payment_log;
        update_user_meta($customer_id, '_installment_payment_logs', $payment_logs);

        // Add order note
        $order->add_order_note(
            sprintf(
                __('Installment payment %d of %d processed. Amount: %s. Method: %s', 'wc-installments'),
                $installment_number,
                $total_installments,
                $order->get_formatted_order_total(),
                $payment_log['is_manual'] ? __('Manual completion', 'wc-installments') : $payment_log['payment_method']
            )
        );
        
        return true;
    }
    
    /**
     * Send completion notifications
     */
    public function send_completion_notifications($customer_id, $orders) {
        $customer = get_user_by('id', $customer_id);
        if (!$customer) {
            return;
        }

        $payment_manager = new WC_Installments_Manager_Payment_Manager();
        $payment_manager->send_completion_notifications($customer_id, $orders);
    }
    
    /**
     * Send payment reminders
     */
    public function send_payment_reminders() {
        // Skip if reminders are disabled
        if (get_option('wc_installments_enable_reminders', 'yes') !== 'yes') {
            return;
        }
        
        $payment_manager = new WC_Installments_Manager_Payment_Manager();
        $payment_manager->send_reminder_emails();
    }
    
    /**
     * Add custom bulk actions
     */
    public function add_custom_bulk_actions($bulk_actions) {
        $bulk_actions['mark_installment'] = __('Change status to Installment', 'wc-installments');
        return $bulk_actions;
    }
    
    /**
     * Handle custom bulk actions
     */
    public function handle_custom_bulk_actions($redirect_to, $action, $post_ids) {
        if ($action !== 'mark_installment') {
            return $redirect_to;
        }

        foreach ($post_ids as $post_id) {
            $order = wc_get_order($post_id);
            if ($order) {
                $order->update_status('installment', __('Order status changed to Installment via bulk action.', 'wc-installments'));
            }
        }

        return $redirect_to;
    }
    
    /**
     * Add order meta box
     */
    public function add_order_meta_box() {
        add_meta_box(
            'wc_installment_details',
            __('Installment Details', 'wc-installments'),
            [$this, 'render_order_meta_box'],
            'shop_order',
            'side',
            'high'
        );
    }
    
    /**
     * Render order meta box
     */
    public function render_order_meta_box($post) {
        $order = wc_get_order($post->ID);
        if (!$order) return;
        
        $installment_number = $order->get_meta('_installment_number');
        $total_installments = $order->get_meta('_total_installments');
        
        if (!$installment_number || !$total_installments) {
            echo '<p>' . esc_html__('This is not an installment order.', 'wc-installments') . '</p>';
            return;
        }
        
        include WC_INSTALLMENTS_PATH . 'templates/admin/order-meta-box.php';
    }
}