<?php
/**
 * Installment plan creation form template
 */

if (!defined('ABSPATH')) {
    exit;
}
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
                            $countries_obj = new WC_Countries();
                            $countries = $countries_obj->get_allowed_countries();
                            
                            foreach ($countries as $code => $name) {
                                echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
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