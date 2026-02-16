<?php
/**
 * Custom Post Type: tigon_manufacturer.
 *
 * Registers a CPT for rich manufacturer profile data including
 * brand logo, website URL, tagline, and linked WooCommerce category.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_CPT {

    /**
     * Register the manufacturer CPT.
     */
    public static function register() {
        $labels = array(
            'name'                  => 'Manufacturers',
            'singular_name'         => 'Manufacturer',
            'menu_name'             => 'Manufacturers',
            'name_admin_bar'        => 'Manufacturer',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Manufacturer',
            'new_item'              => 'New Manufacturer',
            'edit_item'             => 'Edit Manufacturer',
            'view_item'             => 'View Manufacturer',
            'all_items'             => 'All Manufacturers',
            'search_items'          => 'Search Manufacturers',
            'not_found'             => 'No manufacturers found.',
            'not_found_in_trash'    => 'No manufacturers found in Trash.',
            'archives'              => 'Manufacturer Archives',
            'filter_items_list'     => 'Filter manufacturers list',
            'items_list_navigation' => 'Manufacturers list navigation',
            'items_list'            => 'Manufacturers list',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'manufacturers', 'with_front' => false ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 26,
            'menu_icon'          => 'dashicons-store',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        );

        register_post_type( 'tigon_manufacturer', $args );

        // Register meta fields.
        self::register_meta();
    }

    /**
     * Register meta fields for the manufacturer CPT.
     */
    private static function register_meta() {
        register_post_meta( 'tigon_manufacturer', '_tigon_brand_logo', array(
            'type'              => 'integer',
            'description'       => 'Brand logo attachment ID',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );

        register_post_meta( 'tigon_manufacturer', '_tigon_brand_url', array(
            'type'              => 'string',
            'description'       => 'Brand website URL',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );

        register_post_meta( 'tigon_manufacturer', '_tigon_brand_tagline', array(
            'type'              => 'string',
            'description'       => 'Brand tagline',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );

        register_post_meta( 'tigon_manufacturer', '_tigon_wc_category_id', array(
            'type'              => 'integer',
            'description'       => 'Linked WooCommerce product category term ID',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );

        // Add meta boxes for the admin edit screen.
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_tigon_manufacturer', array( __CLASS__, 'save_meta' ) );
    }

    /**
     * Add meta boxes to the manufacturer edit screen.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'tigon_manufacturer_details',
            'Manufacturer Details',
            array( __CLASS__, 'render_meta_box' ),
            'tigon_manufacturer',
            'normal',
            'high'
        );
    }

    /**
     * Render the manufacturer details meta box.
     *
     * @param WP_Post $post Current post object.
     */
    public static function render_meta_box( $post ) {
        wp_nonce_field( 'tigon_manufacturer_meta', 'tigon_manufacturer_nonce' );

        $brand_url     = get_post_meta( $post->ID, '_tigon_brand_url', true );
        $brand_tagline = get_post_meta( $post->ID, '_tigon_brand_tagline', true );
        $brand_logo    = get_post_meta( $post->ID, '_tigon_brand_logo', true );
        $wc_cat_id     = get_post_meta( $post->ID, '_tigon_wc_category_id', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="tigon_brand_url">Brand Website URL</label></th>
                <td><input type="url" id="tigon_brand_url" name="_tigon_brand_url" value="<?php echo esc_attr( $brand_url ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="tigon_brand_tagline">Brand Tagline</label></th>
                <td><input type="text" id="tigon_brand_tagline" name="_tigon_brand_tagline" value="<?php echo esc_attr( $brand_tagline ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="tigon_brand_logo">Brand Logo (Attachment ID)</label></th>
                <td><input type="number" id="tigon_brand_logo" name="_tigon_brand_logo" value="<?php echo esc_attr( $brand_logo ); ?>" class="small-text" />
                <p class="description">Enter the media library attachment ID for the brand logo, or use the Featured Image.</p></td>
            </tr>
            <tr>
                <th><label for="tigon_wc_category_id">Linked WC Category ID</label></th>
                <td><input type="number" id="tigon_wc_category_id" name="_tigon_wc_category_id" value="<?php echo esc_attr( $wc_cat_id ); ?>" class="small-text" />
                <p class="description">The WooCommerce product_cat term ID for this manufacturer.</p></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save manufacturer meta on post save.
     *
     * @param int $post_id Post ID.
     */
    public static function save_meta( $post_id ) {
        if ( ! isset( $_POST['tigon_manufacturer_nonce'] ) || ! wp_verify_nonce( $_POST['tigon_manufacturer_nonce'], 'tigon_manufacturer_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            '_tigon_brand_url'        => 'esc_url_raw',
            '_tigon_brand_tagline'    => 'sanitize_text_field',
            '_tigon_brand_logo'       => 'absint',
            '_tigon_wc_category_id'   => 'absint',
        );

        foreach ( $fields as $key => $sanitize ) {
            if ( isset( $_POST[ $key ] ) ) {
                update_post_meta( $post_id, $key, $sanitize( $_POST[ $key ] ) );
            }
        }
    }
}
