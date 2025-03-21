<?php
/**
 * Overview report template
 */

if (!defined('ABSPATH')) {
    exit;
}

$data = $this->get_overview_data();
?>

<div class="report-overview">
    <div class="report-section" style="display: flex; gap: 20px; margin-bottom: 30px;">
        <div class="report-card">
            <h2><?php echo esc_html($data['active_orders']); ?></h2>
            <p><?php echo esc_html__('Active Installment Orders', 'wc-installments'); ?></p>
        </div>
        <div class="report-card">
            <h2><?php echo esc_html($data['completed_orders']); ?></h2>
            <p><?php echo esc_html__('Completed Installment Orders', 'wc-installments'); ?></p>
        </div>
        <div class="report-card">
            <h2><?php echo esc_html($data['total_customers']); ?></h2>
            <p><?php echo esc_html__('Total Customers', 'wc-installments'); ?></p>
        </div>
    </div>
    
    <div class="report-section" style="display: flex; gap: 20px; margin-bottom: 30px;">
        <div class="report-card">
            <h2><?php echo esc_html(wc_price($data['active_total'])); ?></h2>
            <p><?php echo esc_html__('Outstanding Installment Amount', 'wc-installments'); ?></p>
        </div>
        <div class="report-card">
            <h2><?php echo esc_html(wc_price($data['completed_total'])); ?></h2>
            <p><?php echo esc_html__('Collected Installment Amount', 'wc-installments'); ?></p>
        </div>
        <div class="report-card">
            <h2><?php echo esc_html(wc_price($data['active_total'] + $data['completed_total'])); ?></h2>
            <p><?php echo esc_html__('Total Installment Value', 'wc-installments'); ?></p>
        </div>
    </div>
    
    <div class="report-section">
        <h3><?php echo esc_html__('Recent Activity', 'wc-installments'); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Date', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Order', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Customer', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Amount', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Status', 'wc-installments'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($data['recent_orders'])) {
                    foreach ($data['recent_orders'] as $order) {
                        $customer = $order->get_user();
                        $customer_name = $customer ? $customer->display_name : __('Guest', 'wc-installments');
                        ?>
                        <tr>
                            <td><?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format'))); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">
                                    #<?php echo esc_html($order->get_order_number()); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($customer) : ?>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $customer->ID)); ?>">
                                        <?php echo esc_html($customer_name); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html($customer_name); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                            <td>
                                <span class="order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                </span>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="5">' . esc_html__('No recent activity found.', 'wc-installments') . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>