<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Installment plan manager
 */
class WC_Installments_Manager_Plan_Manager {
    /**
 * Send welcome email with login credentials and instructions
 * 
 * @param int $customer_id Customer ID
 * @param array $orders Array of order IDs
 * @param string $password Password (only for new customers)
 * @param array $plan_data Plan details
 * @return bool Whether the email was sent
 */
private function send_welcome_email($customer_id, $orders, $password = '', $plan_data = []) {
    // Check if welcome emails are enabled
    if (get_option('wc_installments_enable_welcome_email', 'yes') !== 'yes') {
        return false;
    }
    
    // Get customer data
    $customer = get_user_by('id', $customer_id);
    if (!$customer) {
        error_log('Failed to send welcome email: Customer not found with ID ' . $customer_id);
        return false;
    }
    
    // Calculate total amount
    $total_amount = 0;
    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $total_amount += $order->get_total();
        }
    }
    
    // Get email template
    $email_template = get_option('wc_installments_welcome_email_template', '');
    if (empty($email_template)) {
        $email_template = wc_installments_get_default_welcome_email_template();
    }
    
    // Replace placeholders
    $login_url = wp_login_url(wc_get_page_permalink('myaccount'));
    $installments_url = wc_get_account_endpoint_url('installments');
    
    $replacements = [
        '{customer_name}' => $customer->display_name,
        '{customer_email}' => $customer->user_email,
        '{password}' => $password, // Will only be included for new customers
        '{login_url}' => $login_url,
        '{installments_url}' => $installments_url,
        '{total_amount}' => wc_price($total_amount),
        '{num_installments}' => isset($plan_data['num_installments']) ? $plan_data['num_installments'] : count($orders),
        '{site_name}' => get_bloginfo('name')
    ];
    
    $message = str_replace(array_keys($replacements), array_values($replacements), $email_template);
    
    // Send email
    $subject = sprintf(__('Your Installment Plan at %s', 'wc-installments'), get_bloginfo('name'));
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    
    $result = wp_mail($customer->user_email, $subject, $message, $headers);
    
    if (WC_INSTALLMENTS_DEBUG) {
        if ($result) {
            error_log('Welcome email sent to customer #' . $customer_id);
        } else {
            error_log('Failed to send welcome email to customer #' . $customer_id);
        }
    }
    
    return $result;
}
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_wc_installments_search_customers', [$this, 'ajax_search_customers']);
        add_action('wp_ajax_wc_installments_search_products', [$this, 'ajax_search_products']);
        add_action('wp_ajax_wc_installments_test_ajax', [$this, 'ajax_test']);
        add_action('admin_post_create_installment_plan', [$this, 'handle_form_submission']);
    }
    
    /**
     * Register menu pages
     */
    public function register_menu_pages() {
        // Main menu
        add_menu_page(
            __('Installment Plans', 'wc-installments'),
            __('Installment Plans', 'wc-installments'),
            'manage_woocommerce',
            'create-installment-plan',
            [$this, 'render_plan_form'],
            'dashicons-money',
            56
        );
        
        // Create plan submenu (same as parent to avoid duplicates)
        add_submenu_page(
            'create-installment-plan',
            __('Create New Plan', 'wc-installments'),
            __('Create New Plan', 'wc-installments'),
            'manage_woocommerce',
            'create-installment-plan',
            [$this, 'render_plan_form']
        );
        
        // Settings submenu
        add_submenu_page(
            'create-installment-plan',
            __('Settings', 'wc-installments'),
            __('Settings', 'wc-installments'),
            'manage_woocommerce',
            'installment-settings',
            [WC_Installments()->settings, 'render_settings_page']
        );
        
        // Add reports submenu
        add_submenu_page(
            'create-installment-plan',
            __('Reports', 'wc-installments'),
            __('Reports', 'wc-installments'),
            'manage_woocommerce',
            'installment-reports',
            [$this, 'render_reports_page']
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_create-installment-plan') {
            return;
        }
        
        // First register the scripts
        wp_register_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], '4.0.13', true);
        wp_register_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', [], '4.0.13');
        
        wp_register_script('wc-installments-admin', WC_INSTALLMENTS_URL . 'assets/js/admin.js', ['jquery', 'select2'], WC_INSTALLMENTS_VERSION, true);
        
        // Localize before enqueuing
        wp_localize_script('wc-installments-admin', 'wc_installments_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'search_customers_nonce' => wp_create_nonce('search_customers'),
            'search_products_nonce' => wp_create_nonce('search_products'),
            'test_ajax_nonce' => wp_create_nonce('test_ajax'),
            'min_installments' => get_option('wc_installments_min_installments', 2),
            'max_installments' => get_option('wc_installments_max_installments', 12)
        ]);
        
        // Now enqueue the scripts
        wp_enqueue_style('select2');
        wp_enqueue_script('select2');
        wp_enqueue_script('wc-installments-admin');
        
        // Add inline styles
        $this->add_admin_styles();
    }
    
    /**
     * Add admin styles
     */
    private function add_admin_styles() {
        $css = '
        .form-field {
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .customer-selection-type {
            margin-bottom: 20px;
        }
        .customer-selection-type label {
            margin-right: 20px;
        }
        .new-customer-fields {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .new-customer-fields p {
            margin: 10px 0;
        }
        .new-customer-fields label {
            display: inline-block;
            width: 100px;
        }
        .new-customer-fields input,
        .new-customer-fields select {
            width: 300px;
        }
        .product-entry {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .product-select {
            flex: 2;
            min-width: 200px;
        }
        .quantity-input {
            width: 80px !important;
        }
        .discount-options {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .discount-type {
            flex: 1;
        }
        .discount-type label {
            display: block;
            margin-bottom: 10px;
        }
        .discount-amount {
            flex: 1;
            padding-top: 10px;
        }
        .total-calculation {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-top: 20px;
        }
        .total-calculation p {
            margin: 5px 0;
        }
        .password-requirements {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .password-requirements ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .password-requirements li {
            color: #666;
            margin: 3px 0;
        }
        .password-requirements li.met {
            color: #4CAF50;
        }
        .password-strength-meter {
            height: 4px;
            background: #ddd;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        .out-of-stock {
            color: #999;
            font-style: italic;
        }
        ';
        
        wp_add_inline_style('select2', $css);
    }
    
    /**
     * Test AJAX functionality
     */
    public function add_test_ajax_button() {
    
    }
    
    /**
     * Test AJAX handler
     */
    public function ajax_test() {
        check_ajax_referer('test_ajax', 'security');
        wp_send_json_success('AJAX is working properly');
    }
    
    /**
     * AJAX handler for searching customers
     */
    public function ajax_search_customers() {
        error_log('AJAX request received for customers search');
        
        if (!check_ajax_referer('search_customers', 'security', false)) {
            error_log('Nonce verification failed for customer search');
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            error_log('Insufficient permissions for customer search');
            wp_send_json_error('Permission denied');
            return;
        }
        
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        
        if (empty($term)) {
            wp_send_json(['results' => []]);
            return;
        }
        
        $args = [
            'search'         => '*' . $term . '*',
            'search_columns' => ['user_login', 'user_email', 'user_nicename', 'display_name'],
            'fields'         => ['ID', 'user_email', 'display_name'],
            'number'         => 20,
        ];
        
        // Add role filter if specified
        if (!empty($_GET['role']) && $_GET['role'] === 'customer') {
            $args['role'] = 'customer';
        }
        
        $users = get_users($args);
        
        $results = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $results[] = [
                    'id'   => $user->ID,
                    'text' => sprintf('%s (%s)', $user->user_email, $user->display_name)
                ];
            }
        }
        
        error_log('Customer search complete. Found ' . count($results) . ' results.');
        wp_send_json(['results' => $results]);
    }
    
    /**
     * AJAX handler for searching products
     */
    public function ajax_search_products() {
        error_log('AJAX request received for products search');
        
        if (!check_ajax_referer('search_products', 'security', false)) {
            error_log('Nonce verification failed for product search');
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            error_log('Insufficient permissions for product search');
            wp_send_json_error('Permission denied');
            return;
        }
        
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        
        if (empty($term)) {
            wp_send_json(['results' => []]);
            return;
        }
        
        $args = [
            'status' => 'publish',
            'limit'  => 20,
            'return' => 'objects',
            's'      => $term,
        ];
        
        $products = [];
        
        // Check if WooCommerce is active and the function exists
        if (function_exists('wc_get_products')) {
            $products = wc_get_products($args);
        } else {
            error_log('WooCommerce functions not available');
        }
        
        $results = [];
        if (!empty($products)) {
            foreach ($products as $product) {
                $stock_status = $product->get_stock_status();
                $stock_class = $stock_status === 'outofstock' ? 'out-of-stock' : '';
                $stock_label = $stock_status === 'outofstock' ? ' (Out of Stock)' : '';
                
                $results[] = [
                    'id'         => $product->get_id(),
                    'text'       => sprintf('%s ($%s)%s', $product->get_name(), $product->get_price(), $stock_label),
                    'price'      => $product->get_price(),
                    'stock'      => $stock_status,
                    'stock_class' => $stock_class
                ];
            }
        }
        
        error_log('Product search complete. Found ' . count($results) . ' results.');
        wp_send_json(['results' => $results]);
    }
    
    /**
     * Render plan creation form
     */
    public function render_plan_form() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-installments'));
        }
        
        // Check for error message in transient
        $error = get_transient('wc_installments_admin_notice');
        if ($error && $error['type'] === 'error') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error['message']) . '</p></div>';
            delete_transient('wc_installments_admin_notice');
        }
        
        // Check for customer ID in URL
        $preselected_customer = isset($_GET['customer_id']) ? absint($_GET['customer_id']) : 0;
        $customer = $preselected_customer ? get_user_by('id', $preselected_customer) : null;
        
        // Add debug tools at the top
        $this->add_test_ajax_button();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Create Installment Plan', 'wc-installments'); ?></h1>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="create-installment-form">
                <input type="hidden" name="action" value="create_installment_plan">
                <?php wp_nonce_field('create_installment_plan', 'installment_nonce'); ?>
                
                <!-- Customer Selection -->
                <div class="form-field">
                    <h3><?php echo esc_html__('Customer Information', 'wc-installments'); ?></h3>
                    <div class="customer-selection-type">
                        <label>
                            <input type="radio" name="customer_type" value="existing" <?php checked(empty($preselected_customer) || !empty($customer)); ?>> 
                            <?php echo esc_html__('Existing Customer', 'wc-installments'); ?>
                        </label>
                        <label>
                            <input type="radio" name="customer_type" value="new" <?php checked(!empty($preselected_customer) && empty($customer)); ?>> 
                            <?php echo esc_html__('New Customer', 'wc-installments'); ?>
                        </label>
                    </div>
                    
                    <div id="existing-customer-section" <?php echo empty($preselected_customer) || !empty($customer) ? '' : 'style="display: none;"'; ?>>
                        <select name="customer_id" class="existing-customer-select" style="width: 100%;">
                            <?php if ($customer) : ?>
                                <option value="<?php echo esc_attr($customer->ID); ?>" selected>
                                    <?php echo esc_html(sprintf('%s (%s)', $customer->user_email, $customer->display_name)); ?>
                                </option>
                            <?php else : ?>
                                <option value=""><?php echo esc_html__('Search for a customer...', 'wc-installments'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div id="new-customer-section" <?php echo !empty($preselected_customer) && empty($customer) ? '' : 'style="display: none;"'; ?>>
                        <div class="new-customer-fields">
                            <h4><?php echo esc_html__('Customer Information', 'wc-installments'); ?></h4>
                            <p>
                                <label><?php echo esc_html__('First Name:', 'wc-installments'); ?></label>
                                <input type="text" name="new_customer_first_name" class="new-customer-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('Last Name:', 'wc-installments'); ?></label>
                                <input type="text" name="new_customer_last_name" class="new-customer-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('Email:', 'wc-installments'); ?></label>
                                <input type="email" name="new_customer_email" class="new-customer-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('Phone:', 'wc-installments'); ?></label>
                                <input type="tel" name="new_customer_phone" class="new-customer-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('Password:', 'wc-installments'); ?></label>
                                <input type="password" name="new_customer_password" class="new-customer-input" id="new_customer_password">
                                <div class="password-strength-meter"></div>
                            </p>
                            <p>
                                <label><?php echo esc_html__('Confirm Password:', 'wc-installments'); ?></label>
                                <input type="password" name="new_customer_password_confirm" class="new-customer-input">
                            </p>
                            
                            <!-- Billing Address Fields -->
                            <h4><?php echo esc_html__('Billing Address', 'wc-installments'); ?></h4>
                            <p>
                                <label><?php echo esc_html__('Address Line 1:', 'wc-installments'); ?></label>
                                <input type="text" name="billing_address_1" class="billing-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('Address Line 2:', 'wc-installments'); ?></label>
                                <input type="text" name="billing_address_2" class="billing-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('City:', 'wc-installments'); ?></label>
                                <input type="text" name="billing_city" class="billing-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('State/Province:', 'wc-installments'); ?></label>
                                <input type="text" name="billing_state" class="billing-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('Postcode/ZIP:', 'wc-installments'); ?></label>
                                <input type="text" name="billing_postcode" class="billing-input">
                            </p>
                            <p>
                                <label><?php echo esc_html__('Country:', 'wc-installments'); ?></label>
                                <select name="billing_country" class="billing-input">
                                    <?php
                                    // Get list of countries
                                    if (class_exists('WC_Countries')) {
                                        $countries_obj = new WC_Countries();
                                        $countries = $countries_obj->get_allowed_countries();
                                        
                                        foreach ($countries as $code => $name) {
                                            echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                                        }
                                    } else {
                                        echo '<option value="US">United States</option>';
                                    }
                                    ?>
                                </select>
                            </p>
                            
                            <div class="password-requirements">
                                <p><?php echo esc_html__('Password must contain:', 'wc-installments'); ?></p>
                                <ul>
                                    <li id="length"><?php echo esc_html__('At least 8 characters', 'wc-installments'); ?></li>
                                    <li id="uppercase"><?php echo esc_html__('One uppercase letter', 'wc-installments'); ?></li>
                                    <li id="lowercase"><?php echo esc_html__('One lowercase letter', 'wc-installments'); ?></li>
                                    <li id="number"><?php echo esc_html__('One number', 'wc-installments'); ?></li>
                                    <li id="special"><?php echo esc_html__('One special character', 'wc-installments'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product Selection -->
                <div class="form-field">
                    <h3><?php echo esc_html__('Select Products', 'wc-installments'); ?></h3>
                    <div id="product-list">
                        <div class="product-entry">
                            <select name="products[]" required class="product-select">
                                <option value=""><?php echo esc_html__('Search for a product...', 'wc-installments'); ?></option>
                            </select>
                            <input type="number" name="quantities[]" value="1" min="1" required class="quantity-input">
                            <button type="button" class="button remove-product" style="display:none;"><?php echo esc_html__('Remove', 'wc-installments'); ?></button>
                        </div>
                    </div>
                    <button type="button" class="button" id="add-product"><?php echo esc_html__('Add Another Product', 'wc-installments'); ?></button>
                </div>

                <!-- Discount Section -->
                <div class="form-field">
                    <h3><?php echo esc_html__('Discount', 'wc-installments'); ?></h3>
                    <div class="discount-options">
                        <div class="discount-type">
                            <label>
                                <input type="radio" name="discount_type" value="none" checked> 
                                <?php echo esc_html__('No Discount', 'wc-installments'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="discount_type" value="percentage"> 
                                <?php echo esc_html__('Percentage Discount', 'wc-installments'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="discount_type" value="fixed"> 
                                <?php echo esc_html__('Fixed Amount Discount', 'wc-installments'); ?>
                            </label>
                        </div>
                        <div class="discount-amount" style="display: none;">
                            <label for="discount_value"><?php echo esc_html__('Discount Amount:', 'wc-installments'); ?></label>
                            <input type="number" id="discount_value" name="discount_value" step="0.01" min="0">
                            <span class="discount-symbol">$</span>
                        </div>
                    </div>
                    <div class="total-calculation">
                        <p><?php echo esc_html__('Subtotal:', 'wc-installments'); ?> <span id="subtotal">$0.00</span></p>
                        <p><?php echo esc_html__('Discount:', 'wc-installments'); ?> <span id="discount-amount">$0.00</span></p>
                        <p><strong><?php echo esc_html__('Total:', 'wc-installments'); ?> <span id="final-total">$0.00</span></strong></p>
                    </div>
                </div>

                <!-- Installment Details -->
                <div class="form-field">
                    <h3><?php echo esc_html__('Installment Details', 'wc-installments'); ?></h3>
                    <label>
                        <?php echo esc_html__('Number of Installments:', 'wc-installments'); ?>
                        <input type="number" name="num_installments" 
                               min="<?php echo esc_attr(get_option('wc_installments_min_installments', '2')); ?>" 
                               max="<?php echo esc_attr(get_option('wc_installments_max_installments', '12')); ?>" 
                               required value="2">
                    </label>
                    <p><?php echo esc_html__('Amount per installment:', 'wc-installments'); ?> <span id="installment-amount">$0.00</span></p>
                </div>

                <input type="submit" class="button button-primary" value="<?php echo esc_attr__('Create Installment Plan', 'wc-installments'); ?>">
            </form>
        </div>
        <?php
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-installments'));
        }
        
        // Get report type from URL parameters
        $report_type = isset($_GET['report']) ? sanitize_text_field($_GET['report']) : 'overview';
        
        // Load appropriate template
        include WC_INSTALLMENTS_PATH . 'templates/admin/reports.php';
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        // Check nonce for security
        if (!isset($_POST['installment_nonce']) || !wp_verify_nonce($_POST['installment_nonce'], 'create_installment_plan')) {
            wp_die('Security check failed', 'Error', ['response' => 403, 'back_link' => true]);
        }
        
        try {
            // Get form data
            $form_data = $this->get_form_data();
            if (!$form_data) {
                throw new Exception('Invalid form data submitted.');
            }
            
            $customer_id = $this->process_customer_data();
            if (!$customer_id) {
                throw new Exception('No valid customer ID.');
            }
            
            // Process products and create orders
            $created_orders = $this->create_installment_orders($customer_id, $form_data);
            
            // Send welcome email with login details
            $password = isset($_POST['customer_type']) && $_POST['customer_type'] === 'new' ? $_POST['new_customer_password'] : '';
            $this->send_welcome_email($customer_id, $created_orders, $password, $form_data);
            
            // Add admin notice for success
            set_transient('wc_installments_admin_notice', [
                'type' => 'success',
                'message' => __('Installment plan created successfully.', 'wc-installments')
            ], 60);
            // Redirect on success
            wp_redirect(admin_url('edit.php?post_type=shop_order'));
            exit;
            
        } catch (Exception $e) {
            if (defined('WC_INSTALLMENTS_DEBUG') && WC_INSTALLMENTS_DEBUG) {
                error_log('Error in installment creation: ' . $e->getMessage());
            }
            
            // Set error message
            set_transient('wc_installments_admin_notice', [
                'type' => 'error',
                'message' => $e->getMessage()
            ], 60);
            
            wp_redirect(add_query_arg('error', '1', admin_url('admin.php?page=create-installment-plan')));
            exit;
        }
    }
    
    /**
     * Process and validate form data
     */
    private function get_form_data() {
        if (!isset($_POST['products']) || !isset($_POST['quantities']) || !isset($_POST['num_installments'])) {
            return false;
        }
        
        // Validate product IDs exist and are valid products
        $products = array_map('absint', $_POST['products']);
        foreach ($products as $product_id) {
            if (!wc_get_product($product_id)) {
                return false;
            }
        }
        
        // Validate quantities are positive numbers
        $quantities = array_map('absint', $_POST['quantities']);
        foreach ($quantities as $quantity) {
            if ($quantity <= 0) {
                return false;
            }
        }
        
        // Validate number of installments
        $num_installments = absint($_POST['num_installments']);
        $min_installments = get_option('wc_installments_min_installments', 2);
        $max_installments = get_option('wc_installments_max_installments', 12);
        
        if ($num_installments < $min_installments || $num_installments > $max_installments) {
            return false;
        }
        
        // Validate discount type
        $discount_type = isset($_POST['discount_type']) ? sanitize_text_field($_POST['discount_type']) : 'none';
        if (!in_array($discount_type, ['none', 'percentage', 'fixed'])) {
            $discount_type = 'none';
        }
        
        // Validate discount value
        $discount_value = isset($_POST['discount_value']) ? floatval($_POST['discount_value']) : 0;
        if ($discount_value < 0) {
            $discount_value = 0;
        }

        return [
            'products' => $products,
            'quantities' => $quantities,
            'num_installments' => $num_installments,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value
        ];
    }
    
    /**
     * Process customer data (create new or use existing)
     */
    private function process_customer_data() {
        if (isset($_POST['customer_type']) && $_POST['customer_type'] === 'new') {
            // Create new customer
            $user_data = [
                'user_login' => sanitize_email($_POST['new_customer_email']),
                'user_email' => sanitize_email($_POST['new_customer_email']),
                'user_pass' => $_POST['new_customer_password'],
                'first_name' => sanitize_text_field($_POST['new_customer_first_name']),
                'last_name' => sanitize_text_field($_POST['new_customer_last_name']),
                'role' => 'customer'
            ];
            
            $customer_id = wp_insert_user($user_data);
            if (is_wp_error($customer_id)) {
                throw new Exception($customer_id->get_error_message());
            }
            
            // Add meta data
            $this->save_customer_meta_data($customer_id);
            
            return $customer_id;
        } else {
            return absint($_POST['customer_id']);
        }
    }
    
    /**
     * Save customer meta data for new customers
     */
    private function save_customer_meta_data($customer_id) {
        // Add phone number if provided
        if (!empty($_POST['new_customer_phone'])) {
            update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['new_customer_phone']));
        }
        
        // Add billing address information
        $billing_fields = [
            'billing_first_name' => sanitize_text_field($_POST['new_customer_first_name']),
            'billing_last_name' => sanitize_text_field($_POST['new_customer_last_name']),
            'billing_address_1' => isset($_POST['billing_address_1']) ? sanitize_text_field($_POST['billing_address_1']) : '',
            'billing_address_2' => isset($_POST['billing_address_2']) ? sanitize_text_field($_POST['billing_address_2']) : '',
            'billing_city' => isset($_POST['billing_city']) ? sanitize_text_field($_POST['billing_city']) : '',
            'billing_state' => isset($_POST['billing_state']) ? sanitize_text_field($_POST['billing_state']) : '',
            'billing_postcode' => isset($_POST['billing_postcode']) ? sanitize_text_field($_POST['billing_postcode']) : '',
            'billing_country' => isset($_POST['billing_country']) ? sanitize_text_field($_POST['billing_country']) : '',
            'billing_email' => sanitize_email($_POST['new_customer_email']),
            'billing_phone' => isset($_POST['new_customer_phone']) ? sanitize_text_field($_POST['new_customer_phone']) : '',
        ];
        
        // Save billing fields to user meta
        foreach ($billing_fields as $key => $value) {
            if (!empty($value)) {
                update_user_meta($customer_id, $key, $value);
            }
        }
        
        return true;
    }
    
    /**
     * Create installment orders
     */
    /**
     * Create installment orders
     */
    private function create_installment_orders($customer_id, $form_data) {
        // Check if WooCommerce is available
        if (!function_exists('wc_get_product') || !function_exists('wc_create_order')) {
            throw new Exception('WooCommerce functions are not available.');
        }
        
        // Calculate total amount
        $subtotal = 0;
        foreach ($form_data['products'] as $key => $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) {
                throw new Exception('Invalid product ID: ' . $product_id);
            }
            
            // Check stock status
            if (!$product->is_in_stock()) {
                throw new Exception('Product "' . $product->get_name() . '" is out of stock.');
            }
            
            $subtotal += $product->get_price() * $form_data['quantities'][$key];
        }

        // Apply discount
        $discount_amount = 0;
        if ($form_data['discount_type'] === 'percentage' && $form_data['discount_value'] > 0) {
            $discount_amount = $subtotal * ($form_data['discount_value'] / 100);
        } elseif ($form_data['discount_type'] === 'fixed' && $form_data['discount_value'] > 0) {
            $discount_amount = $form_data['discount_value'];
        }

        $total = $subtotal - $discount_amount;
        $amount_per_installment = round($total / $form_data['num_installments'], 2);
        $final_installment = round($total - ($amount_per_installment * ($form_data['num_installments'] - 1)), 2);

        // Create installment orders
        $created_orders = array();
        $installment_status = str_replace('wc-', '', get_option('wc_installments_default_status', 'wc-pending'));
        $plan_id = uniqid('plan_');

        for ($i = 1; $i <= $form_data['num_installments']; $i++) {
            $order = wc_create_order(['customer_id' => $customer_id]);

            if ($i === 1) {
                foreach ($form_data['products'] as $key => $product_id) {
                    $product = wc_get_product($product_id);
                    $item = new WC_Order_Item_Product();
                    $item->set_props([
                        'product'  => $product,
                        'quantity' => $form_data['quantities'][$key],
                        'total'    => 0
                    ]);
                    $order->add_item($item);
                }
            } else {
                foreach ($form_data['products'] as $key => $product_id) {
                    $product = wc_get_product($product_id);
                    $fee = new WC_Order_Item_Fee();
                    $fee->set_name(sprintf(
                        __('Payment for: %s (Quantity: %d)', 'wc-installments'), 
                        $product->get_name(), 
                        $form_data['quantities'][$key]
                    ));
                    $fee->set_amount(0);
                    $fee->set_total(0);
                    $order->add_item($fee);
                }
            }

            // Add installment fee
            $amount = ($i === $form_data['num_installments']) ? $final_installment : $amount_per_installment;
            $fee = new WC_Order_Item_Fee();
            $fee->set_name(sprintf(__('Installment Payment %d of %d', 'wc-installments'), $i, $form_data['num_installments']));
            $fee->set_amount($amount);
            $fee->set_total($amount);
            $order->add_item($fee);

            // Set order meta
            $order->update_meta_data('_installment_number', $i);
            $order->update_meta_data('_total_installments', $form_data['num_installments']);
            $order->update_meta_data('_installment_plan_id', $plan_id);
            
            if ($discount_amount > 0) {
                $order->update_meta_data('_installment_discount_type', $form_data['discount_type']);
                $order->update_meta_data('_installment_discount_value', $form_data['discount_value']);
                $order->update_meta_data('_installment_discount_amount', $discount_amount);
            }

            $order->set_status($installment_status);
            $order->calculate_totals();
            $order->save();

            $created_orders[] = $order->get_id();
        }

        // Apply WP Fusion tag if available
        $this->apply_wp_fusion_tag($customer_id);
        
        return $created_orders;
    }
    
    /**
     * Apply WP Fusion tag to customer
     */
    private function apply_wp_fusion_tag($customer_id) {
        if (function_exists('wp_fusion') && method_exists(wp_fusion()->user, 'apply_tags')) {
            try {
                $tag_id = absint(get_option('wc_installments_wpf_tag', 537));
                $tag_timing = get_option('wc_installments_tag_timing', 'creation');
                
                // Only apply tag immediately if timing is set to creation
                if ($tag_timing === 'creation') {
                    $tags = [$tag_id];
                    wp_fusion()->user->apply_tags($tags, $customer_id);
                    
                    if (defined('WC_INSTALLMENTS_DEBUG') && WC_INSTALLMENTS_DEBUG) {
                        error_log('Applied WP Fusion tag ID: ' . $tag_id . ' to customer ' . $customer_id);
                    }
                }
                
                return true;
            } catch (Exception $e) {
                error_log('Error applying WP Fusion tag: ' . $e->getMessage());
                // Continue processing - don't let WP Fusion errors stop the installment creation
            }
        }
        
        return false;
    }
}
