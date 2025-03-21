<?php
/**
 * General settings tab template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Debug Mode', 'wc-installments'); ?></th>
        <td>
            <label>
                <input type="checkbox" name="wc_installments_debug_mode" value="yes" <?php checked('yes', get_option('wc_installments_debug_mode', 'no')); ?> />
                <?php echo esc_html__('Enable debug logging (only use in development)', 'wc-installments'); ?>
            </label>
            <p class="description"><?php echo esc_html__('When enabled, debug information will be logged to the WordPress debug.log file.', 'wc-installments'); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Default Installment Status', 'wc-installments'); ?></th>
        <td>
            <select name="wc_installments_default_status" style="width: 250px;">
                <?php 
                $statuses = wc_get_order_statuses();
                $current = get_option('wc_installments_default_status', 'wc-pending');
                
                foreach ($statuses as $status => $label) {
                    echo '<option value="' . esc_attr($status) . '" ' . selected($current, $status, false) . '>' . esc_html($label) . '</option>';
                }
                ?>
            </select>
            <p class="description"><?php echo esc_html__('Select the default order status for installment orders', 'wc-installments'); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Installment Options', 'wc-installments'); ?></th>
        <td>
            <label>
                <input type="number" name="wc_installments_min_installments" 
                       value="<?php echo esc_attr(get_option('wc_installments_min_installments', '2')); ?>" 
                       min="2" max="6" step="1" style="width: 60px;">
                <?php echo esc_html__('Minimum installments', 'wc-installments'); ?>
            </label>
            <br><br>
            <label>
                <input type="number" name="wc_installments_max_installments" 
                       value="<?php echo esc_attr(get_option('wc_installments_max_installments', '12')); ?>" 
                       min="2" max="24" step="1" style="width: 60px;">
                <?php echo esc_html__('Maximum installments', 'wc-installments'); ?>
            </label>
            <p class="description"><?php echo esc_html__('Set the allowed range for number of installments.', 'wc-installments'); ?></p>
        </td>
    </tr>
</table>