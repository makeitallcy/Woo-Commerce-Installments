<?php
/**
 * Payments report template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get payment data for current month
$current_month = date('m');
$current_year = date('Y');

// Check for date filter
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-t');

// Get completed installment orders in the selected date range
$completed_orders = wc_get_orders([
    'status' => ['completed'],
    'meta_key' => '_installment_number',
    'date_created' => $date_from . '...' . $date_to,
    'limit' => -1
]);

// Calculate totals
$monthly_total = 0;
foreach ($completed_orders as $order) {
    $monthly_total += $order->get_total();
}

// Get payment data for past 6 months for chart
$monthly_data = [];
for ($i = 0; $i < 6; $i++) {
    $month = date('m', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $month_name = date_i18n('F', strtotime("-$i months"));
    
    $orders = wc_get_orders([
        'status' => ['completed'],
        'meta_key' => '_installment_number',
        'date_created' => $year . '-' . $month . '-01...' . $year . '-' . $month . '-31',
        'limit' => -1
    ]);
    
    $total = 0;
    foreach ($orders as $order) {
        $total += $order->get_total();
    }
    
    $monthly_data[$month_name] = $total;
}

// Reverse the array to show oldest to newest
$monthly_data = array_reverse($monthly_data);
?>

<div class="report-payments">
    <!-- Date filter -->
    <form method="get" action="" id="filter-form" class="report-filter">
        <input type="hidden" name="page" value="installment-reports">
        <input type="hidden" name="report" value="payments">
        <div class="date-selector" style="margin-bottom: 20px;">
            <label for="date_from"><?php echo esc_html__('From:', 'wc-installments'); ?></label>
            <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="date-picker">
            
            <label for="date_to"><?php echo esc_html__('To:', 'wc-installments'); ?></label>
            <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="date-picker">
            
            <button type="submit" class="button"><?php echo esc_html__('Filter', 'wc-installments'); ?></button>
        </div>
    </form>
    
    <div class="report-section" style="display: flex; gap: 20px; margin-bottom: 30px;">
        <div class="report-card">
            <h2><?php echo esc_html(wc_price($monthly_total)); ?></h2>
            <p><?php echo esc_html__('Payments This Period', 'wc-installments'); ?></p>
        </div>
        <div class="report-card">
            <h2><?php echo esc_html(count($completed_orders)); ?></h2>
            <p><?php echo esc_html__('Orders Completed This Period', 'wc-installments'); ?></p>
        </div>
        <div class="report-card">
            <h2><?php echo !empty($completed_orders) ? esc_html(wc_price($monthly_total / count($completed_orders))) : wc_price(0); ?></h2>
            <p><?php echo esc_html__('Average Payment', 'wc-installments'); ?></p>
        </div>
    </div>
    
    <div class="report-section">
        <h3><?php echo esc_html__('Monthly Payment History', 'wc-installments'); ?></h3>
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <canvas id="payment-chart" height="300"></canvas>
        </div>
        
        <script>
            var chartData = {
                labels: <?php echo json_encode(array_keys($monthly_data)); ?>,
                values: <?php echo json_encode(array_values($monthly_data)); ?>
            };
        </script>
    </div>
    
    <div class="report-section">
        <h3><?php echo esc_html__('Recent Payments', 'wc-installments'); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Date', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Order', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Customer', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Amount', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Installment', 'wc-installments'); ?></th>
                    <th><?php echo esc_html__('Payment Method', 'wc-installments'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($completed_orders)) {
                    // Sort by completion date (newest first)
                    usort($completed_orders, function($a, $b) {
                        if (!$a->get_date_completed() || !$b->get_date_completed()) {
                            return 0;
                        }
                        return $b->get_date_completed()->getTimestamp() - $a->get_date_completed()->getTimestamp();
                    });
                    
                    // Display only the 10 most recent
                    $recent_orders = array_slice($completed_orders, 0, 10);
                    
                    foreach ($recent_orders as $order) {
                        if (!$order->get_date_completed()) {
                            continue;
                        }
                        
                        $customer = $order->get_user();
                        $customer_name = $customer ? $customer->display_name : __('Guest', 'wc-installments');
                        ?>
                        <tr>
                            <td><?php echo esc_html($order->get_date_completed()->date_i18n(get_option('date_format'))); ?></td>
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
                                <?php 
                                echo esc_html(sprintf(
                                    __('%d of %d', 'wc-installments'), 
                                    $order->get_meta('_installment_number'),
                                    $order->get_meta('_total_installments')
                                )); 
                                ?>
                            </td>
                            <td><?php echo esc_html($order->get_payment_method_title()); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="6">' . esc_html__('No payments found for this period.', 'wc-installments') . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>