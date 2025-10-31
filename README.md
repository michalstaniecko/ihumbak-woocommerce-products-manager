# iHumbak WooCommerce Products Manager

Advanced WooCommerce products management plugin with filtering and bulk price editing capabilities.

## Features

- **Clear Products Table**: Easy-to-read table displaying all your WooCommerce products
- **Advanced Filtering**: Filter products by:
  - Category
  - Search term
  - Order by (Name, Date, ID)
  - Sort direction (Ascending/Descending)
- **Asynchronous Loading**: Fast, responsive interface built with Vue.js 3
- **Efficient Product Loading**: 
  - Load More functionality for handling large product catalogs (20k+ products)
  - Shows loaded vs total product count
  - Request cancellation to prevent conflicts
- **Price Management**:
  - Edit regular prices
  - Edit sale prices
  - Single product editing
  - Bulk price updates for filtered products
- **Bulk Price Operations**:
  - Percentage-based changes (e.g., +10%, -5%)
  - Fixed amount changes
  - Apply to all filtered products (including non-visible ones)

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher

## Installation

1. Upload the plugin files to `/wp-content/plugins/ihumbak-woocommerce-products-manager/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Products Manager' in the WordPress admin menu

## Usage

### Filtering Products

1. Navigate to **Products Manager** in the WordPress admin menu
2. Use the filter options at the top:
   - **Search**: Type to search products by name
   - **Category**: Select a product category
   - **Order by**: Choose how to sort products
   - **Order**: Choose ascending or descending order

### Working with Large Product Catalogs

The plugin is optimized to handle shops with thousands of products:

1. Products are loaded in batches (50 at a time by default)
2. Use the **Load More Products** button to load additional products
3. The interface shows how many products are loaded vs. the total matching your filters
4. Bulk operations work on ALL filtered products, not just the ones currently displayed

### Editing Individual Product Prices

1. Find the product in the table
2. Edit the **Regular Price** or **Sale Price** fields directly
3. Changes are saved automatically when you modify the field

### Bulk Price Updates

1. Filter products to the desired set (optional)
2. In the "Bulk Price Update" section:
   - Select **Price Type** (Regular or Sale)
   - Select **Change Type** (Percentage or Fixed Amount)
   - Enter the **Value** (e.g., 10 for +10%, -5 for -5%)
3. Click **Apply to Filtered Products**
4. Confirm the action

**Important**: The bulk update will apply to ALL products matching your current filters, not just the ones currently visible on the screen. This ensures consistent updates across your entire product catalog.

## Development

### File Structure

```
ihumbak-woocommerce-products-manager/
├── assets/
│   ├── css/
│   │   └── admin.css          # Admin interface styles
│   └── js/
│       └── admin.js           # Vue.js application
├── includes/
│   ├── admin-page.php         # Admin page template
│   └── class-products-handler.php  # Products data handler
├── ihumbak-woocommerce-products-manager.php  # Main plugin file
└── README.md
```

## License

GPL v2 or later

## Author

Michał Stanięcko
- GitHub: [@michalstaniecko](https://github.com/michalstaniecko)

## Changelog

### 1.1.0
- Added "Load More" functionality for handling large product catalogs (20k+ products)
- Improved performance with request cancellation and debouncing
- Increased default products per page from 20 to 50
- Enhanced bulk update confirmation message to clarify it affects all filtered products
- Added product count display (loaded vs total)
- Updated UI to show loading states for "Load More" button

### 1.0.0
- Initial release
- Products table with filtering
- Category and taxonomy filtering
- Asynchronous data loading with Vue.js
- Single product price editing
- Bulk price updates with percentage and fixed changes
