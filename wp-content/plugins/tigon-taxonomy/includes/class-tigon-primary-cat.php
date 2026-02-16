<?php
/**
 * Tigon Primary Cat — Overrides primary display category to MODEL (deepest child).
 *
 * Ensures every WooCommerce product displays the MODEL as its primary category.
 * Auto-inherits parent manufacturer category when a model is assigned.
 * Enforces tigon_location taxonomy requirement on product publish.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Primary_Cat {

    /**
     * Hook into WordPress and WooCommerce.
     */
    public static function init() {
        // Override the displayed product category (single product page).
        add_filter( 'woocommerce_product_categories_widget_args', array( __CLASS__, 'widget_args' ) );

        // When displaying a single category link for a product, use the deepest child.
        add_filter( 'woocommerce_product_get_category_ids', array( __CLASS__, 'ensure_parent_inherited' ), 10, 2 );

        // Auto-assign parent manufacturer when model category is set.
        add_action( 'set_object_terms', array( __CLASS__, 'auto_inherit_parent' ), 10, 6 );

        // Add admin notice if location is missing on product save.
        add_action( 'save_post_product', array( __CLASS__, 'check_location_on_save' ), 20, 2 );

        // Prevent publishing without location.
        add_filter( 'wp_insert_post_data', array( __CLASS__, 'prevent_publish_without_location' ), 10, 2 );

        // Store the primary (model) category as post meta for reliable retrieval.
        add_action( 'save_post_product', array( __CLASS__, 'set_primary_category_meta' ), 15, 2 );
    }

    /**
     * Get the primary (deepest child) category for a product.
     *
     * @param int $product_id Product ID.
     * @return WP_Term|false The deepest child category term, or false if none found.
     */
    public static function get_primary_category( $product_id ) {
        // Check stored meta first.
        $stored_id = get_post_meta( $product_id, '_tigon_primary_category', true );
        if ( $stored_id ) {
            $term = get_term( (int) $stored_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term;
            }
        }

        // Fallback: compute from assigned categories.
        return self::compute_primary_category( $product_id );
    }

    /**
     * Compute the primary category by finding the deepest child.
     *
     * @param int $product_id Product ID.
     * @return WP_Term|false
     */
    public static function compute_primary_category( $product_id ) {
        $terms = wp_get_post_terms( $product_id, 'product_cat' );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return false;
        }

        // Find the deepest child (term with a parent that is also assigned).
        $deepest      = null;
        $deepest_depth = -1;

        foreach ( $terms as $term ) {
            $depth = self::get_term_depth( $term->term_id, 'product_cat' );
            if ( $depth > $deepest_depth ) {
                $deepest_depth = $depth;
                $deepest       = $term;
            }
        }

        return $deepest ? $deepest : $terms[0];
    }

    /**
     * Get the depth of a term in its taxonomy hierarchy.
     *
     * @param int    $term_id  Term ID.
     * @param string $taxonomy Taxonomy slug.
     * @return int Depth (0 for root terms).
     */
    private static function get_term_depth( $term_id, $taxonomy ) {
        $depth = 0;
        $term  = get_term( $term_id, $taxonomy );

        while ( $term && ! is_wp_error( $term ) && $term->parent > 0 ) {
            $depth++;
            $term = get_term( $term->parent, $taxonomy );
        }

        return $depth;
    }

    /**
     * Get the manufacturer (top-level parent) category for a product.
     *
     * @param int $product_id Product ID.
     * @return WP_Term|false
     */
    public static function get_manufacturer_category( $product_id ) {
        $primary = self::get_primary_category( $product_id );

        if ( ! $primary ) {
            return false;
        }

        // Walk up to root.
        $term = $primary;
        while ( $term->parent > 0 ) {
            $parent = get_term( $term->parent, 'product_cat' );
            if ( ! $parent || is_wp_error( $parent ) ) {
                break;
            }
            $term = $parent;
        }

        return $term;
    }

    /**
     * When product_cat terms are set on a product, auto-inherit the parent manufacturer.
     *
     * @param int    $object_id  Object ID.
     * @param array  $terms      Array of term IDs.
     * @param array  $tt_ids     Array of term taxonomy IDs.
     * @param string $taxonomy   Taxonomy slug.
     * @param bool   $append     Whether terms were appended.
     * @param array  $old_tt_ids Old term taxonomy IDs.
     */
    public static function auto_inherit_parent( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
        if ( 'product_cat' !== $taxonomy ) {
            return;
        }

        $post = get_post( $object_id );
        if ( ! $post || 'product' !== $post->post_type ) {
            return;
        }

        $current_term_ids = array_map( 'intval', $terms );
        $parents_to_add   = array();

        foreach ( $current_term_ids as $term_id ) {
            $term = get_term( $term_id, 'product_cat' );
            if ( ! $term || is_wp_error( $term ) || $term->parent <= 0 ) {
                continue;
            }

            // Walk up the hierarchy and collect all ancestors.
            $parent_id = $term->parent;
            while ( $parent_id > 0 ) {
                if ( ! in_array( $parent_id, $current_term_ids, true ) ) {
                    $parents_to_add[] = $parent_id;
                }
                $parent_term = get_term( $parent_id, 'product_cat' );
                $parent_id   = ( $parent_term && ! is_wp_error( $parent_term ) ) ? $parent_term->parent : 0;
            }
        }

        if ( ! empty( $parents_to_add ) ) {
            // Remove this action temporarily to avoid infinite loop.
            remove_action( 'set_object_terms', array( __CLASS__, 'auto_inherit_parent' ), 10 );
            $all_terms = array_unique( array_merge( $current_term_ids, $parents_to_add ) );
            wp_set_object_terms( $object_id, $all_terms, 'product_cat' );
            add_action( 'set_object_terms', array( __CLASS__, 'auto_inherit_parent' ), 10, 6 );
        }
    }

    /**
     * Store the primary category as post meta whenever a product is saved.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public static function set_primary_category_meta( $post_id, $post ) {
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $primary = self::compute_primary_category( $post_id );
        if ( $primary ) {
            update_post_meta( $post_id, '_tigon_primary_category', $primary->term_id );
        }
    }

    /**
     * Check that a dealership location is assigned when saving a product.
     * Adds an admin notice if missing.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public static function check_location_on_save( $post_id, $post ) {
        if ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type ) {
            return;
        }

        $locations = wp_get_post_terms( $post_id, 'tigon_location' );

        if ( empty( $locations ) || is_wp_error( $locations ) ) {
            set_transient(
                'tigon_location_missing_' . $post_id,
                true,
                60
            );
        }
    }

    /**
     * Prevent publishing a product without a dealership location.
     * Reverts to draft if no location taxonomy term is assigned.
     *
     * @param array $data    Post data array.
     * @param array $postarr Raw post data array.
     * @return array Modified post data.
     */
    public static function prevent_publish_without_location( $data, $postarr ) {
        if ( 'product' !== $data['post_type'] ) {
            return $data;
        }

        if ( 'publish' !== $data['post_status'] ) {
            return $data;
        }

        // Check if location terms are being set.
        if ( empty( $postarr['tax_input']['tigon_location'] ) ) {
            // Check existing terms if this is an update.
            if ( ! empty( $postarr['ID'] ) ) {
                $existing = wp_get_post_terms( $postarr['ID'], 'tigon_location' );
                if ( ! empty( $existing ) && ! is_wp_error( $existing ) ) {
                    return $data;
                }
            }

            // Revert to draft and add admin notice.
            $data['post_status'] = 'draft';
            add_filter( 'redirect_post_location', function( $location ) {
                return add_query_arg( 'tigon_location_required', '1', $location );
            } );
        }

        return $data;
    }

    /**
     * Ensure parent categories are always included in the category_ids array.
     * This filter runs when WooCommerce retrieves product category IDs.
     *
     * @param array      $category_ids Category ID array.
     * @param WC_Product $product      Product object.
     * @return array
     */
    public static function ensure_parent_inherited( $category_ids, $product ) {
        if ( empty( $category_ids ) ) {
            return $category_ids;
        }

        $all_ids = $category_ids;

        foreach ( $category_ids as $cat_id ) {
            $ancestors = get_ancestors( $cat_id, 'product_cat', 'taxonomy' );
            $all_ids   = array_merge( $all_ids, $ancestors );
        }

        return array_unique( array_map( 'intval', $all_ids ) );
    }

    /**
     * Display admin notices for missing location.
     */
    public static function admin_notices() {
        if ( ! isset( $_GET['tigon_location_required'] ) ) {
            return;
        }

        echo '<div class="error"><p><strong>Tigon Taxonomy:</strong> A Dealership Location must be assigned before publishing this product. The product has been saved as a draft.</p></div>';
    }

    /**
     * Modify widget args if needed.
     *
     * @param array $args Widget arguments.
     * @return array
     */
    public static function widget_args( $args ) {
        return $args;
    }
}

// Hook admin notices outside the class for immediate availability.
add_action( 'admin_notices', array( 'Tigon_Primary_Cat', 'admin_notices' ) );
