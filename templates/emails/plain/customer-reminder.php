<?php
/**
 * Customer payment reminder plain email
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . $email_heading . " =\n\n";

echo sprintf(__('Dear %s,', 'wc-installments'), $customer_name) . "\n\n";

echo __('This is a friendly reminder that your installment payment is due soon.', 'wc-installments') . "\n\n";

echo __('Order:', 'wc-installments') . " #" . $order_number . "\n";
echo __('Amount Due:', 'wc-installments') . " " . $amount_due . "\n";
echo __('Due Date:', 'wc-installments') . " " . $due_date . "\n";
echo __('Payment:', 'wc-installments') . " " . $payment_number . " " . __('of', 'wc-installments') . " " . $total_payments . "\n\n";

echo __('To make your payment, please visit:', 'wc-installments') . "\n";
echo $payment_link . "\n\n";

echo __('Thank you for your business!', 'wc-installments') . "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));