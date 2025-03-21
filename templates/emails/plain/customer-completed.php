<?php
/**
 * Customer completed installment plan plain email
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . $email_heading . " =\n\n";

echo sprintf(__('Dear %s,', 'wc-installments'), $customer_name) . "\n\n";

echo __('Thank you for completing all payments for your installment plan. We appreciate your business!', 'wc-installments') . "\n\n";

echo __('Your payment plan details:', 'wc-installments') . "\n";
echo sprintf(__('Total Installments: %s', 'wc-installments'), $total_installments) . "\n";
echo sprintf(__('Total Amount Paid: %s', 'wc-installments'), $total_amount) . "\n";
echo sprintf(__('Completion Date: %s', 'wc-installments'), $completion_date) . "\n\n";

echo __('If you have any questions, please don\'t hesitate to contact us.', 'wc-installments') . "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));