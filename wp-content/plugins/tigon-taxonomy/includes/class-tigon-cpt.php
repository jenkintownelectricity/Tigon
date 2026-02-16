<?php
/**
 * Tigon CPT — Registers the tigon_manufacturer custom post type.
 *
 * Provides rich manufacturer profile data including brand logo, website,
 * tagline, and linked WooCommerce category. Public with archive at /manufacturers/.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_CPT {

    /**
     * Hook into WordPress init.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_tigon_manufacturer', array( __CLASS__, 'save_meta' ) );
    }

    /**
     * Register the tigon_manufacturer custom post type.
     */
    public static function register_post_type() {
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
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array(
                'slug'       => 'manufacturers',
                'with_front' => false,
            ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 26,
            'menu_icon'          => 'dashicons-store',
            'supports'           => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'custom-fields',
            ),
        );

        register_post_type( 'tigon_manufacturer', $args );
    }

    /**
     * Add meta boxes for manufacturer custom fields.
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

        $brand_logo    = get_post_meta( $post->ID, '_tigon_brand_logo', true );
        $brand_url     = get_post_meta( $post->ID, '_tigon_brand_url', true );
        $brand_tagline = get_post_meta( $post->ID, '_tigon_brand_tagline', true );
        $wc_cat_id     = get_post_meta( $post->ID, '_tigon_wc_category_id', true );

        ?>
        <table class="form-table">
            <tr>
                <th><label for="tigon_brand_logo">Brand Logo (Image URL)</label></th>
                <td>
                    <input type="text" id="tigon_brand_logo" name="_tigon_brand_logo"
                           value="<?php echo esc_attr( $brand_logo ); ?>" class="regular-text" />
                    <p class="description">Enter the URL of the brand logo image, or use the Media Library.</p>
                </td>
            </tr>
            <tr>
                <th><label for="tigon_brand_url">Brand Website URL</label></th>
                <td>
                    <input type="url" id="tigon_brand_url" name="_tigon_brand_url"
                           value="<?php echo esc_url( $brand_url ); ?>" class="regular-text" />
                    <p class="description">Official manufacturer website URL.</p>
                </td>
            </tr>
            <tr>
                <th><label for="tigon_brand_tagline">Brand Tagline</label></th>
                <td>
                    <input type="text" id="tigon_brand_tagline" name="_tigon_brand_tagline"
                           value="<?php echo esc_attr( $brand_tagline ); ?>" class="regular-text" />
                    <p class="description">Short tagline or motto for this manufacturer.</p>
                </td>
            </tr>
            <tr>
                <th><label for="tigon_wc_category_id">Linked WC Category</label></th>
                <td>
                    <?php
                    $categories = get_terms( array(
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => false,
                        'parent'     => 0,
                    ) );
                    ?>
                    <select id="tigon_wc_category_id" name="_tigon_wc_category_id">
                        <option value="">— Select Category —</option>
                        <?php if ( ! is_wp_error( $categories ) ) : ?>
                            <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat->term_id ); ?>"
                                    <?php selected( $wc_cat_id, $cat->term_id ); ?>>
                                    <?php echo esc_html( $cat->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="description">Link this manufacturer profile to its WooCommerce product category.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save manufacturer meta fields.
     *
     * @param int $post_id Post ID.
     */
    public static function save_meta( $post_id ) {
        if ( ! isset( $_POST['tigon_manufacturer_nonce'] ) ||
             ! wp_verify_nonce( $_POST['tigon_manufacturer_nonce'], 'tigon_manufacturer_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            '_tigon_brand_logo'       => 'sanitize_text_field',
            '_tigon_brand_url'        => 'esc_url_raw',
            '_tigon_brand_tagline'    => 'sanitize_text_field',
            '_tigon_wc_category_id'   => 'absint',
        );

        foreach ( $fields as $key => $sanitize_callback ) {
            if ( isset( $_POST[ $key ] ) ) {
                $value = call_user_func( $sanitize_callback, $_POST[ $key ] );
                update_post_meta( $post_id, $key, $value );
            }
        }
    }
}
