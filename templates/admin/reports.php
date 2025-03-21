<?php
/**
 * Reports page template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap woocommerce">
    <h1><?php echo esc_html__('Installment Reports', 'wc-installments'); ?></h1>
    
    <!-- Export button -->
    <div class="alignright" style="margin-bottom: 10px;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="export-form">
            <input type="hidden" name="action" value="export_installments_csv">
            <?php wp_nonce_field('export_installments_csv', 'export_nonce'); ?>
            <button type="button" class="button" id="export-csv"><?php echo esc_html__('Export to CSV', 'wc-installments'); ?></button>
        </form>
    </div>
    
    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=installment-reports&report=overview')); ?>" class="nav-tab <?php echo $report_type === 'overview' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Overview', 'wc-installments'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=installment-reports&report=customers')); ?>" class="nav-tab <?php echo $report_type === 'customers' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Customers', 'wc-installments'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=installment-reports&report=payments')); ?>" class="nav-tab <?php echo $report_type === 'payments' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Payments', 'wc-installments'); ?></a>
    </nav>
    
    <div class="report-container" style="margin-top: 20px;">
        <?php
        switch ($report_type) {
            case 'customers':
                $this->render_customers_report();
                break;
            case 'payments':
                $this->render_payments_report();
                break;
            default:
                $this->render_overview_report();
                break;
        }
        ?>
    </div>
</div>