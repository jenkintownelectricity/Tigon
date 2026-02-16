# Tigon Taxonomy Plugin

**Version:** 1.0.0
**Requires WordPress:** 5.8+
**Requires PHP:** 7.4+
**Requires WooCommerce:** 6.0+
**Author:** Lefebvre Design Solutions LLC

## Overview

The Tigon Taxonomy plugin restructures the WooCommerce product taxonomy for Tigon Golf Carts (tigongolfcarts.com). It establishes a proper Manufacturer > Model category hierarchy where **MODEL is the primary display category**, registers global product attributes, product tags, a Manufacturer custom post type, and a Dealership Location taxonomy.

## Installation

1. Upload the `tigon-taxonomy` folder to `/wp-content/plugins/` on your WordPress server.
2. Activate the plugin through the **Plugins** menu in WordPress admin.
3. On activation, the plugin will automatically:
   - Create all manufacturer parent categories and model child categories
   - Register 9 global product attributes with default terms
   - Create default product tags
   - Register the `tigon_manufacturer` custom post type
   - Register the `tigon_location` taxonomy
   - Flush rewrite rules

### Manual Upload

```bash
# From your WordPress root directory
cp -r tigon-taxonomy/ wp-content/plugins/
```

Then activate via WP Admin > Plugins.

## What the Plugin Creates

### Product Categories (Hierarchical)

13 manufacturer parent categories, each with model child categories (38 models total):

| Manufacturer | Models |
|---|---|
| Evolution® | Forester 4 Plus, Forester 6 Plus, Classic 4 Plus, Classic 4 Pro, Carrier 6 Plus, D3, D5 Ranger 4, D5 Maverick 4, Turfman 800, Turfman 1000 |
| ICON® | i20, i40, i40L, i60, i60L |
| EZ-GO® | Liberty, Freedom, Express S4, Express S6, Valor, Flex |
| Club Car® | Onward, Precedent, Villager |
| Yamaha® | Drive2, UMAX Rally, Adventurer |
| DENAGO® EV | Nomad, Gran Turismo |
| Royal® EV | Royal EV Models |
| Swift® EV | Swift EV Models |
| EPIC® Carts | E40, E60 |
| STAR EV® | Star EV Models |
| Bintelli® | Beyond, Nemesis |
| Cushman® | Hauler |
| TIGON® | TIGON Custom Builds |

### Global Product Attributes (pa_ prefix)

| Attribute | Terms | Variations? |
|---|---|---|
| Manufacturer | 13 brands | No |
| Seating Capacity | 2/4/6/8 Seater | Yes |
| Voltage | 36V, 48V, 72V | No |
| Vehicle Type | NEV, LSV, MSV, PTV, ZEV, UTV, Golf Cart | No |
| Power Type | Electric, Gas, Lithium, Lead Acid | Yes |
| Drive Type | 2x4, 4x4 | No |
| Color | 12 colors | Yes |
| Condition | New, Used, Certified Pre-Owned | No |
| Features | Street Legal, Lifted, Bluetooth, etc. | No |

### Product Tags

21 tags across 4 groups: Location, Promo/Status, Use Case, Compliance.

### Custom Post Type: Manufacturer

- Post type: `tigon_manufacturer`
- Archive URL: `/manufacturers/`
- Fields: Brand Logo, Brand Website URL, Brand Tagline, Linked WC Category

### Custom Taxonomy: Dealership Location

- Taxonomy: `tigon_location`
- Attached to: WooCommerce products
- Terms: Hatfield PA, Poconos PA, Ocean View NJ, National
- **Required:** Products cannot be published without a location assignment.

## Key Behaviors

### Primary Category = MODEL

The deepest child category assigned to a product (the MODEL) is treated as the primary display category. When you assign "Forester 4 Plus" (child of "Evolution®"), the product page displays "Forester 4 Plus" as the primary category.

### Auto-Inherit Manufacturer

When you assign a model category to a product, the parent manufacturer category is automatically assigned as well. You only need to select the model.

### Breadcrumbs

On single product pages, breadcrumbs render as:

```
Home > Evolution® > Forester 4 Plus > Product Name
```

### Location Requirement

Products must have a Dealership Location assigned before they can be published. If no location is selected, the product is saved as a draft with an admin notice.

## WP-CLI Verification Commands

After activating the plugin, verify the taxonomy was created correctly:

```bash
# List all product categories (manufacturers and models)
wp term list product_cat --fields=term_id,name,slug,parent --format=table

# List only top-level manufacturer categories
wp term list product_cat --parent=0 --fields=term_id,name,slug --format=table

# List models for a specific manufacturer (replace PARENT_ID)
wp term list product_cat --parent=PARENT_ID --fields=term_id,name,slug --format=table

# Example: List Evolution models
wp term list product_cat --parent=$(wp term get product_cat $(wp term list product_cat --slug=evolution --field=term_id) --field=term_id) --fields=term_id,name,slug --format=table

# List all global product attributes
wp wc product_attribute list --format=table

# List dealership location terms
wp term list tigon_location --fields=term_id,name,slug --format=table

# List product tags
wp term list product_tag --fields=term_id,name,slug --format=table

# Verify the manufacturer CPT is registered
wp post-type get tigon_manufacturer

# Verify the location taxonomy is registered
wp taxonomy get tigon_location

# Check plugin version
wp option get _tigon_taxonomy_version
```

## Adding New Manufacturers or Models

1. Edit `data/taxonomy-seed.json` in the plugin directory.
2. Add a new manufacturer object to the `manufacturers` array, following the existing pattern:
   ```json
   {
     "name": "New Brand®",
     "slug": "new-brand",
     "description": "Description of the brand.",
     "models": [
       { "name": "Model A", "slug": "model-a" },
       { "name": "Model B", "slug": "model-b" }
     ]
   }
   ```
3. Deactivate and reactivate the plugin, or run the activation hook:
   ```bash
   wp plugin deactivate tigon-taxonomy && wp plugin activate tigon-taxonomy
   ```
4. The new categories will be created without affecting existing ones (idempotent).

## Widgets

### Manufacturer/Model Filter Widget

Add the **Tigon Manufacturer/Model Filter** widget to any sidebar via Appearance > Widgets. It displays:

- Expandable accordion of manufacturers
- Nested model links under each manufacturer
- Product counts per manufacturer and per model
- Active state highlighting for the current category
- Configurable title and hide-empty option

## Template Overrides

The plugin provides templates for the manufacturer CPT that can be overridden in your theme:

- `single-tigon_manufacturer.php` — Single manufacturer page
- `archive-tigon_manufacturer.php` — Manufacturer archive page

Copy these files from the plugin's `templates/` directory to your theme root to customize.

## Troubleshooting

### Categories not appearing after activation

1. Go to **Settings > Permalinks** and click "Save Changes" to flush rewrite rules.
2. Verify WooCommerce is active — the plugin requires it.
3. Check that `data/taxonomy-seed.json` is present and valid JSON.

### Breadcrumbs not showing correctly

1. Ensure your theme uses WooCommerce's native breadcrumb function (`woocommerce_breadcrumb()`).
2. The plugin hooks `woocommerce_get_breadcrumb` at priority 20. If another plugin hooks at a higher priority, it may override.

### Products stuck in draft

If a product won't publish, check that a **Dealership Location** is selected. The plugin requires at least one location term on every product.

### Widget not showing manufacturers

1. Ensure manufacturers have been created as product categories (check Products > Categories in admin).
2. If "Hide empty categories" is checked in the widget settings, only manufacturers with products will show.

### 404 on manufacturer pages

Go to **Settings > Permalinks** and click "Save Changes" to regenerate rewrite rules.

## File Structure

```
tigon-taxonomy/
├── tigon-taxonomy.php              # Main plugin file
├── includes/
│   ├── class-tigon-categories.php  # WC category registration
│   ├── class-tigon-attributes.php  # WC attribute registration
│   ├── class-tigon-tags.php        # Product tag registration
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

## License

GPL-2.0+
