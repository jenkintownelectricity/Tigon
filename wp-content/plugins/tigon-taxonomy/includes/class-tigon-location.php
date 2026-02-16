<?php
/**
 * Tigon Location — Dealership location taxonomy registration.
 *
 * Registers `tigon_location` custom taxonomy on WooCommerce products
 * for multi-location inventory filtering.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Location {

    /**
     * Register the taxonomy and hooks.
     */
    public function register() {
        add_action( 'init', array( $this, 'register_taxonomy' ), 5 );
    }

    /**
     * Register the tigon_location taxonomy.
     */
    public function register_taxonomy() {
        $labels = array(
            'name'                       => 'Dealership Locations',
            'singular_name'              => 'Dealership Location',
            'menu_name'                  => 'Locations',
            'all_items'                  => 'All Locations',
            'edit_item'                  => 'Edit Location',
            'view_item'                  => 'View Location',
            'update_item'                => 'Update Location',
            'add_new_item'               => 'Add New Location',
            'new_item_name'              => 'New Location Name',
            'search_items'               => 'Search Locations',
            'popular_items'              => 'Popular Locations',
            'separate_items_with_commas' => 'Separate locations with commas',
            'add_or_remove_items'        => 'Add or remove locations',
            'choose_from_most_used'      => 'Choose from the most used locations',
            'not_found'                  => 'No locations found.',
            'back_to_items'              => 'Back to locations',
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
            'meta_box_cb'       => array( $this, 'render_checkbox_metabox' ),
            'rewrite'           => array( 'slug' => 'location', 'with_front' => false ),
        );

        register_taxonomy( 'tigon_location', array( 'product' ), $args );
    }

    /**
     * Render a checkbox-style meta box instead of the default tag cloud.
     * This makes it behave like a hierarchical taxonomy in the UI.
     *
     * @param WP_Post $post     Current post.
     * @param array   $box      Meta box args.
     */
    public function render_checkbox_metabox( $post, $box ) {
        $terms     = get_terms( array(
            'taxonomy'   => 'tigon_location',
            'hide_empty' => false,
        ) );
        $post_terms = wp_get_object_terms( $post->ID, 'tigon_location', array( 'fields' => 'ids' ) );

        wp_nonce_field( 'tigon_location_meta', 'tigon_location_nonce' );
        ?>
        <div id="tigon-location-checklist" style="max-height: 200px; overflow-y: auto; padding: 5px;">
            <?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
                <?php foreach ( $terms as $term ) : ?>
                    <label style="display: block; margin-bottom: 4px;">
                        <input type="checkbox" name="tax_input[tigon_location][]"
                               value="<?php echo esc_attr( $term->slug ); ?>"
                               <?php checked( in_array( $term->term_id, $post_terms, true ) ); ?> />
                        <?php echo esc_html( $term->name ); ?>
                    </label>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No locations available. Add locations in the Locations admin screen.</p>
            <?php endif; ?>
        </div>
        <p class="description" style="color: #d63638; margin-top: 8px;">
            <strong>Required:</strong> Every product must have at least one dealership location assigned.
        </p>
        <?php
    }

    /**
     * Seed default location terms from taxonomy-seed.json.
     * Called on plugin activation.
     */
    public static function seed() {
        if ( ! taxonomy_exists( 'tigon_location' ) ) {
            // Register temporarily for seeding during activation.
            register_taxonomy( 'tigon_location', array( 'product' ), array(
                'hierarchical' => false,
                'public'       => true,
                'rewrite'      => array( 'slug' => 'location', 'with_front' => false ),
            ) );
        }

        $data = Tigon_Categories::load_seed_data();
        if ( ! $data || empty( $data['locations'] ) ) {
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
}
