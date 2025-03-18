<?php
if (!defined('ABSPATH')) {
    exit;
}

class ED_Dates_CK_Calculator {
    /**
     * @var ED_Dates_CK_Calculator The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main ED_Dates_CK_Calculator Instance
     */
    public static function get_instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Calculate estimated delivery date
     */
    public function calculate_estimated_delivery($product_id = null) {
        $start_date = $this->get_start_date();
        $total_days = $this->calculate_total_days($product_id);
        
        $delivery_date = $this->add_business_days($start_date, $total_days);
        
        return $this->format_delivery_date($delivery_date);
    }

    /**
     * Get start date based on order cutoff time
     */
    private function get_start_date() {
        $current_time = current_time('H:i');
        $cutoff_time = get_option('ed_dates_ck_order_cutoff_time', '16:00');
        
        $start_date = new DateTime();
        
        // If current time is after cutoff time, start from next day
        if ($current_time > $cutoff_time) {
            $start_date->modify('+1 day');
        }
        
        // Skip closed days
        while ($this->is_closed_day($start_date)) {
            $start_date->modify('+1 day');
        }
        
        return $start_date;
    }

    /**
     * Calculate total days needed for delivery
     */
    private function calculate_total_days($product_id = null) {
        $days = 0;
        
        // Add product lead time
        if ($product_id) {
            $min_lead_time = absint(get_post_meta($product_id, '_ed_dates_ck_min_lead_time', true));
            $max_lead_time = absint(get_post_meta($product_id, '_ed_dates_ck_max_lead_time', true));
            
            if ($min_lead_time > 0 && $max_lead_time >= $min_lead_time) {
                $days += rand($min_lead_time, $max_lead_time);
            }
        }
        
        // Add shipping method transit time
        $shipping_methods = get_option('ed_dates_ck_shipping_methods', array());
        $chosen_method = WC()->session ? WC()->session->get('chosen_shipping_methods') : array();
        
        if (is_array($chosen_method) && !empty($chosen_method) && isset($shipping_methods[$chosen_method[0]])) {
            $method_settings = $shipping_methods[$chosen_method[0]];
            if (isset($method_settings['min_days']) && isset($method_settings['max_days'])) {
                $min_days = absint($method_settings['min_days']);
                $max_days = absint($method_settings['max_days']);
                if ($min_days > 0 && $max_days >= $min_days) {
                    $days += rand($min_days, $max_days);
                }
            }
        }
        
        return absint($days);
    }

    /**
     * Add business days to date
     */
    private function add_business_days($date, $days) {
        try {
            $date = clone $date;
            $days = absint($days);
            
            while ($days > 0) {
                $date->modify('+1 day');
                
                if (!$this->is_closed_day($date)) {
                    $days--;
                }
            }
            
            return $date;
        } catch (Exception $e) {
            error_log('ED Dates CK - Error adding business days: ' . $e->getMessage());
            return new DateTime();
        }
    }

    /**
     * Check if a date is a closed day
     */
    private function is_closed_day($date) {
        if (!($date instanceof DateTime)) {
            return false;
        }

        try {
            // Check shop closed days
            $closed_days = get_option('ed_dates_ck_shop_closed_days', array('sunday'));
            if (!is_array($closed_days)) {
                $closed_days = array('sunday');
            }
            $day_name = strtolower($date->format('l'));
            
            if (in_array($day_name, $closed_days)) {
                return true;
            }
            
            // Check shop holidays
            $shop_holidays = get_option('ed_dates_ck_shop_holidays', array());
            if (!is_array($shop_holidays)) {
                $shop_holidays = array();
            }
            $date_string = $date->format('Y-m-d');
            
            if (in_array($date_string, $shop_holidays)) {
                return true;
            }
            
            // Check postage holidays
            $postage_holidays = get_option('ed_dates_ck_postage_holidays', array());
            if (!is_array($postage_holidays)) {
                $postage_holidays = array();
            }
            if (in_array($date_string, $postage_holidays)) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('ED Dates CK - Error checking closed day: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format delivery date for display
     */
    private function format_delivery_date($date) {
        try {
            if (!($date instanceof DateTime)) {
                return '';
            }
            return apply_filters('ed_dates_ck_date_format', $date->format('l, F j, Y'));
        } catch (Exception $e) {
            error_log('ED Dates CK - Error formatting date: ' . $e->getMessage());
            return '';
        }
    }
} 