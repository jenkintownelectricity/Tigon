<?php
/**
 * Plugin Name: Tigon Taxonomy
 * Plugin URI: https://tigongolfcarts.com
 * Description: WooCommerce taxonomy restructuring for Tigon Golf Carts. Registers manufacturer/model category hierarchy, global product attributes, product tags, the tigon_manufacturer CPT, and the tigon_location dealership taxonomy. MODEL is the primary display category.
 * Version: 1.0.0
 * Author: Lefebvre Design Solutions LLC / Jenkintown Electricity
 * Author URI: https://jenkintownelectricity.com
 * Text Domain: tigon-taxonomy
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 *
 * L0-CMD-2026-0216-001 — ValidKernel Deterministic Authority
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
        'Tigon_Categories'   => 'class-tigon-categories.php',
        'Tigon_Attributes'   => 'class-tigon-attributes.php',
        'Tigon_Tags'         => 'class-tigon-tags.php',
        'Tigon_CPT'          => 'class-tigon-cpt.php',
        'Tigon_Location'     => 'class-tigon-location.php',
        'Tigon_Primary_Cat'  => 'class-tigon-primary-cat.php',
        'Tigon_Breadcrumbs'  => 'class-tigon-breadcrumbs.php',
        'Tigon_Widgets'      => 'class-tigon-widgets.php',
    );

    foreach ( $classes as $class => $file ) {
        $filepath = TIGON_TAXONOMY_PLUGIN_DIR . 'includes/' . $file;
        if ( file_exists( $filepath ) ) {
            require_once $filepath;
        }
    }
}

/**
 * Initialize the plugin.
 */
function tigon_taxonomy_init() {
    tigon_taxonomy_autoload();

    // Register CPT and taxonomies early.
    if ( class_exists( 'Tigon_CPT' ) ) {
        $cpt = new Tigon_CPT();
        $cpt->register();
    }

    if ( class_exists( 'Tigon_Location' ) ) {
        $location = new Tigon_Location();
        $location->register();
    }

    // Primary category override.
    if ( class_exists( 'Tigon_Primary_Cat' ) ) {
        $primary_cat = new Tigon_Primary_Cat();
        $primary_cat->register();
    }

    // Breadcrumb override.
    if ( class_exists( 'Tigon_Breadcrumbs' ) ) {
        $breadcrumbs = new Tigon_Breadcrumbs();
        $breadcrumbs->register();
    }

    // Filter widget.
    if ( class_exists( 'Tigon_Widgets' ) ) {
        add_action( 'widgets_init', array( 'Tigon_Widgets', 'register_widgets' ) );
    }
}
add_action( 'init', 'tigon_taxonomy_init', 5 );

/**
 * Enqueue front-end assets.
 */
function tigon_taxonomy_enqueue_assets() {
    if ( is_post_type_archive( 'product' ) || is_tax( 'product_cat' ) || is_tax( 'tigon_location' ) || is_active_widget( false, false, 'tigon_manufacturer_filter' ) ) {
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
        wp_localize_script( 'tigon-filters', 'tigonFilters', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'tigon_filter_nonce' ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'tigon_taxonomy_enqueue_assets' );

/**
 * Plugin activation.
 */
function tigon_taxonomy_activate() {
    tigon_taxonomy_autoload();

    // 1. Register CPT and taxonomies so rewrite rules work.
    if ( class_exists( 'Tigon_CPT' ) ) {
        $cpt = new Tigon_CPT();
        $cpt->register();
    }
    if ( class_exists( 'Tigon_Location' ) ) {
        $location = new Tigon_Location();
        $location->register();
    }

    // 2. Seed categories.
    if ( class_exists( 'Tigon_Categories' ) ) {
        Tigon_Categories::seed();
    }

    // 3. Register attributes and seed terms.
    if ( class_exists( 'Tigon_Attributes' ) ) {
        Tigon_Attributes::seed();
    }

    // 4. Seed tags.
    if ( class_exists( 'Tigon_Tags' ) ) {
        Tigon_Tags::seed();
    }

    // 5. Seed location terms.
    if ( class_exists( 'Tigon_Location' ) ) {
        Tigon_Location::seed();
    }

    // 6. Set version option.
    update_option( '_tigon_taxonomy_version', TIGON_TAXONOMY_VERSION );

    // 7. Flush rewrite rules once.
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
