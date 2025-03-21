<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings page handler
 */
class WC_Installments_Manager_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_filter("plugin_action_links_" . plugin_basename(WC_INSTALLMENTS_FILE), [$this, 'add_settings_link']);
        add_action('wp_ajax_wc_installments_smtp_test', [$this, 'ajax_smtp_test']);
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General Settings
        register_setting('wc_installments_general', 'wc_installments_debug_mode');
        register_setting('wc_installments_general', 'wc_installments_default_status');
        register_setting('wc_installments_general', 'wc_installments_min_installments');
        register_setting('wc_installments_general', 'wc_installments_max_installments');
        
        // WP Fusion Settings
        register_setting('wc_installments_wpf', 'wc_installments_wpf_tag');
        register_setting('wc_installments_wpf', 'wc_installments_remove_tag');
        register_setting('wc_installments_wpf', 'wc_installments_tag_timing');
        
        // Email Settings
        register_setting('wc_installments_email', 'wc_installments_enable_emails');
        register_setting('wc_installments_email', 'wc_installments_notify_admin');
        register_setting('wc_installments_email', 'wc_installments_email_template');
        register_setting('wc_installments_email', 'wc_installments_admin_email_template');
        register_setting('wc_installments_email', 'wc_installments_enable_welcome_email');
        register_setting('wc_installments_email', 'wc_installments_welcome_email_template');
        
        // Reminder Settings
        register_setting('wc_installments_reminder', 'wc_installments_enable_reminders');
        register_setting('wc_installments_reminder', 'wc_installments_reminder_days');
        register_setting('wc_installments_reminder', 'wc_installments_reminder_template');
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=installment-settings') . '">' . __('Settings', 'wc-installments') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Check WP Mail SMTP status
     */
    public function check_wp_mail_smtp_status() {
        $status = [
            'active' => false,
            'version' => '',
            'provider' => '',
            'configured' => false
        ];
        
        if (function_exists('is_plugin_active') && is_plugin_active('wp-mail-smtp/wp_mail_smtp.php')) {
            $status['active'] = true;
            
            if (class_exists('WPMailSMTP\Options')) {
                $options = new \WPMailSMTP\Options();
                $status['version'] = defined('WPMS_PLUGIN_VER') ? WPMS_PLUGIN_VER : 'Unknown';
                $status['provider'] = $options->get('mail', 'mailer');
                $status['configured'] = !empty($options->get('mail', 'from_email'));
            }
        }
        
        return $status;
    }

    /**
     * Display WP Mail SMTP diagnostic information
     */
    public function display_wp_mail_smtp_diagnostics() {
        $smtp_status = $this->check_wp_mail_smtp_status();
        ?>
        <div class="wp-mail-smtp-diagnostics" style="margin-bottom: 20px; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <h3>WP Mail SMTP Diagnostics</h3>
            <table class="widefat striped">
                <tr>
                    <th>WP Mail SMTP Active</th>
                    <td><?php echo $smtp_status['active'] ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>'; ?></td>
                </tr>
                <?php if ($smtp_status['active']): ?>
                <tr>
                    <th>Version</th>
                    <td><?php echo esc_html($smtp_status['version']); ?></td>
                </tr>
                <tr>
                    <th>Mail Provider</th>
                    <td><?php echo esc_html($smtp_status['provider']); ?></td>
                </tr>
                <tr>
                    <th>Configured</th>
                    <td><?php echo $smtp_status['configured'] ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>'; ?></td>
                </tr>
                <tr>
                    <th>Test Email</th>
                    <td>
                        <input type="email" id="smtp-test-email" placeholder="Enter email address" style="width: 250px;">
                        <button type="button" id="send-smtp-test" class="button">Send Test Email</button>
                        <div id="smtp-test-result" style="margin-top: 10px;"></div>
                        <script>
                        jQuery(document).ready(function($) {
                            $('#send-smtp-test').on('click', function() {
                                var email = $('#smtp-test-email').val();
                                if (!email) {
                                    alert('Please enter an email address');
                                    return;
                                }
                                
                                $('#smtp-test-result').html('Sending test email...');
                                
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'wc_installments_smtp_test',
                                        security: '<?php echo wp_create_nonce('smtp_test'); ?>',
                                        email: email
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            $('#smtp-test-result').html('<p style="color:green">' + response.data + '</p>');
                                        } else {
                                            $('#smtp-test-result').html('<p style="color:red">' + response.data + '</p>');
                                        }
                                    },
                                    error: function() {
                                        $('#smtp-test-result').html('<p style="color:red">Ajax error occurred</p>');
                                    }
                                });
                            });
                        });
                        </script>
                    </td>
                </tr>
                <?php else: ?>
                <tr>
                    <td colspan="2">
                        <p><strong>WP Mail SMTP plugin is not active or not detected.</strong></p>
                        <p>Please make sure the plugin is installed and activated.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for SMTP test
     */
    public function ajax_smtp_test() {
        check_ajax_referer('smtp_test', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (empty($email)) {
            wp_send_json_error('Please provide a valid email address');
            return;
        }
        
        $subject = 'WC Installments Manager SMTP Test';
        $message = 'This is a test email sent from WooCommerce Installments Manager plugin at ' . current_time('mysql');
        
        $result = wp_mail($email, $subject, $message);
        
        if ($result) {
            wp_send_json_success('Test email sent successfully via WP Mail SMTP!');
        } else {
            wp_send_json_error('Test email failed. Please check WP Mail SMTP logs for details.');
        }
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-installments'));
        }
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Define tabs
        $tabs = [
            'general'  => __('General', 'wc-installments'),
            'wpf'      => __('WP Fusion', 'wc-installments'),
            'email'    => __('Email Notifications', 'wc-installments'),
            'reminder' => __('Payment Reminders', 'wc-installments'),
        ];
        ?>
        <div class="wrap wc-installments-settings">
            <h1><?php echo esc_html__('Installment Manager Settings', 'wc-installments'); ?></h1>
            
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <?php
                foreach ($tabs as $tab_id => $tab_name) {
                    $active = $current_tab === $tab_id ? ' nav-tab-active' : '';
                    echo '<a href="?page=installment-settings&tab=' . esc_attr($tab_id) . '" class="nav-tab' . esc_attr($active) . '">' . esc_html($tab_name) . '</a>';
                }
                ?>
            </nav>
            
            <form method="post" action="options.php">
                <?php
                switch ($current_tab) {
                    case 'wpf':
                        settings_fields('wc_installments_wpf');
                        $this->render_wpf_settings();
                        break;
                    case 'email':
                        settings_fields('wc_installments_email');
                        $this->render_email_settings();
                        break;
                    case 'reminder':
                        settings_fields('wc_installments_reminder');
                        $this->render_reminder_settings();
                        break;
                    default:
                        settings_fields('wc_installments_general');
                        $this->render_general_settings();
                        break;
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render general settings
     */
    public function render_general_settings() {
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
                        $statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array('wc-pending' => 'Pending');
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
        <?php
    }
    
    /**
     * Render WP Fusion settings
     */
    public function render_wpf_settings() {
        // Check if WP Fusion is active
        $wpf_active = function_exists('wp_fusion');
        
        if (!$wpf_active) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('WP Fusion is not active. These settings will only take effect when WP Fusion is installed and activated.', 'wc-installments') . '</p></div>';
        }
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php echo esc_html__('Installment Tag ID', 'wc-installments'); ?></th>
                <td>
                    <input type="number" name="wc_installments_wpf_tag" value="<?php echo esc_attr(get_option('wc_installments_wpf_tag', '537')); ?>" />
                    <p class="description"><?php echo esc_html__('Enter the WP Fusion tag ID to apply to customers on installment plans', 'wc-installments'); ?></p>
                    
                    <?php if ($wpf_active && method_exists(wp_fusion()->user, 'get_available_tags')) : 
                        $available_tags = wp_fusion()->user->get_available_tags();
                    ?>
                        <div style="margin-top: 10px;">
                            <p><?php echo esc_html__('Available Tags:', 'wc-installments'); ?></p>
                            <select id="wpf-tags-list" style="width: 100%; max-width: 400px;">
                                <?php
                                if (!empty($available_tags)) {
                                    foreach ($available_tags as $tag_id => $tag_name) {
                                        echo '<option value="' . esc_attr($tag_id) . '">' . esc_html($tag_name) . ' (ID: ' . esc_html($tag_id) . ')</option>';
                                    }
                                } else {
                                    echo '<option value="">' . esc_html__('No tags found', 'wc-installments') . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php echo esc_html__('This is a reference list. Please select a tag and click "Use Selected Tag" to apply.', 'wc-installments'); ?></p>
                            <button type="button" class="button" id="use-selected-tag"><?php echo esc_html__('Use Selected Tag', 'wc-installments'); ?></button>
                        </div>
                        
                        <script>
                        jQuery(document).ready(function($) {
                            $('#use-selected-tag').on('click', function(e) {
                                e.preventDefault();
                                var selectedTag = $('#wpf-tags-list').val();
                                if (selectedTag) {
                                    $('input[name="wc_installments_wpf_tag"]').val(selectedTag);
                                }
                            });
                        });
                        </script>
                    <?php endif; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo esc_html__('Remove Tag on Completion', 'wc-installments'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wc_installments_remove_tag" value="yes" <?php checked('yes', get_option('wc_installments_remove_tag', 'yes')); ?> />
                        <?php echo esc_html__('Remove WP Fusion tag when all installments are paid', 'wc-installments'); ?>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo esc_html__('Apply Tag Location', 'wc-installments'); ?></th>
                <td>
                    <select name="wc_installments_tag_timing">
                        <option value="creation" <?php selected('creation', get_option('wc_installments_tag_timing', 'creation')); ?>><?php echo esc_html__('When installment plan is created', 'wc-installments'); ?></option>
                        <option value="first_payment" <?php selected('first_payment', get_option('wc_installments_tag_timing', 'creation')); ?>><?php echo esc_html__('After first payment is complete', 'wc-installments'); ?></option>
                    </select>
                    <p class="description"><?php echo esc_html__('When to apply the installment tag to the customer', 'wc-installments'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render email settings
     */
    public function render_email_settings() {
        // Display SMTP diagnostics at the top
        $this->display_wp_mail_smtp_diagnostics();
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
                <th scope="row"><?php echo esc_html__('Welcome Email', 'wc-installments'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wc_installments_enable_welcome_email" value="yes" <?php checked('yes', get_option('wc_installments_enable_welcome_email', 'yes')); ?> />
                        <?php echo esc_html__('Send welcome email when installment plan is created', 'wc-installments'); ?>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo esc_html__('Welcome Email Template', 'wc-installments'); ?></th>
                <td>
                    <p class="description"><?php echo esc_html__('HTML template for welcome emails. Available placeholders: {customer_name}, {customer_email}, {password}, {login_url}, {installments_url}, {total_amount}, {num_installments}, {site_name}', 'wc-installments'); ?></p>
                    <?php
                    $editor_settings = [
                        'textarea_name' => 'wc_installments_welcome_email_template',
                        'editor_height' => 300,
                        'media_buttons' => false,
                    ];
                    wp_editor(get_option('wc_installments_welcome_email_template', wc_installments_get_default_welcome_email_template()), 'wc_installments_welcome_email', $editor_settings);
                    ?>
                    <div style="margin-top: 10px;">
                        <button type="button" class="button" id="reset-welcome-template"><?php echo esc_html__('Reset to Default Template', 'wc-installments'); ?></button>
                    </div>
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
            
            $('#reset-welcome-template').on('click', function(e) {
                e.preventDefault();
                if (confirm('<?php echo esc_js(__('Are you sure you want to reset to the default template? This will overwrite any customizations.', 'wc-installments')); ?>')) {
                    var defaultTemplate = <?php echo json_encode(wc_installments_get_default_welcome_email_template()); ?>;
                    if (typeof tinymce !== 'undefined' && tinymce.get('wc_installments_welcome_email')) {
                        tinymce.get('wc_installments_welcome_email').setContent(defaultTemplate);
                    } else {
                        $('#wc_installments_welcome_email').val(defaultTemplate);
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render reminder settings
     */
    public function render_reminder_settings() {
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
        <?php
    }
}