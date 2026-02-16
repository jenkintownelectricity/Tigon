<?php
/**
 * Plugin Name: Tigon WooCommerce Add-Ons
 * Plugin URI: https://tigongolfcarts.com
 * Description: Manufacturer and model-based WooCommerce product add-ons for Tigon Golf Carts. Configures add-on options (accessories, upgrades, packages) dynamically based on the assigned manufacturer and model taxonomy terms.
 * Version: 1.0.0
 * Author: Tigon Golf Carts Engineering
 * Author URI: https://tigongolfcarts.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * Text Domain: tigon-woo-addons
 *
 * == ADD-ON SYSTEM ==
 * Add-on groups are tied to manufacturer + model combinations.
 * When a product is assigned to a manufacturer/model, the matching
 * add-ons automatically appear on the product page.
 *
 * Add-on types: checkbox, select, radio, text, number, color
 */

defined('ABSPATH') || exit;

define('TIGON_ADDONS_VERSION', '1.0.0');
define('TIGON_ADDONS_DIR', plugin_dir_path(__FILE__));
define('TIGON_ADDONS_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) return;

    require_once TIGON_ADDONS_DIR . 'includes/class-addon-registry.php';
    require_once TIGON_ADDONS_DIR . 'includes/class-addon-display.php';
    require_once TIGON_ADDONS_DIR . 'includes/class-addon-cart-handler.php';
    require_once TIGON_ADDONS_DIR . 'includes/class-addon-data.php';
});

register_activation_hook(__FILE__, function () {
    // Create add-on groups table
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $table = $wpdb->prefix . 'tigon_addon_groups';
    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT,
        group_name VARCHAR(255) NOT NULL,
        manufacturer_slug VARCHAR(255),
        model_slug VARCHAR(255),
        applies_to VARCHAR(50) DEFAULT 'all',
        priority INT(5) DEFAULT 10,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME,
        PRIMARY KEY (id),
        KEY idx_manufacturer (manufacturer_slug),
        KEY idx_model (model_slug)
    ) {$charset};";

    $addons_table = $wpdb->prefix . 'tigon_addons';
    $sql .= "CREATE TABLE {$addons_table} (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT,
        group_id BIGINT(20) UNSIGNED NOT NULL,
        addon_name VARCHAR(255) NOT NULL,
        addon_type VARCHAR(50) NOT NULL DEFAULT 'checkbox',
        addon_price DECIMAL(10,2) DEFAULT 0.00,
        addon_description TEXT,
        addon_options TEXT,
        is_required TINYINT(1) DEFAULT 0,
        sort_order INT(5) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        PRIMARY KEY (id),
        KEY idx_group (group_id)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Seed default add-on groups
    if (!get_option('tigon_addons_seeded')) {
        require_once TIGON_ADDONS_DIR . 'includes/class-addon-data.php';
        Tigon_Addon_Data::seed_defaults();
        update_option('tigon_addons_seeded', true);
    }
});
