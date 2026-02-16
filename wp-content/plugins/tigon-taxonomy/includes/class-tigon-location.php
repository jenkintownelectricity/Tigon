<?php
/**
 * Custom Taxonomy: tigon_location (Dealership Location).
 *
 * Registers a non-hierarchical taxonomy for multi-location inventory filtering.
 * Attached to WooCommerce products. Required on every product.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Location {

    /**
     * Register the taxonomy.
     */
    public static function register() {
        $labels = array(
            'name'                       => 'Dealership Locations',
            'singular_name'              => 'Dealership Location',
            'search_items'               => 'Search Locations',
            'all_items'                  => 'All Locations',
            'edit_item'                  => 'Edit Location',
            'update_item'                => 'Update Location',
            'add_new_item'               => 'Add New Location',
            'new_item_name'              => 'New Location Name',
            'menu_name'                  => 'Locations',
            'not_found'                  => 'No locations found.',
            'no_terms'                   => 'No locations',
            'items_list_navigation'      => 'Locations list navigation',
            'items_list'                 => 'Locations list',
            'back_to_items'              => 'Back to Locations',
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'location', 'with_front' => false ),
            'meta_box_cb'       => 'post_categories_meta_box', // Use checkboxes instead of tag-style input.
        );

        register_taxonomy( 'tigon_location', array( 'product' ), $args );
    }

    /**
     * Seed default location terms from taxonomy-seed.json.
     */
    public static function seed() {
        $seed_file = TIGON_TAXONOMY_PLUGIN_DIR . 'data/taxonomy-seed.json';
        if ( ! file_exists( $seed_file ) ) {
            return;
        }

        $data = json_decode( file_get_contents( $seed_file ), true );
        if ( empty( $data['locations'] ) ) {
            return;
        }

        foreach ( $data['locations'] as $location ) {
            if ( ! term_exists( $location['slug'], 'tigon_location' ) ) {
                wp_insert_term( $location['name'], 'tigon_location', array(
                    'slug' => $location['slug'],
                ) );
            }
        }
    }

    /**
     * Require location assignment before publishing a product.
     * Hooked via admin_init in the main plugin if needed.
     */
    public static function require_location_on_publish() {
        add_action( 'save_post_product', array( __CLASS__, 'check_location_assignment' ), 10, 2 );
        add_action( 'admin_notices', array( __CLASS__, 'location_admin_notice' ) );
    }

    /**
     * Check if product has a location assigned. If not, revert to draft.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public static function check_location_assignment( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( 'publish' !== $post->post_status ) {
            return;
        }

        $locations = wp_get_object_terms( $post_id, 'tigon_location' );
        if ( empty( $locations ) || is_wp_error( $locations ) ) {
            // Revert to draft.
            wp_update_post( array(
                'ID'          => $post_id,
                'post_status' => 'draft',
            ) );
            set_transient( 'tigon_location_missing_' . get_current_user_id(), $post_id, 30 );
        }
    }

    /**
     * Show admin notice when a product was reverted to draft due to missing location.
     */
    public static function location_admin_notice() {
        $post_id = get_transient( 'tigon_location_missing_' . get_current_user_id() );
        if ( $post_id ) {
            delete_transient( 'tigon_location_missing_' . get_current_user_id() );
            echo '<div class="notice notice-error"><p><strong>Tigon Taxonomy:</strong> Product reverted to draft. A <strong>Dealership Location</strong> must be assigned before publishing.</p></div>';
        }
    }
}
