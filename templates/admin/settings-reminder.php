<?php
/**
 * Reminder settings tab template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Enable Payment Reminders', 'wc-installments'); ?></th>
        <td>
            <label>
                <input type="checkbox" name="wc_installments_enable_reminders" value="yes" <?php checked('yes', get_option('wc_installments_enable_reminders', 'yes')); ?> />
                <?php echo esc_html__('Send reminder emails for upcoming installment payments', 'wc-installments'); ?>
            </label>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Days Before Payment', 'wc-installments'); ?></th>
        <td>
            <input type="number" name="wc_installments_reminder_days" value="<?php echo esc_attr(get_option('wc_installments_reminder_days', '3')); ?>" min="1" max="10" />
            <p class="description"><?php echo esc_html__('Send reminder this many days before payment is due', 'wc-installments'); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Reminder Email Template', 'wc-installments'); ?></th>
        <td>
            <p class="description"><?php echo esc_html__('HTML template for payment reminder emails. Available placeholders: {customer_name}, {order_number}, {amount_due}, {due_date}, {payment_number}, {total_payments}, {payment_link}', 'wc-installments'); ?></p>
            <?php
            $editor_settings = [
                'textarea_name' => 'wc_installments_reminder_template',
                'editor_height' => 300,
                'media_buttons' => false,
            ];
            wp_editor(get_option('wc_installments_reminder_template', wc_installments_get_default_reminder_template()), 'wc_installments_reminder_email', $editor_settings);
            ?>
            <div style="margin-top: 10px;">
                <button type="button" class="button" id="reset-reminder-template"><?php echo esc_html__('Reset to Default Template', 'wc-installments'); ?></button>
            </div>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo esc_html__('Test Reminder Email', 'wc-installments'); ?></th>
        <td>
            <p>
                <input type="email" id="test-email-address" placeholder="<?php echo esc_attr__('Enter email address', 'wc-installments'); ?>" style="width: 250px;">
                <button type="button" class="button" id="test-reminder-email"><?php echo esc_html__('Send Test Email', 'wc-installments'); ?></button>
            </p>
            <p class="description"><?php echo esc_html__('Send a test reminder email to verify your template', 'wc-installments'); ?></p>
            <div id="test-email-result"></div>
        </td>
    </tr>
</table>

<script>
jQuery(document).ready(function($) {
    // Reset template button
    $('#reset-reminder-template').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php echo esc_js(__('Are you sure you want to reset to the default template? This will overwrite any customizations.', 'wc-installments')); ?>')) {
            var defaultTemplate = <?php echo json_encode(wc_installments_get_default_reminder_template()); ?>;
            if (typeof tinymce !== 'undefined' && tinymce.get('wc_installments_reminder_email')) {
                tinymce.get('wc_installments_reminder_email').setContent(defaultTemplate);
            } else {
                $('#wc_installments_reminder_email').val(defaultTemplate);
            }
        }
    });
    
    // Test email functionality
    $('#test-reminder-email').on('click', function(e) {
        e.preventDefault();
        var email = $('#test-email-address').val();
        
        if (!email) {
            alert('<?php echo esc_js(__('Please enter an email address', 'wc-installments')); ?>');
            return;
        }
        
        // Get template content from editor
        var template = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('wc_installments_reminder_email')) {
            template = tinymce.get('wc_installments_reminder_email').getContent();
        } else {
            template = $('#wc_installments_reminder_email').val();
        }
        
        // Show loading
        $('#test-email-result').html('<p><?php echo esc_js(__('Sending test email...', 'wc-installments')); ?></p>');
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_installment_reminder',
                security: '<?php echo wp_create_nonce('test_reminder_email'); ?>',
                email: email,
                template: template
            },
            success: function(response) {
                if (response.success) {
                    $('#test-email-result').html('<p style="color: green;">' + response.data + '</p>');
                } else {
                    $('#test-email-result').html('<p style="color: red;">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#test-email-result').html('<p style="color: red;"><?php echo esc_js(__('An error occurred while sending the test email', 'wc-installments')); ?></p>');
            }
        });
    });
});
</script>