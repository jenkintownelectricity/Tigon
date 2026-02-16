<?php
/**
 * Primary Category Override.
 *
 * Sets the MODEL (deepest child category) as the primary display category
 * on product pages and archives. Ensures manufacturer parent is always
 * inherited when a model child category is assigned.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Primary_Cat {

    /**
     * Initialize hooks.
     */
    public static function init() {
        // Override the primary displayed category in WooCommerce templates.
        add_filter( 'woocommerce_product_categories', array( __CLASS__, 'set_primary_category_display' ), 10, 2 );

        // Filter the category list output on single product pages.
        add_filter( 'woocommerce_get_product_category_list', array( __CLASS__, 'filter_category_list' ), 10, 3 );

        // Auto-assign parent manufacturer category when model is assigned.
        add_action( 'set_object_terms', array( __CLASS__, 'auto_assign_parent_category' ), 10, 6 );

        // Store primary category as post meta for reliable retrieval.
        add_action( 'save_post_product', array( __CLASS__, 'update_primary_category_meta' ), 20 );
    }

    /**
     * Get the primary (deepest child / MODEL) category for a product.
     *
     * @param int $product_id Product ID.
     * @return WP_Term|false The primary category term, or false.
     */
    public static function get_primary_category( $product_id ) {
        // Check if a primary category is explicitly stored.
        $stored = get_post_meta( $product_id, '_tigon_primary_category', true );
        if ( $stored ) {
            $term = get_term( (int) $stored, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term;
            }
        }

        // Otherwise, determine from assigned categories.
        return self::determine_primary_category( $product_id );
    }

    /**
     * Determine the primary category by finding the deepest child.
     *
     * @param int $product_id Product ID.
     * @return WP_Term|false
     */
    public static function determine_primary_category( $product_id ) {
        $terms = wp_get_post_terms( $product_id, 'product_cat', array(
            'orderby' => 'term_id',
            'order'   => 'ASC',
        ) );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return false;
        }

        // Find deepest child categories (terms with a parent).
        $children = array();
        $parents  = array();

        foreach ( $terms as $term ) {
            if ( $term->parent > 0 ) {
                $children[] = $term;
            } else {
                $parents[] = $term;
            }
        }

        // If we have child categories (models), return the first one as primary.
        if ( ! empty( $children ) ) {
            return $children[0];
        }

        // Fall back to first parent (manufacturer).
        if ( ! empty( $parents ) ) {
            return $parents[0];
        }

        return $terms[0];
    }

    /**
     * Filter the category list displayed on single product pages
     * to show the primary MODEL category prominently.
     *
     * @param string $list      HTML category list.
     * @param string $separator Separator between categories.
     * @param string $before    Before each category.
     * @return string
     */
    public static function filter_category_list( $list, $separator, $before ) {
        global $product;
        if ( ! $product ) {
            return $list;
        }

        $primary = self::get_primary_category( $product->get_id() );
        if ( ! $primary ) {
            return $list;
        }

        // Build a reordered list with the primary category first.
        $terms = wp_get_post_terms( $product->get_id(), 'product_cat' );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return $list;
        }

        $links = array();
        // Primary first.
        $links[] = '<a href="' . esc_url( get_term_link( $primary ) ) . '" class="tigon-primary-category">' . esc_html( $primary->name ) . '</a>';

        // Then other categories.
        foreach ( $terms as $term ) {
            if ( $term->term_id === $primary->term_id ) {
                continue;
            }
            $links[] = '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
        }

        return implode( $separator, $links );
    }

    /**
     * Placeholder for the woocommerce_product_categories filter.
     *
     * @param array      $categories Category terms.
     * @param WC_Product $product    Product object.
     * @return array
     */
    public static function set_primary_category_display( $categories, $product = null ) {
        return $categories;
    }

    /**
     * When a model (child) category is assigned to a product,
     * automatically assign the parent manufacturer category too.
     *
     * @param int    $object_id  Object ID (product).
     * @param array  $terms      Array of term IDs.
     * @param array  $tt_ids     Array of term taxonomy IDs.
     * @param string $taxonomy   Taxonomy slug.
     * @param bool   $append     Whether terms were appended.
     * @param array  $old_tt_ids Old term taxonomy IDs.
     */
    public static function auto_assign_parent_category( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
        if ( 'product_cat' !== $taxonomy ) {
            return;
        }

        $parent_ids = array();
        foreach ( $terms as $term_id ) {
            $term = get_term( (int) $term_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) && $term->parent > 0 ) {
                $parent_ids[] = $term->parent;
            }
        }

        if ( ! empty( $parent_ids ) ) {
            // Merge parent IDs with existing terms (don't remove anything).
            $current_terms = wp_get_object_terms( $object_id, 'product_cat', array( 'fields' => 'ids' ) );
            $merged = array_unique( array_merge( $current_terms, $parent_ids ) );

            // Only update if we actually need to add parents.
            if ( count( $merged ) > count( $current_terms ) ) {
                // Remove this action temporarily to avoid infinite loop.
                remove_action( 'set_object_terms', array( __CLASS__, 'auto_assign_parent_category' ), 10 );
                wp_set_object_terms( $object_id, array_map( 'intval', $merged ), 'product_cat' );
                add_action( 'set_object_terms', array( __CLASS__, 'auto_assign_parent_category' ), 10, 6 );
            }
        }
    }

    /**
     * Update the _tigon_primary_category meta when a product is saved.
     *
     * @param int $post_id Post ID.
     */
    public static function update_primary_category_meta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $primary = self::determine_primary_category( $post_id );
        if ( $primary ) {
            update_post_meta( $post_id, '_tigon_primary_category', $primary->term_id );
        }
    }

    /**
     * Get the manufacturer (parent) category for a product.
     *
     * @param int $product_id Product ID.
     * @return WP_Term|false
     */
    public static function get_manufacturer_category( $product_id ) {
        $primary = self::get_primary_category( $product_id );
        if ( ! $primary ) {
            return false;
        }

        // If the primary has a parent, that's the manufacturer.
        if ( $primary->parent > 0 ) {
            $parent = get_term( $primary->parent, 'product_cat' );
            if ( $parent && ! is_wp_error( $parent ) ) {
                return $parent;
            }
        }

        // If primary has no parent, it IS the manufacturer level.
        return $primary;
    }
}
