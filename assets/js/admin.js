(function($) {
    'use strict';
    
    // Set up global error handler to catch any uncaught errors
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('Global error caught:', message, 'at', source, lineno, colno);
        if (error) {
            console.error('Error object:', error);
        }
        return false;
    };
    
    $(document).ready(function() {
        // Debug information
        console.log('Document ready');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Select2 available:', typeof $.fn.select2 === 'function');
        
        if (typeof wc_installments_params === 'undefined') {
            console.error('wc_installments_params is not defined. AJAX will not work.');
            return;
        }
        
        console.log('AJAX params:', wc_installments_params);
        
        // Try a manual initialization of one Select2 to verify it works
        try {
            console.log('Trying a direct Select2 initialization...');
            $('.existing-customer-select').select2({
                placeholder: "Direct initialization test"
            });
            console.log('Direct Select2 initialization successful');
        } catch(e) {
            console.error('Direct Select2 initialization failed:', e);
        }
        
        
        console.log('AJAX params:', wc_installments_params);
        
        try {
            // Initialize Select2 for customer dropdown - simplified version
$('.existing-customer-select').select2({
    placeholder: "Search for a customer...",
    minimumInputLength: 2,
    ajax: {
        url: wc_installments_params.ajax_url,
        dataType: 'json',
        data: function(params) {
            return {
                term: params.term,
                action: 'wc_installments_search_customers',
                security: wc_installments_params.search_customers_nonce
            };
        },
        processResults: function(data) {
            return data;
        }
    }
});

            // Customer type toggle
            $('input[name="customer_type"]').change(function() {
                var type = $(this).val();
                if (type === 'existing') {
                    $('#existing-customer-section').show();
                    $('#new-customer-section').hide();
                    $('.existing-customer-select').prop('required', true);
                    $('.new-customer-input').prop('required', false);
                } else {
                    $('#existing-customer-section').hide();
                    $('#new-customer-section').show();
                    $('.existing-customer-select').prop('required', false);
                    $('.new-customer-input').prop('required', true);
                }
            });
            
            // Initialize product select with AJAX search
            initializeProductSelect($('.product-select:first'));

            // Product management
            $('#add-product').on('click', function() {
                var clone = $('.product-entry:first').clone();
                clone.find('input').val('1');
                clone.find('.remove-product').show();
                
                // Clear and destroy Select2
                try {
                    clone.find('.product-select').select2('destroy');
                } catch (e) {
                    console.error('Error destroying Select2:', e);
                }
                
                clone.find('.product-select').val('');
                
              // Add to DOM
                $('#product-list').append(clone);
                
                // Reinitialize Select2
                initializeProductSelect(clone.find('.product-select'));
                
                calculateTotals();
            });

            // Remove product handler
            $(document).on('click', '.remove-product', function() {
                if ($('.product-entry').length > 1) {
                    $(this).closest('.product-entry').remove();
                    calculateTotals();
                }
            });

            // Discount handling
            $('input[name="discount_type"]').change(function() {
                var type = $(this).val();
                var discountAmount = $('.discount-amount');
                var discountSymbol = $('.discount-symbol');

                if (type === 'none') {
                    discountAmount.hide();
                    $('#discount_value').val('');
                } else {
                    discountAmount.show();
                    discountSymbol.text(type === 'percentage' ? '%' : '$');
                }
                calculateTotals();
            });
            
            // Password validation
            $('#new_customer_password').on('keyup', function() {
                var password = $(this).val();
                var $requirements = $('.password-requirements li');
                
                // Check each requirement and update visual indicators
                $requirements.filter('#length').toggleClass('met', password.length >= 8);
                $requirements.filter('#uppercase').toggleClass('met', /[A-Z]/.test(password));
                $requirements.filter('#lowercase').toggleClass('met', /[a-z]/.test(password));
                $requirements.filter('#number').toggleClass('met', /[0-9]/.test(password));
                $requirements.filter('#special').toggleClass('met', /[^A-Za-z0-9]/.test(password));
                
                // Calculate strength
                var strength = 0;
                strength += password.length >= 8 ? 1 : 0;
                strength += /[A-Z]/.test(password) ? 1 : 0;
                strength += /[a-z]/.test(password) ? 1 : 0;
                strength += /[0-9]/.test(password) ? 1 : 0;
                strength += /[^A-Za-z0-9]/.test(password) ? 1 : 0;
                
                var $meter = $('.password-strength-meter');
                $meter.removeClass('weak medium strong');
                
                if (strength >= 5) {
                    $meter.addClass('strong').css('width', '100%').css('background', '#4CAF50');
                } else if (strength >= 3) {
                    $meter.addClass('medium').css('width', '66%').css('background', '#ffc107');
                } else if (strength > 0) {
                    $meter.addClass('weak').css('width', '33%').css('background', '#ff5722');
                } else {
                    $meter.css('width', '0%');
                }
                
                // Style met requirements
                $('.password-requirements li.met').css('color', '#4CAF50');
            });

            // Calculate totals function
            function calculateTotals() {
                var subtotal = 0;
                
                $('.product-entry').each(function() {
                    var selectedOption = $(this).find('.product-select').find(':selected');
                    var price = selectedOption.data('price') || 0;
                    if (!price && selectedOption.length && selectedOption.val()) {
                        // For AJAX loaded options
                        var data = $(this).find('.product-select').select2('data')[0];
                        if (data && data.price) {
                            price = data.price;
                        }
                    }
                    
                    var quantity = parseInt($(this).find('.quantity-input').val()) || 0;
                    subtotal += price * quantity;
                });

                var discountType = $('input[name="discount_type"]:checked').val();
                var discountValue = parseFloat($('#discount_value').val()) || 0;
                var discountAmount = 0;

                if (discountType === 'percentage') {
                    discountAmount = (subtotal * (discountValue / 100));
                } else if (discountType === 'fixed') {
                    discountAmount = discountValue;
                }

                var total = Math.max(0, subtotal - discountAmount);
                var installments = parseInt($('input[name="num_installments"]').val()) || 1;
                var installmentAmount = (total / installments).toFixed(2);

                // Update display with formatted numbers
                $('#subtotal').text('$' + subtotal.toFixed(2));
                $('#discount-amount').text('$' + discountAmount.toFixed(2));
                $('#final-total').text('$' + total.toFixed(2));
                $('#installment-amount').text('$' + installmentAmount);
            }

            // Bind calculation to all relevant changes
            $(document).on('change', '.product-select, .quantity-input, input[name="num_installments"], #discount_value', calculateTotals);
            $(document).on('keyup', '#discount_value, input[name="num_installments"], .quantity-input', calculateTotals);

            // Form validation and submission
            $('#create-installment-form').on('submit', function(e) {
                var isValid = true;
                var customerType = $('input[name="customer_type"]:checked').val();
                var $form = $(this);

                // Prevent double submission
                if ($form.hasClass('loading')) {
                    e.preventDefault();
                    return false;
                }

                // Validate customer selection/creation
                if (customerType === 'existing') {
                    if (!$('.existing-customer-select').val()) {
                        alert('Please select a customer');
                        isValid = false;
                    }
                } else {
                    // Validate new customer fields
                    var requiredFields = {
                        'new_customer_first_name': 'First Name',
                        'new_customer_last_name': 'Last Name',
                        'new_customer_email': 'Email'
                    };

                    for (var field in requiredFields) {
                        if (!$('input[name="' + field + '"]').val()) {
                            alert(requiredFields[field] + ' is required');
                            isValid = false;
                            break;
                        }
                    }

                    // Validate email format
                    var email = $('input[name="new_customer_email"]').val();
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        alert('Please enter a valid email address');
                        isValid = false;
                    }

                    // Validate password
                    if (isValid) {
                        var password = $('input[name="new_customer_password"]').val();
                        var confirmPassword = $('input[name="new_customer_password_confirm"]').val();

                        if (!password) {
                            alert('Password is required');
                            isValid = false;
                        } else if (password !== confirmPassword) {
                            alert('Passwords do not match');
                            isValid = false;
                        } else {
                            var strength = 0;
                            strength += password.length >= 8 ? 1 : 0;
                            strength += /[A-Z]/.test(password) ? 1 : 0;
                            strength += /[a-z]/.test(password) ? 1 : 0;
                            strength += /[0-9]/.test(password) ? 1 : 0;
                            strength += /[^A-Za-z0-9]/.test(password) ? 1 : 0;

                            if (strength < 5) {
                                alert('Password does not meet all requirements');
                                isValid = false;
                            }
                        }
                    }
                    
                    // Validate billing fields
                    if (isValid) {
                        var requiredBillingFields = {
                            'billing_address_1': 'Address Line 1',
                            'billing_city': 'City',
                            'billing_postcode': 'Postcode/ZIP',
                            'billing_country': 'Country'
                        };

                        for (var field in requiredBillingFields) {
                            if (!$('input[name="' + field + '"]').val() && !$('select[name="' + field + '"]').val()) {
                                alert(requiredBillingFields[field] + ' is required');
                                isValid = false;
                                break;
                            }
                        }
                    }
                }

                // Validate product selection and quantities
                if ($('.product-select').filter(function() { return !$(this).val(); }).length > 0) {
                    alert('Please select all products');
                    isValid = false;
                }

                // Validate out of stock products
                var hasOutOfStock = false;
                $('.product-select').each(function() {
                    var data = $(this).select2('data')[0];
                    if (data && data.stock === 'outofstock') {
                        hasOutOfStock = true;
                        return false;
                    }
                });

                if (hasOutOfStock) {
                    alert('One or more selected products are out of stock');
                    isValid = false;
                }

                if ($('.quantity-input').filter(function() { return parseInt($(this).val()) < 1; }).length > 0) {
                    alert('Please enter valid quantities for all products');
                    isValid = false;
                }

                // Validate installment number
                var installments = parseInt($('input[name="num_installments"]').val());
                var min_installments = parseInt(wc_installments_params.min_installments);
                var max_installments = parseInt(wc_installments_params.max_installments);
                
                if (!installments || installments < min_installments || installments > max_installments) {
                    alert('Please enter a valid number of installments (' + min_installments + '-' + max_installments + ')');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // Add loading state if form is valid
                $form.addClass('loading');
                $form.css('opacity', '0.5');
                $form.css('position', 'relative');
                $form.append('<div class="spinner" style="position: absolute; top: 50%; left: 50%; margin-top: -10px; margin-left: -10px; width: 20px; height: 20px; background: url(../wp-admin/images/spinner.gif) no-repeat; background-position: 50% 50%; display: block;"></div>');
                $form.find('input[type="submit"]').prop('disabled', true);
            });

            // Initial calculations
            calculateTotals();
        } catch (e) {
            console.error('Error initializing form:', e);
        }
    });
    
    // Function to initialize product select with AJAX search
    function initializeProductSelect($element) {
        try {
            console.log('Initializing product select for element:', $element);
            
            $element.select2({
                placeholder: "Search for a product...",
                width: '100%',
                minimumInputLength: 2,
                dropdownAutoWidth: true,
                ajax: {
                    url: wc_installments_params.ajax_url,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term,
                            action: 'wc_installments_search_products',
                            security: wc_installments_params.search_products_nonce
                        };
                    },
                    processResults: function(data) {
                        console.log('Product search results:', data);
                        return data;
                    },
                    cache: true,
                    error: function(xhr, status, error) {
                        console.error('AJAX error in product search:', error);
                        console.log('Status:', status);
                        console.log('Response:', xhr.responseText);
                    }
                },
                templateResult: formatProduct,
                templateSelection: formatProductSelection
            }).on('select2:open', function() {
                console.log('Product select opened');
            }).on('select2:select', function(e) {
                console.log('Product selected:', e.params.data);
                calculateTotals();
            }).on('select2:error', function(e) {
                console.error('Select2 error:', e);
            });
        } catch (e) {
            console.error('Error in initializeProductSelect:', e);
        }
    }

    function formatProduct(product) {
        if (!product.id) {
            return product.text;
        }
        
        var $product = $(
            '<span class="product-option' + (product.stock_class ? ' ' + product.stock_class : '') + '">' + 
            product.text + 
            '</span>'
        );
        
        return $product;
    }
    
    function formatProductSelection(product) {
        return product.text || product.id;
    }
    
    function calculateTotals() {
        try {
            var subtotal = 0;
            
            $('.product-entry').each(function() {
                var selectedOption = $(this).find('.product-select').find(':selected');
                var price = selectedOption.data('price') || 0;
                if (!price && selectedOption.length && selectedOption.val()) {
                    // For AJAX loaded options
                    var data = $(this).find('.product-select').select2('data')[0];
                    if (data && data.price) {
                        price = data.price;
                    }
                }
                
                var quantity = parseInt($(this).find('.quantity-input').val()) || 0;
                subtotal += price * quantity;
            });

            var discountType = $('input[name="discount_type"]:checked').val();
            var discountValue = parseFloat($('#discount_value').val()) || 0;
            var discountAmount = 0;

            if (discountType === 'percentage') {
                discountAmount = (subtotal * (discountValue / 100));
            } else if (discountType === 'fixed') {
                discountAmount = discountValue;
            }

            var total = Math.max(0, subtotal - discountAmount);
            var installments = parseInt($('input[name="num_installments"]').val()) || 1;
            var installmentAmount = (total / installments).toFixed(2);

            // Update display with formatted numbers
            $('#subtotal').text('$' + subtotal.toFixed(2));
            $('#discount-amount').text('$' + discountAmount.toFixed(2));
            $('#final-total').text('$' + total.toFixed(2));
            $('#installment-amount').text('$' + installmentAmount);
        } catch (e) {
            console.error('Error in calculateTotals:', e);
        }
    }
})(jQuery);