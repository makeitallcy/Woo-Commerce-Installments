# WooCommerce Installments Manager

## Description

WooCommerce Installments Manager enables store owners to create and manage custom installment payment plans for their customers. Split payments across multiple scheduled transactions, track payment progress, and automate the entire process from start to finish.

Perfect for businesses offering financing options, payment plans, or those who need more flexible payment scheduling than standard subscriptions.

## Features

- **Create Custom Installment Plans**: Set up payment plans with variable numbers of installments
- **Customer Integration**: Works with existing customers or create new accounts during setup
- **Product Selection**: Include any products in your installment plans with quantity options
- **Flexible Discounting**: Apply percentage or fixed amount discounts to payment plans
- **Payment Tracking**: Monitor completed and outstanding payments in a centralized dashboard
- **Automated Reminders**: Send email reminders before payments are due
- **Completion Notifications**: Alert customers and admins when payment plans are completed
- **My Account Integration**: Give customers visibility into their payment progress
- **WP Fusion Support**: Apply and remove tags for customers with installment plans
- **Detailed Reporting**: Get insights on payment activity, customer engagement, and more

## Installation

1. Upload the `woocommerce-installments-manager` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Installment Plans in your WordPress admin menu to configure settings

## Requirements

- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.0 or higher

## Configuration

### General Settings

Access the settings page via the "Settings" tab under the Installment Plans menu to configure:

- Minimum and maximum number of installments
- Default order status for installment orders
- Debug mode for troubleshooting

### Email Templates

Customize email templates for:
- Payment reminders
- Completion notifications
- Welcome emails for new customers

### WP Fusion Integration

If you use WP Fusion, you can configure:
- Tag to apply for customers on installment plans
- Whether to remove tags on plan completion
- When to apply tags (plan creation or first payment)

## Usage

### Creating a New Installment Plan

1. Go to Installment Plans > Create New Plan
2. Select an existing customer or create a new one
3. Choose products to include in the plan
4. Set the number of installments
5. Apply discounts if needed
6. Click "Create Installment Plan"

### Monitoring Plans

Use the Reports section to:
- View active and completed plans
- Monitor payment progress
- Export payment data to CSV

## FAQ

**Q: Can customers make early payments?**
A: Yes, customers can pay any active installment at any time through their My Account page.

**Q: Does this plugin handle automatic payments?**
A: The plugin creates separate WooCommerce orders for each installment. Customers can use any payment method supported by your store for each payment.

**Q: Can I change the number of installments after creating a plan?**
A: This is not supported in the current version. The number of installments is fixed when the plan is created.

## Changelog

### 2.0.0
- Complete rewrite with enhanced reporting
- Added WP Fusion integration
- Improved email notification system
- Added customer dashboard with progress tracking
- Enhanced admin reporting

### 1.0.0
- Initial release

## Support

For support questions, feature requests, or bug reports, please contact our support team at support@example.com.
