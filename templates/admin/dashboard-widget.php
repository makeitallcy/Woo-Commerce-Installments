<?php
/**
 * Admin dashboard widget template
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin = WC_Installments()->admin;
$counts = $admin->get_dashboard_counts();

$installment_status = str_replace('wc-', '', wc_installments_get_status());
$recent_orders = wc_get_orders([
    'limit' => 5,
    'status' => [$installment_status],
    'meta_key' => '_installment_number',
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="wc-installments-dashboard-widget">
    <div class="summary">
        <div class="stat">
            <span class="stat-count"><?php echo esc_html($counts['active']); ?></span>
            <span class="stat-label"><?php _e('Active Plans', 'wc-installments'); ?></span>
        </div>
        <div class="stat">
            <span class="stat-count"><?php echo esc_html($counts['completed']); ?></span>
            <span class="stat-label"><?php _e('Completed Plans', 'wc-installments'); ?></span>
        </div>
        <div class="stat">
            <span class="stat-count"><?php echo esc_html($counts['total']); ?></span>
            <span class="stat-label"><?php _e('Total Plans', 'wc-installments'); ?></span>
        </div>
    </div>
    
    <?php if (!empty($recent_orders)) : ?>
        <h3><?php _e('Recent Installment Orders', 'wc-installments'); ?></h3>
        <ul class="recent-orders">
            <?php foreach ($recent_orders as $order) : 
                $customer = $order->get_user();
                $customer_name = $customer ? $customer->display_name : __('Guest', 'wc-installments');
                $installment = $order->get_meta('_installment_number');
                $total = $order->get_meta('_total_installments');
            ?>
                <li>
                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">
                        <?php echo sprintf(
                            __('#%1$s - %2$s - Payment %3$d of %4$d', 'wc-installments'),
                            $order->get_order_number(),
                            $customer_name,
                            $installment,
                            $total
                        ); ?>
                    </a>
                    <span class="amount"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="view-all">
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&post_status=' . $installment_status)); ?>">
                <?php _e('View all installment orders', 'wc-installments'); ?> →
            </a>
        </p>
    <?php else : ?>
        <p><?php _e('No active installment orders found.', 'wc-installments'); ?></p>
    <?php endif; ?>
    
    <p class="create-plan">
        <a href="<?php echo esc_url(admin_url('admin.php?page=create-installment-plan')); ?>" class="button button-primary">
            <?php _e('Create New Plan', 'wc-installments'); ?>
        </a>
    </p>
</div>

<style>
.wc-installments-dashboard-widget .summary {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}
.wc-installments-dashboard-widget .stat {
    text-align: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    flex: 1;
    margin: 0 5px;
}
.wc-installments-dashboard-widget .stat-count {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #23282d;
}
.wc-installments-dashboard-widget .stat-label {
    color: #646970;
    font-size: 13px;
}
.wc-installments-dashboard-widget .recent-orders {
    margin: 0;
}
.wc-installments-dashboard-widget .recent-orders li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}
.wc-installments-dashboard-widget .recent-orders .amount {
    font-weight: bold;
}
.wc-installments-dashboard-widget .view-all {
    text-align: right;
    margin: 10px 0;
}
.wc-installments-dashboard-widget .create-plan {
    margin-top: 15px;
}
</style>