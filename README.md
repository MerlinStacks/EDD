# WooCommerce Estimated Delivery Date

Display estimated delivery dates on WooCommerce product pages using a customizable Gutenberg block.

## Description

WooCommerce Estimated Delivery Date adds a customizable Gutenberg block to display estimated delivery dates on your WooCommerce product pages. The plugin calculates delivery dates based on:

- Default lead times
- Product-specific lead times
- Shipping method transit times
- Store closed days
- Postage closed days

### Features

- Gutenberg block with extensive styling options
- Product-specific lead times
- Shipping method-specific transit times
- Store and postage closed days configuration
- Cart and checkout display options
- Fully responsive design
- RTL language support
- HPOS (High-Performance Order Storage) compatible

### Block Customization

The Gutenberg block includes the following customization options:

- Display type (text or date only)
- Text alignment
- Font size, family, and weight
- Text and background colors
- Margin settings
- Border styling
- Icon selection and positioning

## Installation

1. Upload the plugin files to `/wp-content/plugins/woocommerce-estimated-delivery-date`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Estimate DD menu to configure the plugin settings
4. Add the "Estimated Delivery Date" block to your product pages

## Development

### Requirements

- Node.js (v16.0.0 or later)
- npm (v7.0.0 or later)
- WordPress (v5.0 or later)
- WooCommerce (v7.0 or later)
- PHP 8.0 or later

### Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/woocommerce-estimated-delivery-date.git
```

2. Install dependencies:
```bash
cd woocommerce-estimated-delivery-date
npm install
```

3. Start development build:
```bash
npm start
```

4. Create production build:
```bash
npm run build
```

### Filters

- `wc_edd_delivery_date_min`: Filter the minimum delivery date
- `wc_edd_delivery_date_max`: Filter the maximum delivery date
- `wc_edd_closed_days`: Filter the closed days array
- `wc_edd_shipping_transit_times`: Filter shipping method transit times

### Actions

- `wc_edd_before_calculate_dates`: Fires before delivery date calculation
- `wc_edd_after_calculate_dates`: Fires after delivery date calculation
- `wc_edd_delivery_date_updated`: Fires when delivery date is updated

### Examples

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

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin feature/my-new-feature`
5. Submit a pull request

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Credits

- Built with the [WordPress Block Editor](https://developer.wordpress.org/block-editor/)
- Uses [WooCommerce](https://woocommerce.com/) hooks and filters