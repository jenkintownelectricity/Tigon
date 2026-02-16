<?php
/**
 * Plugin Name:       Tigon Taxonomy
 * Plugin URI:        https://tigongolfcarts.com
 * Description:       Restructures WooCommerce taxonomy for Tigon Golf Carts. Registers manufacturer/model category hierarchy, global product attributes, product tags, the tigon_manufacturer CPT, and tigon_location taxonomy. Overrides primary display category to MODEL.
 * Version:           1.0.0
 * Author:            Lefebvre Design Solutions LLC
 * Author URI:        https://jenkintownelectricity.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tigon-taxonomy
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TIGON_TAXONOMY_VERSION', '1.0.0' );
define( 'TIGON_TAXONOMY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TIGON_TAXONOMY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TIGON_TAXONOMY_PLUGIN_FILE', __FILE__ );

/**
 * Autoload plugin classes.
 */
function tigon_taxonomy_autoload() {
    $classes = array(
        'Tigon_Categories'    => 'class-tigon-categories.php',
        'Tigon_Attributes'    => 'class-tigon-attributes.php',
        'Tigon_Tags'          => 'class-tigon-tags.php',
        'Tigon_CPT'           => 'class-tigon-cpt.php',
        'Tigon_Location'      => 'class-tigon-location.php',
        'Tigon_Primary_Cat'   => 'class-tigon-primary-cat.php',
        'Tigon_Breadcrumbs'   => 'class-tigon-breadcrumbs.php',
        'Tigon_Widgets'       => 'class-tigon-widgets.php',
    );

    foreach ( $classes as $class => $file ) {
        $path = TIGON_TAXONOMY_PLUGIN_DIR . 'includes/' . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

/**
 * Initialize plugin on plugins_loaded to ensure WooCommerce is available.
 */
function tigon_taxonomy_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'tigon_taxonomy_wc_missing_notice' );
        return;
    }

    tigon_taxonomy_autoload();

    // Initialize components.
    Tigon_CPT::init();
    Tigon_Location::init();
    Tigon_Primary_Cat::init();
    Tigon_Breadcrumbs::init();

    // Register widget.
    add_action( 'widgets_init', array( 'Tigon_Widgets', 'register' ) );

    // Enqueue front-end assets.
    add_action( 'wp_enqueue_scripts', 'tigon_taxonomy_enqueue_assets' );
}
add_action( 'plugins_loaded', 'tigon_taxonomy_init' );

/**
 * Admin notice when WooCommerce is not active.
 */
function tigon_taxonomy_wc_missing_notice() {
    echo '<div class="error"><p><strong>Tigon Taxonomy</strong> requires WooCommerce to be installed and active.</p></div>';
}

/**
 * Enqueue front-end CSS and JS.
 */
function tigon_taxonomy_enqueue_assets() {
    if ( is_active_widget( false, false, 'tigon_manufacturer_filter', true ) || is_shop() || is_product_taxonomy() ) {
        wp_enqueue_style(
            'tigon-filters',
            TIGON_TAXONOMY_PLUGIN_URL . 'assets/css/tigon-filters.css',
            array(),
            TIGON_TAXONOMY_VERSION
        );
        wp_enqueue_script(
            'tigon-filters',
            TIGON_TAXONOMY_PLUGIN_URL . 'assets/js/tigon-filters.js',
            array( 'jquery' ),
            TIGON_TAXONOMY_VERSION,
            true
        );
    }
}

/**
 * Plugin activation.
 */
function tigon_taxonomy_activate() {
    tigon_taxonomy_autoload();

    // Register CPT and taxonomy first so rewrite rules are generated.
    Tigon_CPT::register_post_type();
    Tigon_Location::register_taxonomy();

    // Seed categories, attributes, tags, and location terms.
    Tigon_Categories::seed();
    Tigon_Attributes::seed();
    Tigon_Tags::seed();
    Tigon_Location::seed();

    // Set version option for migration tracking.
    update_option( '_tigon_taxonomy_version', TIGON_TAXONOMY_VERSION );

    // Flush rewrite rules once.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tigon_taxonomy_activate' );

/**
 * Plugin deactivation.
 */
function tigon_taxonomy_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'tigon_taxonomy_deactivate' );
