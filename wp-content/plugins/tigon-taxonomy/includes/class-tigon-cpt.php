<?php
/**
 * Tigon CPT — Manufacturer custom post type registration.
 *
 * Registers the `tigon_manufacturer` CPT for rich manufacturer profiles
 * with meta fields for logo, website URL, tagline, and linked WC category.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_CPT {

    /**
     * Register the CPT and associated hooks.
     */
    public function register() {
        add_action( 'init', array( $this, 'register_post_type' ), 5 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_tigon_manufacturer', array( $this, 'save_meta' ) );
    }

    /**
     * Register the tigon_manufacturer post type.
     */
    public function register_post_type() {
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
    }

    /**
     * Add meta boxes for manufacturer-specific fields.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'tigon_manufacturer_details',
            'Manufacturer Details',
            array( $this, 'render_meta_box' ),
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
    public function render_meta_box( $post ) {
        wp_nonce_field( 'tigon_manufacturer_meta', 'tigon_manufacturer_nonce' );

        $brand_logo    = get_post_meta( $post->ID, '_tigon_brand_logo', true );
        $brand_url     = get_post_meta( $post->ID, '_tigon_brand_url', true );
        $brand_tagline = get_post_meta( $post->ID, '_tigon_brand_tagline', true );
        $wc_cat_id     = get_post_meta( $post->ID, '_tigon_wc_category_id', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="tigon_brand_logo">Brand Logo (Image ID)</label></th>
                <td>
                    <input type="text" id="tigon_brand_logo" name="_tigon_brand_logo"
                           value="<?php echo esc_attr( $brand_logo ); ?>" class="regular-text" />
                    <p class="description">Enter the WordPress Media Library attachment ID for the brand logo.</p>
                </td>
            </tr>
            <tr>
                <th><label for="tigon_brand_url">Brand Website URL</label></th>
                <td>
                    <input type="url" id="tigon_brand_url" name="_tigon_brand_url"
                           value="<?php echo esc_attr( $brand_url ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="tigon_brand_tagline">Brand Tagline</label></th>
                <td>
                    <input type="text" id="tigon_brand_tagline" name="_tigon_brand_tagline"
                           value="<?php echo esc_attr( $brand_tagline ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="tigon_wc_category_id">Linked WooCommerce Category ID</label></th>
                <td>
                    <input type="number" id="tigon_wc_category_id" name="_tigon_wc_category_id"
                           value="<?php echo esc_attr( $wc_cat_id ); ?>" class="small-text" />
                    <p class="description">The product_cat term ID that corresponds to this manufacturer.</p>
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
    public function save_meta( $post_id ) {
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
            '_tigon_brand_logo'       => 'intval',
            '_tigon_brand_url'        => 'esc_url_raw',
            '_tigon_brand_tagline'    => 'sanitize_text_field',
            '_tigon_wc_category_id'   => 'intval',
        );

        foreach ( $fields as $meta_key => $sanitize_cb ) {
            if ( isset( $_POST[ $meta_key ] ) ) {
                $value = call_user_func( $sanitize_cb, $_POST[ $meta_key ] );
                update_post_meta( $post_id, $meta_key, $value );
            }
        }
    }

    /**
     * Load custom templates for the manufacturer CPT.
     *
     * @param string $template Template file path.
     * @return string Modified template path.
     */
    public static function template_include( $template ) {
        if ( is_singular( 'tigon_manufacturer' ) ) {
            $custom = TIGON_TAXONOMY_PLUGIN_DIR . 'templates/single-tigon_manufacturer.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        if ( is_post_type_archive( 'tigon_manufacturer' ) ) {
            $custom = TIGON_TAXONOMY_PLUGIN_DIR . 'templates/archive-tigon_manufacturer.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }
}

add_filter( 'template_include', array( 'Tigon_CPT', 'template_include' ) );
