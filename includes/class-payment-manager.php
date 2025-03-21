<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment manager class
 */
class WC_Installments_Manager_Payment_Manager {
    /**
     * Send completion notifications
     */
    public function send_completion_notifications($customer_id, $orders) {
        $customer = get_user_by('id', $customer_id);
        if (!$customer) {
            return false;
        }

        // Calculate total amount paid
        $total_amount = array_sum(array_map(function($order) {
            return $order->get_total();
        }, $orders));
        
        $this->send_customer_completion_email($customer, $orders, $total_amount);
        
        // Notify admin if enabled
        if (get_option('wc_installments_notify_admin', 'yes') === 'yes') {
            $this->send_admin_completion_email($customer, $orders, $total_amount);
        }
        
        return true;
    }
    
    /**
     * Send customer completion email
     */
    private function send_customer_completion_email($customer, $orders, $total_amount) {
        // Get email template
        $email_template = get_option('wc_installments_email_template', '');
        if (empty($email_template)) {
            $email_template = wc_installments_get_default_email_template();
        }
        
        // Replace placeholders
        $replacements = [
            '{customer_name}' => $customer->display_name,
            '{total_installments}' => count($orders),
            '{total_amount}' => wc_price($total_amount),
            '{completion_date}' => date_i18n(get_option('date_format')),
            '{site_name}' => get_bloginfo('name')
        ];
        
        $message = str_replace(array_keys($replacements), array_values($replacements), $email_template);
        
        // Send email
        $subject = apply_filters(
            'wc_installments_completion_email_subject', 
            sprintf(__('Congratulations! All installments paid - %s', 'wc-installments'), get_bloginfo('name')),
            $customer->ID, 
            $orders
        );
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($customer->user_email, $subject, $message, $headers);
    }
    
    /**
     * Send admin completion email
     */
    private function send_admin_completion_email($customer, $orders, $total_amount) {
        // Get admin email template
        $admin_template = get_option('wc_installments_admin_email_template', '');
        if (empty($admin_template)) {
            $admin_template = wc_installments_get_default_admin_email_template();
        }
        
        // Replace placeholders
        $replacements = [
            '{customer_name}' => $customer->display_name,
            '{customer_id}' => $customer->ID,
            '{total_installments}' => count($orders),
            '{total_amount}' => wc_price($total_amount),
            '{completion_date}' => date_i18n(get_option('date_format')),
            '{site_name}' => get_bloginfo('name'),
            '{order_ids}' => implode(', ', array_map(function($order) {
                return $order->get_id();
            }, $orders))
        ];
        
        $message = str_replace(array_keys($replacements), array_values($replacements), $admin_template);
        
        // Send email to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('Installment Plan Completed - Customer #%d', 'wc-installments'), $customer->ID);
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Send reminder emails
     */
    public function send_reminder_emails() {
        $reminder_days = absint(get_option('wc_installments_reminder_days', 3));
        $installment_status = str_replace('wc-', '', get_option('wc_installments_default_status', 'wc-pending'));
        
        // Get all orders with installment status
        $orders = wc_get_orders([
            'status' => [$installment_status],
            'meta_key' => '_installment_number',
            'limit' => -1
        ]);
        
        if (empty($orders)) {
            return;
        }
        
        // Current date for comparison
        $current_date = new DateTime();
        $target_date = new DateTime();
        $target_date->modify('+' . $reminder_days . ' days');
        $target_date_string = $target_date->format('Y-m-d');
        
        $sent_count = 0;
        
        foreach ($orders as $order) {
            // Skip if reminder already sent
            if ($order->get_meta('_reminder_sent')) {
                continue;
            }
            
            // Get the date this order was created
            $order_date = $order->get_date_created();
            if (!$order_date) {
                continue;
            }
            
            // Calculate payment due date (30 days from creation or last payment)
            $payment_due_date = new DateTime($order_date->date('Y-m-d'));
            $payment_due_date->modify('+30 days');
            
            // If due date matches target date (reminder days from now), send reminder
            if ($payment_due_date->format('Y-m-d') === $target_date_string) {
                $this->send_reminder_email($order);
                $order->add_meta_data('_reminder_sent', 'yes');
                $order->save();
                $sent_count++;
            }
        }
        
        if (WC_INSTALLMENTS_DEBUG && $sent_count > 0) {
            error_log(sprintf('Sent %d payment reminder emails', $sent_count));
        }
        
        return $sent_count;
    }
    
    /**
     * Send payment reminder email
     */
    private function send_reminder_email($order) {
        $customer_id = $order->get_customer_id();
        $customer = get_user_by('id', $customer_id);
        
        if (!$customer) {
            return false;
        }
        
        $reminder_days = absint(get_option('wc_installments_reminder_days', 3));
        $installment_number = $order->get_meta('_installment_number');
        $total_installments = $order->get_meta('_total_installments');
        
        // Calculate due date
        $order_date = $order->get_date_created();
        $payment_due_date = new DateTime($order_date->date('Y-m-d'));
        $payment_due_date->modify('+30 days');
        
        // Get template
        $template = get_option('wc_installments_reminder_template', '');
        if (empty($template)) {
            $template = wc_installments_get_default_reminder_template();
        }
        
        // Replace placeholders
        $replacements = [
            '{customer_name}' => $customer->display_name,
            '{order_number}' => $order->get_order_number(),
            '{amount_due}' => $order->get_formatted_order_total(),
            '{due_date}' => date_i18n(get_option('date_format'), $payment_due_date->getTimestamp()),
            '{payment_number}' => $installment_number,
            '{total_payments}' => $total_installments,
            '{payment_link}' => $order->get_checkout_payment_url()
        ];
        
        $message = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        // Send email
        $subject = sprintf(
            __('Payment Reminder: Installment %d of %d', 'wc-installments'),
            $installment_number,
            $total_installments
        );
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($customer->user_email, $subject, $message, $headers);
    }
}