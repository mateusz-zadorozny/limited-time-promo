# Limited Time Promo

A WooCommerce plugin that enables time-limited promotional campaigns with conditional discounts, free shipping, and an animated sticky message bar.

## Features

- **Time-Based Promotions**: Set start and end dates for your promotional campaigns
- **Conditional Logic**: Apply promotions based on:
  - Product categories (require at least one item from selected categories)
  - Minimum cart amount (before discounts)
- **Flexible Discounts**: Apply percentage-based discounts to the entire cart
- **Free Shipping**: Override shipping costs when promo conditions are met
- **Animated Sticky Bar**: Eye-catching gradient-animated message bar with:
  - Customizable two-color gradient
  - Optional CTA button with link
  - Dismissible with 15-minute cookie
  - Fully responsive design
- **Easy Management**: Simple settings page under WooCommerce Marketing tab

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

1. Upload the `limited-time-promo` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Marketing > Limited Time Promo to configure your promotion

## Usage

### Basic Setup

1. Go to **Marketing > Limited Time Promo** in your WordPress admin
2. Set your promo start and end dates
3. Enter your promotional message
4. Toggle **Activate Promo** to enable
5. Save settings

### Advanced Configuration

**Product Categories**: Select one or more categories. The cart must contain at least one item from these categories for the promo to apply.

**Discount Percentage**: Enter a percentage (0-100) to discount the entire cart.

**Free Shipping**: Enable to make shipping free when promo conditions are met.

**Minimum Cart Amount**: Set a minimum cart subtotal (before discounts) required for the promo.

**CTA Button**: Add a call-to-action button with custom text and optional link.

**Gradient Colors**: Customize the animated gradient background with two colors of your choice.

### Promo Logic

The promotion applies when ALL of the following conditions are true:

1. Promo is activated
2. Current date is within the promo date range
3. IF categories are specified: Cart contains at least one item from those categories
4. IF minimum cart is specified: Cart subtotal meets or exceeds the minimum

If neither categories nor minimum cart are specified, the promo applies automatically (date range only).

## Development

### File Structure

```
limited-time-promo/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── includes/
│   ├── class-ltp-admin.php
│   ├── class-ltp-frontend.php
│   └── class-ltp-promo-logic.php
├── limited-time-promo.php
├── uninstall.php
└── README.md
```

## Changelog

### 1.0.0
- Initial release
- Time-based promotional campaigns
- Conditional discounts and free shipping
- Animated sticky message bar
- Category and cart amount requirements

## License

GPL v2 or later

## Support

For support, please contact your site administrator.
