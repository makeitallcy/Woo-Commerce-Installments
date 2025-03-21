<?php
/**
 * Admin order meta box template
 */

if (!defined('ABSPATH')) {
    exit;
}

$installment_number = $order->get_meta('_installment_number');
$total_installments = $order->get_meta('_total_installments');
$plan_id = $order->get_meta('_installment_plan_id');
$customer_id = $order->get_customer_id();

?>
<p>
    <strong><?php _e('Installment:', 'wc-installments'); ?></strong> 
    <?php echo esc_html($installment_number) . ' ' . esc_html__('of', 'wc-installments') . ' ' . esc_html($total_installments); ?>
</p>

<?php if ($plan_id) : ?>
    <p>
        <strong><?php _e('Plan ID:', 'wc-installments'); ?></strong> 
        <?php echo esc_html($plan_id); ?>
    </p>
<?php endif; ?>

<?php
// Get other orders in this plan
$related_orders = wc_get_orders([
    'meta_key' => '_installment_plan_id',
    'meta_value' => $plan_id,
    'meta_compare' => '=',
    'exclude' => [$order->get_id()],
    'limit' => -1
]);

if (!empty($related_orders)) : ?>
    <p><strong><?php _e('Related Orders:', 'wc-installments'); ?></strong></p>
    <ul>
        <?php foreach ($related_orders as $related_order) : 
            $rel_installment = $related_order->get_meta('_installment_number'); ?>
            <li>
                <a href="<?php echo esc_url(admin_url('post.php?post=' . $related_order->get_id() . '&action=edit')); ?>">
                    <?php echo sprintf(
                        esc_html__('#%1$s (Installment %2$s)', 'wc-installments'),
                        $related_order->get_order_number(),
                        $rel_installment
                    ); ?>
                </a> - 
                <?php echo esc_html(wc_get_order_status_name($related_order->get_status())); ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- Payment status -->
<p>
    <strong><?php _e('Payment Status:', 'wc-installments'); ?></strong><br>
    <?php if ($order->is_paid()) : ?>
        <span style="color: #4CAF50;"><?php _e('Paid', 'wc-installments'); ?></span>
    <?php else : ?>
        <span style="color: #f44336;"><?php _e('Unpaid', 'wc-installments'); ?></span>
    <?php endif; ?>
</p>

<!-- Action buttons -->
<div class="installment-actions">
    <?php if (!$order->is_paid()) : ?>
        <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="button" target="_blank">
            <?php _e('Payment Link', 'wc-installments'); ?>
        </a>
    <?php endif; ?>
    
    <?php if ($customer_id) : ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=installment-reports&report=customers&customer_id=' . $customer_id)); ?>" class="button">
            <?php _e('Customer History', 'wc-installments'); ?>
        </a>
    <?php endif; ?>
</div>