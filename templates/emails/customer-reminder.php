<?php
/**
 * Customer payment reminder email
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php printf(__('Dear %s,', 'wc-installments'), $customer_name); ?></p>

<p><?php _e('This is a friendly reminder that your installment payment is due soon.', 'wc-installments'); ?></p>

<p><strong><?php _e('Order:', 'wc-installments'); ?></strong> #<?php echo $order_number; ?><br>
<strong><?php _e('Amount Due:', 'wc-installments'); ?></strong> <?php echo $amount_due; ?><br>
<strong><?php _e('Due Date:', 'wc-installments'); ?></strong> <?php echo $due_date; ?><br>
<strong><?php _e('Payment:', 'wc-installments'); ?></strong> <?php echo $payment_number; ?> <?php _e('of', 'wc-installments'); ?> <?php echo $total_payments; ?></p>

<p>
    <a href="<?php echo $payment_link; ?>" class="button button-primary" style="display: inline-block; padding: 10px 15px; background-color: #2271b1; color: #ffffff; text-decoration: none; border-radius: 3px; margin: 10px 0;">
        <?php _e('Make Payment Now', 'wc-installments'); ?>
    </a>
</p>

<p><?php _e('Thank you for your business!', 'wc-installments'); ?></p>

<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);