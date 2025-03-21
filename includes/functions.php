<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get default email template for customer notifications
 */
function wc_installments_get_default_email_template() {
    return '<h2>Your Installment Plan is Complete!</h2>
    <p>Dear {customer_name},</p>
    <p>Thank you for completing all payments for your installment plan.</p>';
}

/**
 * Get default admin email template
 */
function wc_installments_get_default_admin_email_template() {
    return '<h2>Installment Plan Completed</h2>
    <p>Customer {customer_name} (ID: {customer_id}) has completed all installments.</p>';
}

/**
 * Get default reminder email template
 */
function wc_installments_get_default_reminder_template() {
    return '<h2>Payment Reminder</h2>
    <p>Dear {customer_name},</p>
    <p>This is a reminder that your payment is due soon.</p>';
}

/**
 * Get default welcome email template
 */
function wc_installments_get_default_welcome_email_template() {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 15px; text-align: center; }
            .content { padding: 20px; }
            .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
            .button { display: inline-block; padding: 10px 15px; background-color: #2271b1; color: #ffffff; text-decoration: none; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Your Installment Plan Has Been Created</h2>
            </div>
            <div class="content">
                <p>Dear {customer_name},</p>
                
                <p>Thank you for choosing our installment payment option. Your plan has been successfully created and is now ready for you to start making payments.</p>
                
                <h3>Your Account Details:</h3>
                <p>Username: {customer_email}<br>
                <?php if (strpos('{password}', '{password}') === false): // Only show if password placeholder is replaced ?>
                Password: {password}<br>
                <?php endif; ?>
                </p>
                
                <p>You can log in and manage your installments by visiting:<br>
                <a href="{login_url}" class="button">Login to Your Account</a></p>
                
                <h3>Installment Plan Details:</h3>
                <p>Total Amount: {total_amount}<br>
                Number of Installments: {num_installments}</p>
                
                <p>Once logged in, you can view and pay your installments by visiting:<br>
                <a href="{installments_url}">View Your Installments</a></p>
                
                <p>If you have any questions, please don't hesitate to contact us.</p>
                
                <p>Best regards,<br>
                {site_name} Team</p>
            </div>
            <div class="footer">
                This is an automated message. Please do not reply to this email.
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Debug log function
 */
function wc_installments_log($message) {
    if (!WC_INSTALLMENTS_DEBUG) {
        return;
    }
    
    if (is_array($message) || is_object($message)) {
        error_log(print_r($message, true));
    } else {
        error_log($message);
    }
}