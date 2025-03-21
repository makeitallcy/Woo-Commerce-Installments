<?php
/**
 * Customer completed installment plan email
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php printf(__('Dear %s,', 'wc-installments'), $customer_name); ?></p>

<p><?php _e('Thank you for completing all payments for your installment plan. We appreciate your business!', 'wc-installments'); ?></p>

<p><strong><?php _e('Your payment plan details:', 'wc-installments'); ?></strong><br>
<?php printf(__('Total Installments: %s', 'wc-installments'), $total_installments); ?><br>
<?php printf(__('Total Amount Paid: %s', 'wc-installments'), $total_amount); ?><br>
<?php printf(__('Completion Date: %s', 'wc-installments'), $completion_date); ?></p>

<p><?php _e('If you have any questions, please don\'t hesitate to contact us.', 'wc-installments'); ?></p>

<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);