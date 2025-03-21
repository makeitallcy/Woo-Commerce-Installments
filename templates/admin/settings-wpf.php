<?php
/**
 * WP Fusion settings tab template
 */

if (!defined('ABSPATH')) {
    exit;
}

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
            
            <?php if ($wpf_active && method_exists(wp_fusion()->user, 'get_tags_by_id')) : 
                $wpf_integration = new WC_Installments_Manager_WP_Fusion();
                $available_tags = $wpf_integration->get_available_tags();
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
</table>