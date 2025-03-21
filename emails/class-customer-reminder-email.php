<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customer reminder email class
 */
class WC_Installments_Manager_Customer_Reminder_Email extends WC_Email {
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'wc_installments_reminder';
        $this->title = __('Installment Payment Reminder', 'wc-installments');
        $this->description = __('Email sent to customers before an installment payment is due.', 'wc-installments');
        $this->template_html = 'emails/customer-reminder.php';
        $this->template_plain = 'emails/plain/customer-reminder.php';
        $this->template_base = WC_INSTALLMENTS_PATH . 'templates/';
        $this->customer_email = true;
        
        // Call parent constructor
        parent::__construct();
    }
    
    /**
     * Get email subject
     *
     * @return string
     */
    public function get_default_subject() {
        return __('Payment Reminder: Installment Payment Due Soon - {site_title}', 'wc-installments');
    }
    
    /**
     * Get email heading
     *
     * @return string
     */
    public function get_default_heading() {
        return __('Installment Payment Reminder', 'wc-installments');
    }
    
    /**
     * Trigger the sending of this email
     *
     * @param WC_Order $order The order object
     */
    public function trigger($order) {
        $this->setup_locale();
        
        if (!$order) {
            return;
        }
        
        $customer_id = $order->get_customer_id();
        $customer = get_user_by('id', $customer_id);
        if (!$customer) {
            return;
        }
        
        $this->recipient = $customer->user_email;
        
        $reminder_days = absint(get_option('wc_installments_reminder_days', 3));
        $installment_number = $order->get_meta('_installment_number');
        $total_installments = $order->get_meta('_total_installments');
        
        // Calculate due date
        $order_date = $order->get_date_created();
        $payment_due_date = new DateTime($order_date->date('Y-m-d'));
        $payment_due_date->modify('+30 days');
        
        $this->placeholders['{customer_name}'] = $customer->display_name;
        $this->placeholders['{order_number}'] = $order->get_order_number();
        $this->placeholders['{amount_due}'] = $order->get_formatted_order_total();
        $this->placeholders['{due_date}'] = date_i18n(get_option('date_format'), $payment_due_date->getTimestamp());
        $this->placeholders['{payment_number}'] = $installment_number;
        $this->placeholders['{total_payments}'] = $total_installments;
        $this->placeholders['{payment_link}'] = $order->get_checkout_payment_url();
        $this->placeholders['{site_title}'] = get_bloginfo('name');
        
        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }
        
        $this->restore_locale();
    }
    
    /**
     * Get content html
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            [
                'customer_name' => $this->placeholders['{customer_name}'],
                'order_number' => $this->placeholders['{order_number}'],
                'amount_due' => $this->placeholders['{amount_due}'],
                'due_date' => $this->placeholders['{due_date}'],
                'payment_number' => $this->placeholders['{payment_number}'],
                'total_payments' => $this->placeholders['{total_payments}'],
                'payment_link' => $this->placeholders['{payment_link}'],
                'site_title' => $this->placeholders['{site_title}'],
                'email_heading' => $this->get_heading(),
                'email' => $this,
            ],
            '',
            $this->template_base
        );
    }
    
    /**
     * Get content plain
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'customer_name' => $this->placeholders['{customer_name}'],
                'order_number' => $this->placeholders['{order_number}'],
                'amount_due' => $this->placeholders['{amount_due}'],
                'due_date' => $this->placeholders['{due_date}'],
                'payment_number' => $this->placeholders['{payment_number}'],
                'total_payments' => $this->placeholders['{total_payments}'],
                'payment_link' => $this->placeholders['{payment_link}'],
                'site_title' => $this->placeholders['{site_title}'],
                'email_heading' => $this->get_heading(),
                'email' => $this,
            ],
            '',
            $this->template_base
        );
    }
}