# Tigon Taxonomy Plugin

**Version:** 1.0.0
**Requires WordPress:** 6.0+
**Requires PHP:** 7.4+
**Requires WooCommerce:** 7.0+
**Author:** Lefebvre Design Solutions LLC / Jenkintown Electricity

## Overview

The Tigon Taxonomy plugin restructures the WooCommerce product taxonomy for Tigon Golf Carts (tigongolfcarts.com). It establishes a proper **Manufacturer > Model** category hierarchy where **MODEL is the primary display category** on all product pages and archives.

### What This Plugin Does

1. **Product Categories**: Creates a hierarchical Manufacturer > Model category structure (13 manufacturers, 38+ models)
2. **Product Attributes**: Registers 9 global WooCommerce product attributes (Manufacturer, Seating Capacity, Voltage, Vehicle Type, Power Type, Drive Type, Color, Condition, Features)
3. **Product Tags**: Seeds 21 default product tags across 4 groups (Location, Promo/Status, Use Case, Compliance)
4. **Manufacturer CPT**: Registers a `tigon_manufacturer` custom post type for rich manufacturer profiles
5. **Dealership Locations**: Registers a `tigon_location` taxonomy for multi-location inventory filtering
6. **Primary Category Override**: Ensures MODEL (deepest child category) is the primary display category; auto-inherits parent manufacturer
7. **Breadcrumbs**: Overrides WooCommerce breadcrumbs to render: Home > Manufacturer > Model > Product Name
8. **Filter Widget**: Accordion-style manufacturer/model browser widget for sidebars

## Installation

### Method 1: Upload via WordPress Admin

1. Download or clone this repository
2. Zip the `wp-content/plugins/tigon-taxonomy/` directory
3. Go to **Plugins > Add New > Upload Plugin** in WordPress admin
4. Upload the zip file and click **Install Now**
5. Click **Activate Plugin**

### Method 2: Manual Upload

1. Upload the `tigon-taxonomy/` directory to `/wp-content/plugins/` on your server
2. Go to **Plugins** in WordPress admin
3. Find "Tigon Taxonomy" and click **Activate**

### Method 3: WP-CLI

```bash
# If plugin files are already on the server:
wp plugin activate tigon-taxonomy

# To install from a zip:
wp plugin install /path/to/tigon-taxonomy.zip --activate
```

### What Happens on Activation

The plugin automatically:
- Creates all manufacturer parent categories and model child categories
- Registers 9 global WooCommerce product attributes with default terms
- Creates 21 default product tags
- Registers the `tigon_manufacturer` custom post type
- Registers the `tigon_location` taxonomy with 4 default locations
- Flushes rewrite rules

**Idempotent**: Running activation multiple times will not create duplicate terms.

## Verification

After activation, verify the taxonomy was created correctly:

### WP-CLI Commands

```bash
# List all product categories (hierarchical)
wp term list product_cat --fields=term_id,name,slug,parent --format=table

# Check manufacturer parent categories
wp term list product_cat --parent=0 --fields=name,slug --format=table

# Check models for a specific manufacturer (e.g., Evolution)
wp term list product_cat --parent=$(wp term get product_cat evolution --field=term_id) --fields=name,slug --format=table

# List all WooCommerce attributes
wp wc product_attribute list --user=1 --format=table

# List attribute terms (e.g., for pa_vehicle-type)
wp term list pa_vehicle-type --fields=name,slug --format=table

# Check custom taxonomy terms
wp term list tigon_location --fields=name,slug --format=table

# Verify CPT is registered
wp post-type get tigon_manufacturer --fields=name,label,public,has_archive

# Check plugin version option
wp option get _tigon_taxonomy_version
```

### Admin Verification

1. Go to **Products > Categories** — you should see 13 manufacturer parents with nested models
2. Go to **Products > Attributes** — you should see 9 global attributes
3. Go to **Products > Tags** — you should see 21 default tags
4. Go to **Manufacturers** in the admin menu — the CPT should be registered
5. Edit any product — you should see a **Dealership Locations** checkbox panel

## Adding New Manufacturers or Models

### Via taxonomy-seed.json

1. Edit `wp-content/plugins/tigon-taxonomy/data/taxonomy-seed.json`
2. Add the new manufacturer or model following the existing JSON structure:
   ```json
   {
     "name": "New Brand®",
     "slug": "new-brand",
     "description": "Description of the brand.",
     "models": [
       { "name": "Model X", "slug": "model-x" },
       { "name": "Model Y", "slug": "model-y" }
     ]
   }
   ```
3. Deactivate and reactivate the plugin, OR run:
   ```bash
   wp eval 'require_once WP_PLUGIN_DIR . "/tigon-taxonomy/tigon-taxonomy.php"; tigon_taxonomy_activate();'
   ```

### Via WordPress Admin

You can also add categories manually through **Products > Categories** in the WordPress admin. Set the parent to the appropriate manufacturer when adding a model category.

## URL Structure

- **Product archives**: `/product-category/{manufacturer}/{model}/`
- **Single products**: `/inventory/{product-slug}/` (uses existing WooCommerce permalink base)
- **Manufacturer profiles**: `/manufacturers/{manufacturer-slug}/`
- **Manufacturer archive**: `/manufacturers/`
- **Location filter**: `/location/{location-slug}/`

## Breadcrumbs

On product pages: **Home > Manufacturer > Model > Product Name**
On model archives: **Home > Manufacturer > Model**
On manufacturer archives: **Home > Manufacturer**

## Filter Widget

Add the **Tigon: Manufacturer & Model Filter** widget to any sidebar via **Appearance > Widgets**. Settings:

- **Title**: Widget heading (default: "Browse by Manufacturer")
- **Hide empty categories**: Toggle to hide manufacturers/models with no products

## File Structure

```
tigon-taxonomy/
├── tigon-taxonomy.php              # Main plugin file
├── includes/
│   ├── class-tigon-categories.php  # WC category registration
│   ├── class-tigon-attributes.php  # WC attribute registration
│   ├── class-tigon-tags.php        # Product tag seeding
│   ├── class-tigon-cpt.php         # Manufacturer CPT
│   ├── class-tigon-location.php    # Location taxonomy
│   ├── class-tigon-primary-cat.php # Primary category override
│   ├── class-tigon-breadcrumbs.php # Breadcrumb override
│   └── class-tigon-widgets.php     # Filter widget
├── templates/
│   ├── single-tigon_manufacturer.php
│   └── archive-tigon_manufacturer.php
├── assets/
│   ├── css/tigon-filters.css
│   └── js/tigon-filters.js
├── data/
│   └── taxonomy-seed.json          # Single source of truth
└── README.md
```

## Troubleshooting

### Categories not appearing

- Ensure WooCommerce is active before activating this plugin
- Try deactivating and reactivating the plugin
- Check that `taxonomy-seed.json` is valid JSON (use `python3 -m json.tool data/taxonomy-seed.json`)

### Breadcrumbs not showing correctly

- The breadcrumb override hooks into `woocommerce_get_breadcrumb` at priority 20
- If another plugin overrides breadcrumbs at a higher priority, there may be conflicts
- Check that products have both a manufacturer (parent) and model (child) category assigned

### Permalinks returning 404

- Go to **Settings > Permalinks** and click **Save Changes** to flush rewrite rules
- Or run: `wp rewrite flush`

### Location taxonomy not showing on products

- Ensure the plugin is activated and the `tigon_location` taxonomy is registered
- If you don't see the Locations meta box, check for JavaScript errors in the admin

### Widget not displaying

- Ensure the widget is added to an active sidebar
- Check that manufacturer categories exist (parent = 0 in product_cat)
- If "Hide empty" is checked, only manufacturers with products will show

## Technical Notes

- **Single source of truth**: `taxonomy-seed.json` defines all categories, attributes, tags, and locations
- **Idempotent activation**: Running activation multiple times produces identical state (IV.01)
- **No destructive operations**: The plugin never deletes existing categories, products, or terms
- **Theme-agnostic**: Works with any WooCommerce-compatible theme
- **Template override**: Copy templates to your theme root to customize

## L0 Command Reference

This plugin was built per **L0-CMD-2026-0216-001** issued by Armand Lefebvre (Lefebvre Design Solutions LLC) for Tigon Golf Carts. Progress tracking is in `.validkernel/L0-CMD-2026-0216-001/`.
