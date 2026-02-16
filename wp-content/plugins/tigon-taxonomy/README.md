# Tigon Taxonomy Plugin

WooCommerce taxonomy restructure for **Tigon Golf Carts** (tigongolfcarts.com).

Registers a complete manufacturer/model category hierarchy, global product attributes, product tags, a custom post type for manufacturer profiles, and a dealership location taxonomy. Sets **MODEL** as the primary display category on all product pages and archives.

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

## Installation

1. Copy the `tigon-taxonomy` folder to `wp-content/plugins/` on your WordPress site.
2. Log in to WordPress Admin.
3. Go to **Plugins > Installed Plugins**.
4. Find **Tigon Taxonomy** and click **Activate**.

On activation the plugin will automatically:
- Create all manufacturer parent categories and model child categories
- Register 9 global WooCommerce product attributes with default terms
- Create default product tags (Location, Promo, Use Case, Compliance)
- Register the `tigon_manufacturer` custom post type
- Register the `tigon_location` (Dealership Location) taxonomy
- Seed 4 default location terms (Hatfield PA, Poconos PA, Ocean View NJ, National)
- Flush rewrite rules

## What It Does

### Category Hierarchy (Manufacturer > Model)

Products are organized as:
```
Evolution® (parent)
├── Forester 4 Plus (child — PRIMARY display)
├── Forester 6 Plus
├── Classic 4 Plus
├── ...
ICON® (parent)
├── i20
├── i40
├── ...
```

**MODEL is always the primary displayed category.** When you assign a model category to a product, the parent manufacturer category is automatically assigned as well.

### Product Attributes

| Attribute | Slug | Example Terms |
|-----------|------|---------------|
| Manufacturer | pa_manufacturer | Evolution, ICON, EZ-GO, Club Car, Yamaha, etc. |
| Seating Capacity | pa_seating | 2 Seater, 4 Seater, 6 Seater, 8 Seater |
| Voltage | pa_voltage | 36V, 48V, 72V |
| Vehicle Type | pa_vehicle-type | NEV, LSV, MSV, PTV, ZEV, UTV, Golf Cart |
| Power Type | pa_power-type | Electric, Gas, Lithium, Lead Acid |
| Drive Type | pa_drive-type | 2x4, 4x4 |
| Color | pa_color | Black, White, Red, Blue, Gray, Green, + more |
| Condition | pa_condition | New, Used, Certified Pre-Owned |
| Features | pa_features | Street Legal, Lifted, Bluetooth, Touchscreen, etc. |

### Breadcrumbs

WooCommerce breadcrumbs are overridden to display:

```
Home > Manufacturer > Model > Product Name
```

### Dealership Location Taxonomy

Every product **must** have a dealership location assigned before it can be published. Available locations:
- Hatfield PA
- Poconos PA
- Ocean View NJ
- National

Products published without a location are automatically reverted to draft with an admin notice.

### Manufacturer Filter Widget

A sidebar widget that displays manufacturers as expandable accordion items:
- Click a manufacturer name to view their category archive
- Click the +/- toggle to expand and see model links
- Product counts shown per manufacturer and model
- Active category highlighting

To add the widget: **Appearance > Widgets > Add "Tigon: Manufacturer/Model Filter"** to your Shop Sidebar.

### Manufacturer Profiles (CPT)

The `tigon_manufacturer` post type provides rich manufacturer profile pages at `/manufacturers/`. Each profile includes:
- Brand logo
- Website URL
- Tagline
- Link to WooCommerce product category
- Model grid from linked category
- Featured inventory products

## WP-CLI Verification

After activation, verify the taxonomy was created correctly:

```bash
# List all manufacturer (top-level) product categories
wp term list product_cat --parent=0 --fields=term_id,name,slug,count

# List all models under Evolution
wp term list product_cat --fields=term_id,name,slug,parent --parent=$(wp term get product_cat evolution --field=term_id)

# List all global product attributes
wp wc product_attribute list --user=1

# List attribute terms for a specific attribute (e.g., vehicle-type)
wp term list pa_vehicle-type --fields=term_id,name,slug

# List all dealership locations
wp term list tigon_location --fields=term_id,name,slug

# List all product tags
wp term list product_tag --fields=term_id,name,slug

# Verify manufacturer CPT is registered
wp post-type get tigon_manufacturer

# Check plugin version
wp option get _tigon_taxonomy_version
```

## Adding New Manufacturers or Models

1. Edit `wp-content/plugins/tigon-taxonomy/data/taxonomy-seed.json`
2. Add the new manufacturer or model following the existing JSON structure:
   ```json
   {
     "name": "New Brand®",
     "slug": "new-brand",
     "description": "Description here.",
     "models": [
       { "name": "Model X", "slug": "model-x" },
       { "name": "Model Y", "slug": "model-y" }
     ]
   }
   ```
3. Deactivate and re-activate the plugin, **OR** run:
   ```bash
   wp eval 'Tigon_Categories::seed();'
   ```

The seeder is idempotent — it will only create terms that don't already exist. It will never delete or modify existing terms.

## File Structure

```
tigon-taxonomy/
├── tigon-taxonomy.php              # Main plugin file
├── includes/
│   ├── class-tigon-categories.php  # WC category registration
│   ├── class-tigon-attributes.php  # WC global attribute registration
│   ├── class-tigon-tags.php        # Product tag registration
│   ├── class-tigon-cpt.php         # Manufacturer CPT
│   ├── class-tigon-location.php    # Dealership location taxonomy
│   ├── class-tigon-primary-cat.php # Primary category override (MODEL)
│   ├── class-tigon-breadcrumbs.php # Breadcrumb override
│   └── class-tigon-widgets.php     # Manufacturer/Model filter widget
├── templates/
│   ├── single-tigon_manufacturer.php
│   └── archive-tigon_manufacturer.php
├── assets/
│   ├── css/tigon-filters.css
│   └── js/tigon-filters.js
├── data/
│   └── taxonomy-seed.json          # Source of truth for all taxonomy data
└── README.md
```

## Troubleshooting

### Categories not appearing after activation
- Ensure WooCommerce is installed and active **before** activating Tigon Taxonomy.
- Check for PHP errors in `wp-content/debug.log` (enable `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`).
- Try deactivating and reactivating the plugin.

### Breadcrumbs not showing correctly
- The breadcrumb override hooks into `woocommerce_get_breadcrumb` at priority 20. If another plugin or theme hooks at a higher priority, it may override the Tigon breadcrumbs.
- Check that your theme uses `woocommerce_breadcrumb()` for breadcrumb output.

### Products reverting to draft
- This is by design. Every product must have a **Dealership Location** assigned. Check the Location checkbox panel on the product edit screen.

### Widget not appearing
- Go to **Appearance > Widgets** and add the "Tigon: Manufacturer/Model Filter" widget to a sidebar.
- Ensure the sidebar is displayed on shop/archive pages in your theme.

### Permalinks returning 404
- Go to **Settings > Permalinks** and click **Save Changes** (no changes needed — this flushes rewrite rules).

## Version

- **1.0.0** — Initial release. Full taxonomy restructure per L0-CMD-2026-0216-001.

## Author

Lefebvre Design Solutions LLC / Jenkintown Electricity
