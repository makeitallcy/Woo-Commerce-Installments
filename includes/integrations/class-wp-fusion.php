<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP Fusion integration
 */
class WC_Installments_Manager_WP_Fusion {
    /**
     * Constructor
     */
    public function __construct() {
        // Only initialize if WP Fusion is active
        if (!function_exists('wp_fusion')) {
            return;
        }
        
        add_action('wc_installments_plan_completed', [$this, 'handle_plan_completion'], 10, 2);
    }
    
    /**
     * Apply tag to customer
     */
    public function apply_tag($customer_id) {
        if (!function_exists('wp_fusion') || !method_exists(wp_fusion()->user, 'apply_tags')) {
            return false;
        }
        
        try {
            $tag_id = absint(get_option('wc_installments_wpf_tag', 537));
            $tags = [$tag_id];
            
            wp_fusion()->user->apply_tags($tags, $customer_id);
            
            wc_installments_log('Applied WP Fusion tag ID: ' . $tag_id . ' to customer ' . $customer_id);
            return true;
            
        } catch (Exception $e) {
            wc_installments_log('Error applying WP Fusion tag: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove tag from customer
     */
    public function remove_tag($customer_id) {
        if (!function_exists('wp_fusion') || !method_exists(wp_fusion()->user, 'remove_tags')) {
            return false;
        }
        
        if (get_option('wc_installments_remove_tag', 'yes') !== 'yes') {
            return false;
        }
        
        try {
            $tag_id = absint(get_option('wc_installments_wpf_tag', 537));
            $tags = [$tag_id];
            
            wp_fusion()->user->remove_tags($tags, $customer_id);
            
            wc_installments_log('Removed WP Fusion tag ID: ' . $tag_id . ' from customer ' . $customer_id);
            return true;
            
        } catch (Exception $e) {
            wc_installments_log('Error removing WP Fusion tag: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle plan completion
     */
    public function handle_plan_completion($customer_id, $orders) {
        $this->remove_tag($customer_id);
    }
    
    /**
     * Get available tags
     */
    public function get_available_tags() {
        if (!function_exists('wp_fusion') || !method_exists(wp_fusion()->user, 'get_tags_by_id')) {
            return [];
        }
        
        return wp_fusion()->user->get_tags_by_id();
    }
}