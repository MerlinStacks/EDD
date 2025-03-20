=== WooCommerce Estimated Delivery Date ===
Contributors: yourusername
Tags: woocommerce, shipping, delivery, estimated delivery, gutenberg
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display estimated delivery dates on WooCommerce product pages using a customizable Gutenberg block.

== Description ==

WooCommerce Estimated Delivery Date adds a customizable Gutenberg block to display estimated delivery dates on your WooCommerce product pages. The plugin calculates delivery dates based on:

* Default lead times
* Product-specific lead times
* Shipping method transit times
* Store closed days
* Postage closed days

= Features =

* Gutenberg block with extensive styling options
* Product-specific lead times
* Shipping method-specific transit times
* Store and postage closed days configuration
* Cart and checkout display options
* Fully responsive design
* RTL language support
* HPOS (High-Performance Order Storage) compatible

= Block Customization =

The Gutenberg block includes the following customization options:

* Display type (text or date only)
* Text alignment
* Font size, family, and weight
* Text and background colors
* Margin settings
* Border styling
* Icon selection and positioning

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/woocommerce-estimated-delivery-date`, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Estimate DD menu to configure the plugin settings.
4. Add the "Estimated Delivery Date" block to your product pages.

== Frequently Asked Questions ==

= How are delivery dates calculated? =

Delivery dates are calculated by combining:
1. Lead time (product-specific or default)
2. Transit time (based on shipping method)
3. Additional days from settings
4. Excluding store closed days and postage closed days

= Can I override delivery times for specific products? =

Yes, you can set product-specific lead times in the product's shipping tab.

= Does this work with variable products? =

Yes, the delivery dates will update when different variations are selected.

= Is this compatible with custom shipping methods? =

Yes, the plugin works with any shipping method registered with WooCommerce.

== Screenshots ==

1. Estimated delivery date block on product page
2. Block editor settings
3. Admin settings page
4. Product-specific lead time settings
5. Cart and checkout display

== Changelog ==

= 1.0.0 =
* Initial release
* Added Gutenberg block
* Added admin settings
* Added product meta fields
* Added delivery date calculator
* Added frontend display
* Added cart/checkout integration
* Added RTL support
* Added HPOS compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release

== Development ==

= Building Assets =

1. Install dependencies:
   ```
   npm install
   ```

2. Start development build:
   ```
   npm start
   ```

3. Create production build:
   ```
   npm run build
   ```

= Filters =

* `wc_edd_delivery_date_min`: Filter the minimum delivery date
* `wc_edd_delivery_date_max`: Filter the maximum delivery date
* `wc_edd_closed_days`: Filter the closed days array
* `wc_edd_shipping_transit_times`: Filter shipping method transit times

= Actions =

* `wc_edd_before_calculate_dates`: Fires before delivery date calculation
* `wc_edd_after_calculate_dates`: Fires after delivery date calculation
* `wc_edd_delivery_date_updated`: Fires when delivery date is updated

= Examples =

Add custom closed days:
```php
add_filter('wc_edd_closed_days', function($closed_days) {
    $closed_days[] = strtotime('2025-12-25'); // Add Christmas
    return $closed_days;
});
```

Modify transit times:
```php
add_filter('wc_edd_shipping_transit_times', function($transit_times, $method) {
    if ($method === 'flat_rate') {
        $transit_times['min'] = 2;
        $transit_times['max'] = 4;
    }
    return $transit_times;
}, 10, 2);
```

== Credits ==

* Built with the [WordPress Block Editor](https://developer.wordpress.org/block-editor/)
* Uses [WooCommerce](https://woocommerce.com/) hooks and filters