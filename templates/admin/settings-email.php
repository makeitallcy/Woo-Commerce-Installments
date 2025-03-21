<?php
/**
 * Email settings tab template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Customer Email Notifications', 'wc-installments'); ?></th>
        <td>
            <label>
                <input type="checkbox" name="wc_installments_enable_emails" value="yes" <?php checked('yes', get_option('wc_installments_enable_emails', 'yes')); ?> />
                <?php echo esc_html__('Send email notifications when installment plan is completed', 'wc-installments'); ?>
            </label>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Admin Notifications', 'wc-installments'); ?></th>
        <td>
            <label>
                <input type="checkbox" name="wc_installments_notify_admin" value="yes" <?php checked('yes', get_option('wc_installments_notify_admin', 'yes')); ?> />
                <?php echo esc_html__('Send email notifications to admin when installment plan is completed', 'wc-installments'); ?>
            </label>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Customer Email Template', 'wc-installments'); ?></th>
        <td>
            <p class="description"><?php echo esc_html__('HTML template for customer completion emails. Use these placeholders: {customer_name}, {total_installments}, {total_amount}, {completion_date}, {site_name}', 'wc-installments'); ?></p>
            <?php
            $editor_settings = [
                'textarea_name' => 'wc_installments_email_template',
                'editor_height' => 300,
                'media_buttons' => false,
            ];
            wp_editor(get_option('wc_installments_email_template', wc_installments_get_default_email_template()), 'wc_installments_customer_email', $editor_settings);
            ?>
            <div style="margin-top: 10px;">
                <button type="button" class="button" id="reset-customer-template"><?php echo esc_html__('Reset to Default Template', 'wc-installments'); ?></button>
            </div>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Admin Email Template', 'wc-installments'); ?></th>
        <td>
            <p class="description"><?php echo esc_html__('HTML template for admin completion emails. Use placeholders as above plus {customer_id}, {order_ids}', 'wc-installments'); ?></p>
            <?php
            $editor_settings = [
                'textarea_name' => 'wc_installments_admin_email_template',
                'editor_height' => 300,
                'media_buttons' => false,
            ];
            wp_editor(get_option('wc_installments_admin_email_template', wc_installments_get_default_admin_email_template()), 'wc_installments_admin_email', $editor_settings);
            ?>
            <div style="margin-top: 10px;">
                <button type="button" class="button" id="reset-admin-template"><?php echo esc_html__('Reset to Default Template', 'wc-installments'); ?></button>
            </div>
        </td>
    </tr>
</table>

<script>
jQuery(document).ready(function($) {
    $('#reset-customer-template').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php echo esc_js(__('Are you sure you want to reset to the default template? This will overwrite any customizations.', 'wc-installments')); ?>')) {
            var defaultTemplate = <?php echo json_encode(wc_installments_get_default_email_template()); ?>;
            if (typeof tinymce !== 'undefined' && tinymce.get('wc_installments_customer_email')) {
                tinymce.get('wc_installments_customer_email').setContent(defaultTemplate);
            } else {
                $('#wc_installments_customer_email').val(defaultTemplate);
            }
        }
    });
    
    $('#reset-admin-template').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php echo esc_js(__('Are you sure you want to reset to the default template? This will overwrite any customizations.', 'wc-installments')); ?>')) {
            var defaultTemplate = <?php echo json_encode(wc_installments_get_default_admin_email_template()); ?>;
            if (typeof tinymce !== 'undefined' && tinymce.get('wc_installments_admin_email')) {
                tinymce.get('wc_installments_admin_email').setContent(defaultTemplate);
            } else {
                $('#wc_installments_admin_email').val(defaultTemplate);
            }
        }
    });
});
</script>