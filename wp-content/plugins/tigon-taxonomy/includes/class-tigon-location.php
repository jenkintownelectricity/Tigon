<?php
/**
 * Tigon Location — Registers the tigon_location custom taxonomy for dealership locations.
 *
 * Attached to WooCommerce products. Non-hierarchical (tag-like) with checkbox UI.
 * Terms: Hatfield PA, Poconos PA, Ocean View NJ, National.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Location {

    /**
     * Hook into WordPress init.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
    }

    /**
     * Register the tigon_location taxonomy on WooCommerce products.
     */
    public static function register_taxonomy() {
        $labels = array(
            'name'              => 'Dealership Locations',
            'singular_name'     => 'Dealership Location',
            'search_items'      => 'Search Locations',
            'all_items'         => 'All Locations',
            'edit_item'         => 'Edit Location',
            'update_item'       => 'Update Location',
            'add_new_item'      => 'Add New Location',
            'new_item_name'     => 'New Location Name',
            'menu_name'         => 'Locations',
            'not_found'         => 'No locations found.',
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => false,
            'query_var'         => true,
            'rewrite'           => array(
                'slug'       => 'location',
                'with_front' => false,
            ),
            // Display as checkboxes in admin despite being non-hierarchical.
            'meta_box_cb'       => 'post_categories_meta_box',
        );

        register_taxonomy( 'tigon_location', array( 'product' ), $args );
    }

    /**
     * Seed default location terms from taxonomy-seed.json.
     *
     * Idempotent: checks for existing terms by slug before creating.
     */
    public static function seed() {
        $seed_data = Tigon_Categories::get_seed_data();

        if ( empty( $seed_data['locations'] ) ) {
            return;
        }

        // Ensure taxonomy is registered.
        if ( ! taxonomy_exists( 'tigon_location' ) ) {
            self::register_taxonomy();
        }

        foreach ( $seed_data['locations'] as $location ) {
            $existing = get_term_by( 'slug', $location['slug'], 'tigon_location' );

            if ( $existing && ! is_wp_error( $existing ) ) {
                continue;
            }

            wp_insert_term(
                $location['name'],
                'tigon_location',
                array( 'slug' => $location['slug'] )
            );
        }
    }
}
