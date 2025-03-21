<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customer completed email class
 */
class WC_Installments_Manager_Customer_Completed_Email extends WC_Email {
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'wc_installments_completed';
        $this->title = __('Installment Plan Completed', 'wc-installments');
        $this->description = __('Email sent to customers when all installments in a plan are paid.', 'wc-installments');
        $this->template_html = 'emails/customer-completed.php';
        $this->template_plain = 'emails/plain/customer-completed.php';
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
        return __('Congratulations! All installments paid - {site_title}', 'wc-installments');
    }
    
    /**
     * Get email heading
     *
     * @return string
     */
    public function get_default_heading() {
        return __('Your Installment Plan is Complete!', 'wc-installments');
    }
    
    /**
     * Trigger the sending of this email
     *
     * @param int $customer_id The customer ID
     * @param array $orders Array of WC_Order objects
     */
    public function trigger($customer_id, $orders) {
        $this->setup_locale();
        
        if (!is_array($orders) || empty($orders)) {
            return;
        }
        
        $customer = get_user_by('id', $customer_id);
        if (!$customer) {
            return;
        }
        
        $this->recipient = $customer->user_email;
        
        // Calculate total amount paid
        $total_amount = array_sum(array_map(function($order) {
            return $order->get_total();
        }, $orders));
        
        $this->placeholders['{customer_name}'] = $customer->display_name;
        $this->placeholders['{total_installments}'] = count($orders);
        $this->placeholders['{total_amount}'] = wc_price($total_amount);
        $this->placeholders['{completion_date}'] = date_i18n(get_option('date_format'));
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
                'total_installments' => $this->placeholders['{total_installments}'],
                'total_amount' => $this->placeholders['{total_amount}'],
                'completion_date' => $this->placeholders['{completion_date}'],
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
                'total_installments' => $this->placeholders['{total_installments}'],
                'total_amount' => $this->placeholders['{total_amount}'],
                'completion_date' => $this->placeholders['{completion_date}'],
                'site_title' => $this->placeholders['{site_title}'],
                'email_heading' => $this->get_heading(),
                'email' => $this,
            ],
            '',
            $this->template_base
        );
    }
}