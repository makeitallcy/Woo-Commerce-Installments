<?php
/**
 * Customers report template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get customers with installment payment logs
$customers = get_users([
    'meta_key' => '_installment_payment_logs',
    'number' => 20,
    'orderby' => 'ID',
    'order' => 'DESC'
]);

// Check for search term
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
if (!empty($search_term)) {
    $customers = get_users([
        'meta_key' => '_installment_payment_logs',
        'number' => 20,
        'search' => '*' . $search_term . '*',
        'search_columns' => ['user_login', 'user_email', 'display_name']
    ]);
}
?>

<div class="report-customers">
    <div class="tablenav top">
        <div class="alignleft actions">
            <form action="" method="get">
                <input type="hidden" name="page" value="installment-reports">
                <input type="hidden" name="report" value="customers">
                <label for="customer-search" class="screen-reader-text"><?php echo esc_html__('Search Customers', 'wc-installments'); ?></label>
                <input type="search" id="customer-search" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" placeholder="<?php echo esc_attr__('Search customers...', 'wc-installments'); ?>">
                <input type="submit" class="button" value="<?php echo esc_attr__('Search', 'wc-installments'); ?>">
            </form>
        </div>
        <br class="clear">
    </div>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Customer', 'wc-installments'); ?></th>
                <th><?php echo esc_html__('Email', 'wc-installments'); ?></th>
                <th><?php echo esc_html__('Active Plans', 'wc-installments'); ?></th>
                <th><?php echo esc_html__('Total Paid', 'wc-installments'); ?></th>
                <th><?php echo esc_html__('Outstanding', 'wc-installments'); ?></th>
                <th><?php echo esc_html__('Actions', 'wc-installments'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($customers)) {
                $customer_manager = new WC_Installments_Manager_Customer_Manager();
                
                foreach ($customers as $customer) {
                    // Get customer summary
                    $summary = $customer_manager->get_customer_installment_summary($customer->ID);
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $customer->ID)); ?>">
                                <?php echo esc_html($customer->display_name); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($customer->user_email); ?></td>
                        <td><?php echo esc_html($summary['active_orders']); ?></td>
                        <td><?php echo esc_html(wc_price($summary['completed_total'])); ?></td>
                        <td><?php echo esc_html(wc_price($summary['total_due'])); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&_customer_user=' . $customer->ID)); ?>" class="button button-small"><?php echo esc_html__('View Orders', 'wc-installments'); ?></a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=create-installment-plan&customer_id=' . $customer->ID)); ?>" class="button button-small"><?php echo esc_html__('Create Plan', 'wc-installments'); ?></a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="6">' . esc_html__('No customers with installment plans found.', 'wc-installments') . '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>