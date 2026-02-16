<?php
/**
 * Plugin Name: Tigon Taxonomy
 * Plugin URI: https://tigongolfcarts.com
 * Description: WooCommerce taxonomy restructure for Tigon Golf Carts. Registers manufacturer/model category hierarchy, global product attributes, product tags, custom post types, and dealership location taxonomy. Sets MODEL as the primary display category.
 * Version: 1.0.0
 * Author: Lefebvre Design Solutions LLC / Jenkintown Electricity
 * Author URI: https://jenkintownelectricity.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tigon-taxonomy
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TIGON_TAXONOMY_VERSION', '1.0.0' );
define( 'TIGON_TAXONOMY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TIGON_TAXONOMY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TIGON_TAXONOMY_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class.
 */
final class Tigon_Taxonomy {

    /**
     * Single instance.
     *
     * @var Tigon_Taxonomy|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return Tigon_Taxonomy
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     */
    private function includes() {
        require_once TIGON_TAXONOMY_PLUGIN_DIR . 'includes/class-tigon-categories.php';
        require_once TIGON_TAXONOMY_PLUGIN_DIR . 'includes/class-tigon-attributes.php';
        require_once TIGON_TAXONOMY_PLUGIN_DIR . 'includes/class-tigon-tags.php';
        require_once TIGON_TAXONOMY_PLUGIN_DIR . 'includes/class-tigon-cpt.php';
        require_once TIGON_TAXONOMY_PLUGIN_DIR . 'includes/class-tigon-location.php';
        require_once TIGON_TAXONOMY_PLUGIN_DIR . 'includes/class-tigon-primary-cat.php';
        require_once TIGON_TAXONOMY_PLUGIN_DIR . 'includes/class-tigon-breadcrumbs.php';
        require_once TIGON_TAXONOMY_PLUGIN_DIR . 'includes/class-tigon-widgets.php';
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Register CPT and taxonomy early.
        add_action( 'init', array( 'Tigon_CPT', 'register' ) );
        add_action( 'init', array( 'Tigon_Location', 'register' ) );

        // Initialize components after WooCommerce is loaded.
        add_action( 'woocommerce_init', array( $this, 'init_components' ) );

        // Register widget.
        add_action( 'widgets_init', array( 'Tigon_Widgets', 'register' ) );

        // Enqueue assets.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Template loading.
        add_filter( 'single_template', array( $this, 'load_single_template' ) );
        add_filter( 'archive_template', array( $this, 'load_archive_template' ) );
    }

    /**
     * Initialize WooCommerce-dependent components.
     */
    public function init_components() {
        Tigon_Primary_Cat::init();
        Tigon_Breadcrumbs::init();
    }

    /**
     * Enqueue front-end assets.
     */
    public function enqueue_assets() {
        if ( is_shop() || is_product_category() || is_product_taxonomy() || is_product() ) {
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
                'nonce'   => wp_create_nonce( 'tigon_filters' ),
            ) );
        }
    }

    /**
     * Load custom single template for manufacturer CPT.
     *
     * @param string $template Template path.
     * @return string
     */
    public function load_single_template( $template ) {
        if ( is_singular( 'tigon_manufacturer' ) ) {
            $custom = TIGON_TAXONOMY_PLUGIN_DIR . 'templates/single-tigon_manufacturer.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    /**
     * Load custom archive template for manufacturer CPT.
     *
     * @param string $template Template path.
     * @return string
     */
    public function load_archive_template( $template ) {
        if ( is_post_type_archive( 'tigon_manufacturer' ) ) {
            $custom = TIGON_TAXONOMY_PLUGIN_DIR . 'templates/archive-tigon_manufacturer.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    /**
     * Plugin activation callback.
     */
    public static function activate() {
        // Register CPT and taxonomy so rewrite rules are available.
        Tigon_CPT::register();
        Tigon_Location::register();

        // Seed categories.
        Tigon_Categories::seed();

        // Register attributes.
        Tigon_Attributes::seed();

        // Register default tags.
        Tigon_Tags::seed();

        // Seed location terms.
        Tigon_Location::seed();

        // Flush rewrite rules once.
        flush_rewrite_rules();

        // Set version option.
        update_option( '_tigon_taxonomy_version', TIGON_TAXONOMY_VERSION );
    }

    /**
     * Plugin deactivation callback.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

// Activation / deactivation hooks.
register_activation_hook( __FILE__, array( 'Tigon_Taxonomy', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Tigon_Taxonomy', 'deactivate' ) );

/**
 * Initialize plugin after plugins are loaded.
 */
function tigon_taxonomy_init() {
    // Require WooCommerce.
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Tigon Taxonomy</strong> requires WooCommerce to be installed and active.</p></div>';
        } );
        return;
    }
    Tigon_Taxonomy::instance();
}
add_action( 'plugins_loaded', 'tigon_taxonomy_init' );
