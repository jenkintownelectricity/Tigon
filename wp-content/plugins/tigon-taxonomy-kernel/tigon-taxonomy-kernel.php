<?php
/**
 * Plugin Name: Tigon Taxonomy Kernel
 * Plugin URI: https://tigongolfcarts.com
 * Description: 50-layer deep hierarchical taxonomy engine for golf cart DNA classification. Registers all custom taxonomies, post types, WooCommerce attributes, and the complete manufacturer > model > spec matrix for Tigon Golf Carts.
 * Version: 1.0.0
 * Author: Tigon Golf Carts Engineering
 * Author URI: https://tigongolfcarts.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * Text Domain: tigon-taxonomy-kernel
 *
 * == ARCHITECTURE ==
 * 50-Layer Taxonomy Depth: Manufacturer > Model Family > Model > Model Year > Trim Level >
 * Body Style > Seating Config > Powertrain > Battery System > Motor > Controller >
 * Drivetrain > Suspension > Braking > Steering > Frame > Chassis > Wheels > Tires >
 * Lighting > Sound System > Comfort > Safety > Street Legal Package > Accessories >
 * Color Exterior > Color Interior > Color Accents > Upholstery > Canopy/Top >
 * Windshield > Storage > Cargo > Hitch System > Lift Kit > Fender > Guard/Bumper >
 * Mirror > Signal > Horn > Charging > Warranty > Certification > Compliance >
 * Location > Condition > Inventory Status > Price Tier > Financing > Shipping Zone >
 * Service History > Owner History > DNA Hash
 */

defined('ABSPATH') || exit;

define('TIGON_KERNEL_VERSION', '1.0.0');
define('TIGON_KERNEL_DIR', plugin_dir_path(__FILE__));
define('TIGON_KERNEL_URL', plugin_dir_url(__FILE__));

/* ============================================
   AUTOLOADER
   ============================================ */
spl_autoload_register(function ($class) {
    $prefix = 'Tigon\\Kernel\\';
    if (strpos($class, $prefix) !== 0) return;
    $relative_class = substr($class, strlen($prefix));
    $file = TIGON_KERNEL_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

/* ============================================
   BOOT
   ============================================ */
add_action('plugins_loaded', function () {
    // Load core includes
    require_once TIGON_KERNEL_DIR . 'includes/class-taxonomy-registry.php';
    require_once TIGON_KERNEL_DIR . 'includes/class-attribute-registry.php';
    require_once TIGON_KERNEL_DIR . 'includes/class-post-type-registry.php';
    require_once TIGON_KERNEL_DIR . 'includes/class-taxonomy-seeder.php';
    require_once TIGON_KERNEL_DIR . 'includes/class-dna-hash.php';

    if (is_admin()) {
        require_once TIGON_KERNEL_DIR . 'admin/class-kernel-admin.php';
    }
});

/* ============================================
   ACTIVATION
   ============================================ */
register_activation_hook(__FILE__, function () {
    require_once TIGON_KERNEL_DIR . 'includes/class-taxonomy-registry.php';
    require_once TIGON_KERNEL_DIR . 'includes/class-attribute-registry.php';
    require_once TIGON_KERNEL_DIR . 'includes/class-post-type-registry.php';
    require_once TIGON_KERNEL_DIR . 'includes/class-taxonomy-seeder.php';

    Tigon_Taxonomy_Registry::register_all();
    Tigon_Attribute_Registry::register_all();
    Tigon_Post_Type_Registry::register_all();

    flush_rewrite_rules();

    // Seed default terms
    Tigon_Taxonomy_Seeder::seed_all();

    update_option('tigon_kernel_version', TIGON_KERNEL_VERSION);
    update_option('tigon_kernel_activated', time());
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
