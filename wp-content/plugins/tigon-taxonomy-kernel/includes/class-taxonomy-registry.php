<?php
/**
 * Tigon Taxonomy Registry — 50-Layer Deep Golf Cart DNA Classification
 *
 * Registers all hierarchical and flat custom taxonomies for the complete
 * golf cart specification matrix. Each layer maps to a specific aspect
 * of the golf cart's DNA, from manufacturer down to service history.
 *
 * @package TigonTaxonomyKernel
 */

defined('ABSPATH') || exit;

class Tigon_Taxonomy_Registry {

    /**
     * Complete 50-layer taxonomy definition.
     * Each entry: slug => [label_singular, label_plural, hierarchical, layer_number, description]
     */
    public static function get_taxonomy_layers() {
        return [
            // === LAYER 1-5: IDENTITY ===
            'manufacturers' => ['Manufacturer', 'Manufacturers', true, 1, 'Cart manufacturer/brand (Denago, Epic, Club Car, etc.)'],
            'model-family' => ['Model Family', 'Model Families', true, 2, 'Product line family (Nomad Series, Rover Series, etc.)'],
            'models' => ['Model', 'Models', true, 3, 'Specific model name — PRIMARY CATEGORY'],
            'model-year' => ['Model Year', 'Model Years', true, 4, 'Production year'],
            'trim-level' => ['Trim Level', 'Trim Levels', true, 5, 'Trim package (Base, Sport, Premium, XL, etc.)'],

            // === LAYER 6-10: BODY & CONFIGURATION ===
            'body-style' => ['Body Style', 'Body Styles', true, 6, 'Body configuration (Open, Enclosed, Cab, Flatbed)'],
            'seating-config' => ['Seating Configuration', 'Seating Configurations', true, 7, 'Passenger capacity (2, 4, 6, 8 passenger)'],
            'powertrain-type' => ['Powertrain Type', 'Powertrain Types', true, 8, 'Electric, Gas, Hybrid, Solar-Assist'],
            'battery-system' => ['Battery System', 'Battery Systems', true, 9, 'Lithium, Lead-Acid, AGM, LiFePO4'],
            'motor-type' => ['Motor Type', 'Motor Types', true, 10, 'AC, DC, Brushless, Hub Motor'],

            // === LAYER 11-15: DRIVETRAIN & MECHANICAL ===
            'controller-type' => ['Controller', 'Controllers', true, 11, 'Motor controller brand/type'],
            'drivetrain' => ['Drivetrain', 'Drivetrains', true, 12, 'FWD, RWD, AWD, 4WD'],
            'suspension-type' => ['Suspension', 'Suspension Types', true, 13, 'Independent, Leaf Spring, Coilover, Air'],
            'braking-system' => ['Braking System', 'Braking Systems', true, 14, 'Disc, Drum, Regenerative, Hydraulic'],
            'steering-type' => ['Steering', 'Steering Types', true, 15, 'Rack and Pinion, Electric Power Steering'],

            // === LAYER 16-20: FRAME & STRUCTURE ===
            'frame-material' => ['Frame Material', 'Frame Materials', true, 16, 'Aircraft-grade Aluminum, Steel, Carbon Fiber'],
            'chassis-type' => ['Chassis', 'Chassis Types', true, 17, 'Monocoque, Ladder, Tubular'],
            'wheel-type' => ['Wheel Type', 'Wheel Types', true, 18, 'Alloy, Steel, Chrome, Forged'],
            'tire-type' => ['Tire Type', 'Tire Types', true, 19, 'All-Terrain, Street, Turf, Off-Road, Low-Profile'],
            'tire-rim-size' => ['Tire & Rim Size', 'Tire & Rim Sizes', true, 20, 'Wheel diameter (10", 12", 14", etc.)'],

            // === LAYER 21-25: ELECTRONICS & FEATURES ===
            'lighting-package' => ['Lighting Package', 'Lighting Packages', true, 21, 'LED, Halogen, Halo, Underglow, Demon Eyes'],
            'sound-systems' => ['Sound System', 'Sound Systems', true, 22, 'None, Soundbar, Full System, Premium'],
            'comfort-features' => ['Comfort Feature', 'Comfort Features', true, 23, 'Heated Seats, Fan System, Armrests, Cup Holders'],
            'safety-features' => ['Safety Feature', 'Safety Features', true, 24, 'Seat Belts, Roll Cage, Mirrors, Backup Camera'],
            'street-legal-package' => ['Street Legal Package', 'Street Legal Packages', true, 25, 'LSV Package, Turn Signals, Horn, DOT compliance'],

            // === LAYER 26-30: ACCESSORIES ===
            'added-features' => ['Added Feature', 'Added Features', true, 26, 'Brush Guard, Light Bar, Fender Flares, Under Glow'],
            'color-exterior' => ['Exterior Color', 'Exterior Colors', false, 27, 'Body/paint color'],
            'color-seat' => ['Seat Color', 'Seat Colors', false, 28, 'Seat/interior color'],
            'color-accent' => ['Accent Color', 'Accent Colors', false, 29, 'Trim accent color'],
            'upholstery-type' => ['Upholstery', 'Upholstery Types', true, 30, 'Vinyl, Leather, Marine-Grade, Premium'],

            // === LAYER 31-35: BODY ACCESSORIES ===
            'canopy-type' => ['Canopy/Top', 'Canopy Types', true, 31, 'Standard, Extended, Sunbrella, Hard Top'],
            'windshield-type' => ['Windshield', 'Windshield Types', true, 32, 'Folding, Fixed, Acrylic, Glass, Tinted'],
            'storage-options' => ['Storage Option', 'Storage Options', true, 33, 'Under-seat, Glove Box, Cargo Rack'],
            'cargo-type' => ['Cargo Configuration', 'Cargo Configurations', true, 34, 'Rear Bed, Flatbed, Basket, Caddie'],
            'hitch-system' => ['Hitch System', 'Hitch Systems', true, 35, '2" Receiver, Ball Mount, Tow Bar'],

            // === LAYER 36-40: UPGRADES ===
            'lift-kit' => ['Lift Kit', 'Lift Kits', true, 36, 'None, 3", 4", 6", Custom'],
            'fender-type' => ['Fender', 'Fender Types', true, 37, 'Standard, Flares, Extended, Carbon'],
            'guard-bumper' => ['Guard/Bumper', 'Guards & Bumpers', true, 38, 'Brush Guard, Bull Bar, Front Bumper, Rear Bumper'],
            'mirror-type' => ['Mirror', 'Mirror Types', true, 39, 'Side Mirrors, Rearview, Convex, Heated'],
            'signal-type' => ['Signal Equipment', 'Signal Equipment', true, 40, 'Turn Signals, Hazards, Brake Lights, Reverse'],

            // === LAYER 41-45: COMPLIANCE & STATUS ===
            'horn-type' => ['Horn', 'Horn Types', true, 41, 'Standard, Dual-Tone, Air Horn'],
            'charging-system' => ['Charging System', 'Charging Systems', true, 42, 'Standard Charger, Fast Charge, Onboard, Off-Board'],
            'warranty-tier' => ['Warranty', 'Warranty Tiers', true, 43, '1-Year, 2-Year, 5-Year, Lifetime Frame'],
            'certification' => ['Certification', 'Certifications', true, 44, 'DOT, NHTSA, UL, CE'],
            'compliance-class' => ['Compliance Class', 'Compliance Classes', true, 45, 'NEV, LSV, MSV, PTV, ZEV, UTV'],

            // === LAYER 46-50: BUSINESS & PROVENANCE ===
            'vehicle-class' => ['Vehicle Class', 'Vehicle Classes', true, 46, 'NEV, MSV, PTV, ZEV, UTV, LSV'],
            'location' => ['Dealership Location', 'Dealership Locations', true, 47, 'Physical store location'],
            'inventory-status' => ['Inventory Status', 'Inventory Statuses', false, 48, 'In Stock, Sold, On Order, In Transit'],
            'price-tier' => ['Price Tier', 'Price Tiers', true, 49, 'Entry, Mid-Range, Premium, Ultra-Premium'],
            'shipping-zone' => ['Shipping Zone', 'Shipping Zones', true, 50, 'Local, Regional, National, International'],
        ];
    }

    /**
     * Register all 50 taxonomy layers
     */
    public static function register_all() {
        $layers = self::get_taxonomy_layers();

        foreach ($layers as $slug => $config) {
            list($singular, $plural, $hierarchical, $layer_num, $description) = $config;
            self::register_taxonomy($slug, $singular, $plural, $hierarchical, $layer_num, $description);
        }
    }

    /**
     * Register a single taxonomy
     */
    private static function register_taxonomy($slug, $singular, $plural, $hierarchical, $layer, $description) {
        $labels = [
            'name'              => $plural,
            'singular_name'     => $singular,
            'search_items'      => "Search {$plural}",
            'all_items'         => "All {$plural}",
            'parent_item'       => $hierarchical ? "Parent {$singular}" : null,
            'parent_item_colon' => $hierarchical ? "Parent {$singular}:" : null,
            'edit_item'         => "Edit {$singular}",
            'update_item'       => "Update {$singular}",
            'add_new_item'      => "Add New {$singular}",
            'new_item_name'     => "New {$singular} Name",
            'menu_name'         => $plural,
        ];

        $args = [
            'labels'            => $labels,
            'description'       => "[Layer {$layer}/50] {$description}",
            'hierarchical'      => $hierarchical,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => ($layer <= 10), // Top 10 layers show in admin columns
            'show_in_nav_menus' => ($layer <= 15),
            'show_in_rest'      => true,
            'show_tagcloud'     => !$hierarchical,
            'query_var'         => true,
            'rewrite'           => [
                'slug'         => $slug,
                'with_front'   => false,
                'hierarchical' => $hierarchical,
            ],
            'meta_box_cb'       => $hierarchical ? null : 'post_tags_meta_box',
        ];

        register_taxonomy($slug, ['product'], $args);
    }
}

// Hook registration at init with priority 5 (before WooCommerce)
add_action('init', ['Tigon_Taxonomy_Registry', 'register_all'], 5);
