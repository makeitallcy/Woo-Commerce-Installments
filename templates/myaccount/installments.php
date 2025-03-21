<?php
/**
 * My Account Installments page
 */

if (!defined('ABSPATH')) {
    exit;
}

$customer_id = get_current_user_id();
$installment_status = str_replace('wc-', '', wc_installments_get_status());
$customer_orders = wc_get_orders([
    'customer' => $customer_id,
    'status' => $installment_status
]);

// Get payment history
$payment_logs = wc_installments_get_payment_logs($customer_id);
?>

<div class="woocommerce-installments">
    <h2><?php _e('Installment Plans', 'wc-installments'); ?></h2>
    
    <?php
    // Display summary if there are any orders
    if ($customer_orders || !empty($payment_logs)) {
        $total_installments = 0;
        $completed_installments = count($payment_logs);
        
        foreach ($customer_orders as $order) {
            $total_installments += intval($order->get_meta('_total_installments'));
        }

        // Calculate progress percentage
        $progress_percentage = 0;
        if ($total_installments > 0) {
            $progress_percentage = min(100, round(($completed_installments / $total_installments) * 100));
        }
        ?>
        
        <div class="installment-summary">
            <h3><?php _e('Payment Progress', 'wc-installments'); ?></h3>
            <p><?php printf(__('Total Installments: %d', 'wc-installments'), $total_installments); ?></p>
            <p><?php printf(__('Completed Payments: %d', 'wc-installments'), $completed_installments); ?></p>
            <p><?php printf(__('Remaining Payments: %d', 'wc-installments'), $total_installments - $completed_installments); ?></p>
            
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo esc_attr($progress_percentage); ?>%;">
                    <span><?php echo esc_html($progress_percentage); ?>%</span>
                </div>
            </div>
        </div>
        
        <style>
            .progress-bar-container {
                width: 100%;
                background-color: #f3f3f3;
                border-radius: 4px;
                margin: 15px 0;
                overflow: hidden;
            }
            .progress-bar {
                height: 24px;
                background-color: #4CAF50;
                text-align: center;
                line-height: 24px;
                color: white;
                transition: width 0.5s;
            }
        </style>
        <?php
    }

    if (!$customer_orders) {
        echo '<p>' . __('No active installment orders found.', 'wc-installments') . '</p>';
    } else {
        ?>
        <h3><?php _e('Active Installments', 'wc-installments'); ?></h3>
        <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
            <thead>
                <tr>
                    <th><?php _e('Order', 'wc-installments'); ?></th>
                    <th><?php _e('Date', 'wc-installments'); ?></th>
                    <th><?php _e('Status', 'wc-installments'); ?></th>
                    <th><?php _e('Amount', 'wc-installments'); ?></th>
                    <th><?php _e('Installment', 'wc-installments'); ?></th>
                    <th><?php _e('Actions', 'wc-installments'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customer_orders as $order) : ?>
                    <tr>
                        <td data-title="<?php _e('Order', 'wc-installments'); ?>">#<?php echo esc_html($order->get_order_number()); ?></td>
                        <td data-title="<?php _e('Date', 'wc-installments'); ?>"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></td>
                        <td data-title="<?php _e('Status', 'wc-installments'); ?>"><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
                        <td data-title="<?php _e('Amount', 'wc-installments'); ?>"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                        <td data-title="<?php _e('Installment', 'wc-installments'); ?>">
                            <?php 
                            echo esc_html(sprintf(
                                __('Payment %d of %d', 'wc-installments'),
                                $order->get_meta('_installment_number'),
                                $order->get_meta('_total_installments')
                            )); 
                            ?>
                        </td>
                        <td data-title="<?php _e('Actions', 'wc-installments'); ?>">
                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="woocommerce-button button view"><?php _e('View', 'wc-installments'); ?></a>
                            <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="woocommerce-button button pay"><?php _e('Pay', 'wc-installments'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    // Display payment history if exists
    if (!empty($payment_logs)) {
        ?>
        <h3><?php _e('Payment History', 'wc-installments'); ?></h3>
        <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
            <thead>
                <tr>
                    <th><?php _e('Order', 'wc-installments'); ?></th>
                    <th><?php _e('Date', 'wc-installments'); ?></th>
                    <th><?php _e('Amount', 'wc-installments'); ?></th>
                    <th><?php _e('Payment', 'wc-installments'); ?></th>
                    <th><?php _e('Method', 'wc-installments'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payment_logs as $order_id => $log) : ?>
                    <tr>
                        <td data-title="<?php _e('Order', 'wc-installments'); ?>">#<?php echo esc_html($order_id); ?></td>
                        <td data-title="<?php _e('Date', 'wc-installments'); ?>"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($log['payment_date']))); ?></td>
                        <td data-title="<?php _e('Amount', 'wc-installments'); ?>"><?php echo wp_kses_post(wc_price($log['amount'])); ?></td>
                        <td data-title="<?php _e('Payment', 'wc-installments'); ?>">
                            <?php echo esc_html(sprintf(__('%d of %d', 'wc-installments'), $log['installment_number'], $log['total_installments'])); ?>
                        </td>
                        <td data-title="<?php _e('Method', 'wc-installments'); ?>"><?php echo esc_html($log['payment_method']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    ?>
</div>