<?php
/**
 * Settings page template
 */

if (!defined('ABSPATH')) {
    exit;
}
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