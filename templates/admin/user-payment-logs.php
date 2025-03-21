<?php
/**
 * Admin user profile payment logs template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h3><?php echo esc_html__('Installment Payment History', 'wc-installments'); ?></h3>
<table class="widefat">
    <thead>
        <tr>
            <th><?php echo esc_html__('Order', 'wc-installments'); ?></th>
            <th><?php echo esc_html__('Date', 'wc-installments'); ?></th>
            <th><?php echo esc_html__('Amount', 'wc-installments'); ?></th>
            <th><?php echo esc_html__('Installment', 'wc-installments'); ?></th>
            <th><?php echo esc_html__('Payment Method', 'wc-installments'); ?></th>
            <th><?php echo esc_html__('Type', 'wc-installments'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($payment_logs as $order_id => $log) : ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url(admin_url('post.php?post=' . absint($order_id) . '&action=edit')); ?>">
                        #<?php echo esc_html($order_id); ?>
                    </a>
                </td>
                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['payment_date']))); ?></td>
                <td><?php echo esc_html(wc_price($log['amount'])); ?></td>
                <td><?php echo esc_html(sprintf('%d of %d', $log['installment_number'], $log['total_installments'])); ?></td>
                <td><?php echo esc_html($log['payment_method']); ?></td>
                <td><?php echo esc_html($log['is_manual'] ? __('Manual', 'wc-installments') : __('Automatic', 'wc-installments')); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top: 15px;">
    <a href="<?php echo esc_url(admin_url('admin.php?page=installment-reports&report=customers&customer_id=' . $user->ID)); ?>" class="button">
        <?php echo esc_html__('View Detailed Report', 'wc-installments'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=create-installment-plan&customer_id=' . $user->ID)); ?>" class="button">
        <?php echo esc_html__('Create New Plan', 'wc-installments'); ?>
    </a>
</div>