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
     * Calculate delivery date range
     */
    public function calculate_delivery_range($product_id = null, $default_lead_time = 0) {
        try {
            $start_date = $this->get_start_date();
            $total_days = $this->calculate_total_days($product_id, $default_lead_time);
            
            if (!is_array($total_days)) {
                return array();
            }

            $earliest_delivery = $this->add_business_days($start_date, $total_days['min']);
            $latest_delivery = $this->add_business_days($start_date, $total_days['max']);
            
            return array(
                'start' => $earliest_delivery->format('Y-m-d'),
                'end' => $latest_delivery->format('Y-m-d')
            );
        } catch (Exception $e) {
            error_log('ED Dates CK - Error calculating delivery range: ' . $e->getMessage());
            return array();
        }
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
    private function calculate_total_days($product_id = null, $default_lead_time = 0) {
        try {
            // Get lead time
            $lead_time = $this->get_lead_time($product_id, $default_lead_time);
            if ($lead_time === false) {
                return false;
            }

            // Get shipping method transit times
            $transit_times = $this->get_transit_times();
            if (empty($transit_times)) {
                return false;
            }

            // Calculate min and max transit times
            $min_transit = PHP_INT_MAX;
            $max_transit = 0;
            foreach ($transit_times as $transit_time) {
                $transit_days = intval($transit_time);
                $min_transit = min($min_transit, $transit_days);
                $max_transit = max($max_transit, $transit_days);
            }

            // Return range
            return array(
                'min' => $lead_time + $min_transit,
                'max' => $lead_time + $max_transit
            );
        } catch (Exception $e) {
            error_log('ED Dates CK - Error calculating total days: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get lead time for a product
     */
    private function get_lead_time($product_id = null, $default_lead_time = 0) {
        try {
            if ($product_id) {
                $product_lead_time = get_post_meta($product_id, '_ed_dates_ck_lead_time', true);
                if ($product_lead_time !== '') {
                    return intval($product_lead_time);
                }
            }
            
            return $default_lead_time;
        } catch (Exception $e) {
            error_log('ED Dates CK - Error getting lead time: ' . $e->getMessage());
            return false;
        }
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