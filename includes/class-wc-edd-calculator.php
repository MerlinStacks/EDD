<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_EDD_Calculator
 *
 * Handles the calculation logic for estimated delivery dates.
 */
class WC_EDD_Calculator {
    /**
     * @var WC_EDD_Calculator The single instance of the class
     */
    protected static ?WC_EDD_Calculator $_instance = null;
    private array $settings = [];
    private array $shipping_settings = [];

    /**
     * Main WC_EDD_Calculator Instance
     */
    public static function get_instance(): WC_EDD_Calculator {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor - Load settings
     */
    public function __construct() {
        // Load main settings (consider caching this if called frequently)
        $defaults = [ // Define defaults here too for calculator context
            'general' => [
                'default_lead_time_min' => 1,
                'default_lead_time_max' => 3,
                'days_to_add' => 0,
                // 'cutoff_time' => '16:00', // TODO: Add cutoff time setting
            ],
            'closed_days' => [
                'store_weekly' => ['sunday'],
                'store_specific' => [],
            ],
            'postage_days' => [
                'specific' => [],
            ],
        ];
        $this->settings = get_option('wc_edd_settings', $defaults);
        // Ensure structure integrity
        $this->settings = wp_parse_args($this->settings, $defaults);
        $this->settings['general'] = wp_parse_args($this->settings['general'] ?? [], $defaults['general']);
        $this->settings['closed_days'] = wp_parse_args($this->settings['closed_days'] ?? [], $defaults['closed_days']);
        $this->settings['postage_days'] = wp_parse_args($this->settings['postage_days'] ?? [], $defaults['postage_days']);

        // Load shipping method settings
        $this->shipping_settings = get_option('wc_edd_shipping_methods', []);
    }

    /**
     * Calculate delivery date range.
     *
     * @param int|null $product_id Product ID (optional).
     * @param string|null $shipping_method_id Specific shipping method ID (e.g., 'flat_rate:1') (optional).
     * @return array{start: string, end: string}|array Empty array on failure.
     */
    public function calculate_delivery_range(?int $product_id = null, ?string $shipping_method_id = null): array {
        try {
            $start_date = $this->get_start_date();
            $lead_time_range = $this->get_lead_time_range($product_id); // Returns ['min' => int, 'max' => int]
            $transit_time_range = $this->get_transit_time_range($shipping_method_id); // Returns ['min' => int, 'max' => int]
            $days_to_add = absint($this->settings['general']['days_to_add'] ?? 0);

            // Calculate total min/max days
            $total_min_days = $lead_time_range['min'] + $transit_time_range['min'] + $days_to_add;
            $total_max_days = $lead_time_range['max'] + $transit_time_range['max'] + $days_to_add;

            // Calculate actual delivery dates, skipping non-working days
            $earliest_delivery = $this->add_working_days($start_date, $total_min_days);
            // For the latest date, start from the earliest and add the difference in days
            $days_diff = max(0, $total_max_days - $total_min_days);
            $latest_delivery = $this->add_working_days(clone $earliest_delivery, $days_diff); // Clone to avoid modifying earliest

            return [
                'start' => $earliest_delivery->format('Y-m-d'),
                'end' => $latest_delivery->format('Y-m-d')
            ];
        } catch (\Exception $e) {
            error_log('WC EDD - Error calculating delivery range: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get start date based on order cutoff time (if implemented).
     * Currently starts from today or tomorrow, skipping initial closed days.
     */
    private function get_start_date(): \DateTime {
        // TODO: Implement cutoff time logic using $this->settings['general']['cutoff_time']
        $start_date = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));

        // Skip initial closed days (store closed or postage holiday)
        while ($this->is_store_closed($start_date) || $this->is_postage_holiday($start_date)) {
            $start_date->modify('+1 day');
        }

        return $start_date;
    }

    /**
     * Get lead time range for a product.
     * Returns ['min' => int, 'max' => int].
     */
    private function get_lead_time_range(?int $product_id = null): array {
        $min_lead = null;
        $max_lead = null;

        if ($product_id) {
            $min_meta = get_post_meta($product_id, '_wc_edd_min_lead_time', true);
            $max_meta = get_post_meta($product_id, '_wc_edd_max_lead_time', true);

            // Use product meta if BOTH min and max are set and numeric
            if ($min_meta !== '' && $max_meta !== '' && is_numeric($min_meta) && is_numeric($max_meta)) {
                $min_lead = absint($min_meta);
                $max_lead = absint($max_meta);
            } elseif ($min_meta !== '' && is_numeric($min_meta)) { // Use only min if set
                 $min_lead = absint($min_meta);
                 $max_lead = $min_lead; // Set max = min if only min is defined
            } elseif ($max_meta !== '' && is_numeric($max_meta)) { // Use only max if set
                 $max_lead = absint($max_meta);
                 $min_lead = $max_lead; // Set min = max if only max is defined
            }
        }

        // Fallback to default settings if product-specific times are not validly set
        if ($min_lead === null || $max_lead === null) {
            $min_lead = absint($this->settings['general']['default_lead_time_min'] ?? 1);
            $max_lead = absint($this->settings['general']['default_lead_time_max'] ?? 3);
        }

        // Ensure min <= max
        if ($min_lead > $max_lead) {
            $max_lead = $min_lead;
        }

        return ['min' => $min_lead, 'max' => $max_lead];
    }

    /**
     * Get transit time range.
     * If a specific method is provided, use its times.
     * Otherwise, find the min/max range across all available methods (e.g., for product page).
     * Returns ['min' => int, 'max' => int].
     */
    private function get_transit_time_range(?string $shipping_method_id = null): array {
        $default_min = 1; // Default if no methods found/configured
        $default_max = 5;

        if (empty($this->shipping_settings)) {
            return ['min' => $default_min, 'max' => $default_max];
        }

        if ($shipping_method_id && isset($this->shipping_settings[$shipping_method_id])) {
            $method_settings = $this->shipping_settings[$shipping_method_id];
            $min_transit = isset($method_settings['min_transit']) && $method_settings['min_transit'] !== '' ? absint($method_settings['min_transit']) : $default_min;
            $max_transit = isset($method_settings['max_transit']) && $method_settings['max_transit'] !== '' ? absint($method_settings['max_transit']) : $default_max;
             // Ensure min <= max
            if ($min_transit > $max_transit) $max_transit = $min_transit;
            return ['min' => $min_transit, 'max' => $max_transit];
        }

        // If no specific method, find the overall min/max range from ALL configured methods
        $overall_min = PHP_INT_MAX;
        $overall_max = 0;
        $found_valid = false;

        foreach ($this->shipping_settings as $method_settings) {
             if (isset($method_settings['min_transit']) && $method_settings['min_transit'] !== '' && is_numeric($method_settings['min_transit'])) {
                 $overall_min = min($overall_min, absint($method_settings['min_transit']));
                 $found_valid = true;
             }
             if (isset($method_settings['max_transit']) && $method_settings['max_transit'] !== '' && is_numeric($method_settings['max_transit'])) {
                 $overall_max = max($overall_max, absint($method_settings['max_transit']));
                 $found_valid = true;
             }
        }

        if (!$found_valid) {
            return ['min' => $default_min, 'max' => $default_max];
        }

        // Ensure min <= max after finding overall range
        if ($overall_min > $overall_max) $overall_min = $overall_max; // If only max was found, min becomes max. If only min, max remains 0, so min becomes 0. Needs check.
        if ($overall_max < $overall_min) $overall_max = $overall_min; // If only min was found, max becomes min.

        return ['min' => ($overall_min === PHP_INT_MAX ? $default_min : $overall_min), 'max' => $overall_max];
    }


    /**
     * Add working days to a date, skipping store closed days and postage holidays.
     */
    private function add_working_days(\DateTime $date, int $days_to_add): \DateTime {
        $date = clone $date; // Work on a copy
        $days_added = 0;

        while ($days_added < $days_to_add) {
            $date->modify('+1 day');
            // Only count the day if the store is open AND it's not a postage holiday
            if (!$this->is_store_closed($date) && !$this->is_postage_holiday($date)) {
                $days_added++;
            }
        }
         // Final check: if the resulting date is a closed day, move to the next working day
         while ($this->is_store_closed($date) || $this->is_postage_holiday($date)) {
             $date->modify('+1 day');
         }

        return $date;
    }

    /**
     * Check if a date is a store closed day (weekly or specific).
     */
    private function is_store_closed(\DateTime $date): bool {
        try {
            // Check weekly closed days
            $closed_days_weekly = $this->settings['closed_days']['store_weekly'] ?? [];
            $day_name = strtolower($date->format('l')); // Monday, Tuesday, etc.
            if (in_array($day_name, $closed_days_weekly, true)) {
                return true;
            }

            // Check specific closed dates
            $closed_days_specific = $this->settings['closed_days']['store_specific'] ?? [];
            $date_string = $date->format('Y-m-d');
            if (in_array($date_string, $closed_days_specific, true)) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log('WC EDD - Error checking store closed day: ' . $e->getMessage());
            return false; // Fail safe: assume open
        }
    }

    /**
     * Check if a date is a postage holiday.
     */
    private function is_postage_holiday(\DateTime $date): bool {
        try {
            $postage_holidays = $this->settings['postage_days']['specific'] ?? [];
            $date_string = $date->format('Y-m-d');
            return in_array($date_string, $postage_holidays, true);
        } catch (\Exception $e) {
            error_log('WC EDD - Error checking postage holiday: ' . $e->getMessage());
            return false; // Fail safe: assume not a holiday
        }
    }

} // End class WC_EDD_Calculator