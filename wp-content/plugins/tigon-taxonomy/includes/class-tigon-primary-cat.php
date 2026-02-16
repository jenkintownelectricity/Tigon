<?php
/**
 * Tigon Primary Cat — Override primary display category to MODEL.
 *
 * The most critical business requirement: every product displays the MODEL
 * (deepest child category) as its primary category, while the Manufacturer
 * parent category is automatically inherited. The tigon_location taxonomy
 * is enforced as required.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Primary_Cat {

    /**
     * Register all hooks.
     */
    public function register() {
        // Override the displayed category link on product loops and single pages.
        add_filter( 'woocommerce_product_categories_widget_args', array( $this, 'widget_args' ) );

        // Auto-inherit parent manufacturer category when a model child is assigned.
        add_action( 'set_object_terms', array( $this, 'auto_inherit_parent' ), 10, 6 );

        // Admin notice if location taxonomy is not set on product save.
        add_action( 'save_post_product', array( $this, 'enforce_location_requirement' ), 20 );

        // Add admin notice display.
        add_action( 'admin_notices', array( $this, 'display_location_notice' ) );

        // Filter the primary category shown in WooCommerce templates.
        add_filter( 'woocommerce_product_get_category_ids', array( $this, 'reorder_category_ids' ), 10, 2 );

        // Override the permalink category base to use the model.
        add_filter( 'wc_product_post_type_link_product_cat', array( $this, 'primary_product_cat_for_permalink' ), 10, 3 );
    }

    /**
     * Get the primary (deepest child / MODEL) category for a product.
     *
     * @param int $product_id Product ID.
     * @return WP_Term|false The deepest child category term, or false.
     */
    public static function get_primary_category( $product_id ) {
        $terms = wp_get_post_terms( $product_id, 'product_cat', array(
            'orderby' => 'parent',
            'order'   => 'DESC',
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return false;
        }

        // Find the deepest child (a term whose term_id is NOT a parent of any other assigned term).
        $parent_ids = wp_list_pluck( $terms, 'parent' );
        $all_ids    = wp_list_pluck( $terms, 'term_id' );

        // Deepest children = terms that are not in any other term's parent field.
        $deepest = array();
        foreach ( $terms as $term ) {
            if ( ! in_array( $term->term_id, $parent_ids, true ) && $term->parent > 0 ) {
                $deepest[] = $term;
            }
        }

        // If we found child terms, return the first one (the MODEL).
        if ( ! empty( $deepest ) ) {
            return $deepest[0];
        }

        // Fallback: return the first category (likely a manufacturer parent).
        return $terms[0];
    }

    /**
     * Get the manufacturer (parent) category for a product.
     *
     * @param int $product_id Product ID.
     * @return WP_Term|false The manufacturer parent category, or false.
     */
    public static function get_manufacturer_category( $product_id ) {
        $terms = wp_get_post_terms( $product_id, 'product_cat', array(
            'orderby' => 'parent',
            'order'   => 'ASC',
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return false;
        }

        // Find top-level terms (parent = 0).
        foreach ( $terms as $term ) {
            if ( $term->parent === 0 ) {
                return $term;
            }
        }

        // Fallback: walk up from the first term.
        $term = $terms[0];
        while ( $term->parent > 0 ) {
            $parent_term = get_term( $term->parent, 'product_cat' );
            if ( is_wp_error( $parent_term ) || ! $parent_term ) {
                break;
            }
            $term = $parent_term;
        }

        return $term;
    }

    /**
     * Reorder category IDs so the primary (model) category comes first.
     * This affects WooCommerce template functions that display the "first" category.
     *
     * @param array      $category_ids Category IDs.
     * @param WC_Product $product      Product object.
     * @return array Reordered category IDs.
     */
    public function reorder_category_ids( $category_ids, $product ) {
        if ( empty( $category_ids ) ) {
            return $category_ids;
        }

        $primary = self::get_primary_category( $product->get_id() );
        if ( ! $primary ) {
            return $category_ids;
        }

        // Move the primary category ID to the front.
        $primary_id = $primary->term_id;
        $key = array_search( $primary_id, $category_ids, true );
        if ( $key !== false ) {
            unset( $category_ids[ $key ] );
            array_unshift( $category_ids, $primary_id );
            $category_ids = array_values( $category_ids );
        }

        return $category_ids;
    }

    /**
     * Override the category used in product permalinks to use the model.
     *
     * @param WP_Term $term       The category term.
     * @param array   $terms      All product category terms.
     * @param object  $product    The product post.
     * @return WP_Term The primary model category.
     */
    public function primary_product_cat_for_permalink( $term, $terms, $product ) {
        $primary = self::get_primary_category( $product->ID );
        if ( $primary ) {
            return $primary;
        }
        return $term;
    }

    /**
     * When a model child category is assigned to a product, automatically
     * add the parent manufacturer category as well.
     *
     * @param int    $object_id  Object ID.
     * @param array  $terms      An array of term taxonomy IDs.
     * @param array  $tt_ids     An array of term taxonomy IDs.
     * @param string $taxonomy   Taxonomy slug.
     * @param bool   $append     Whether to append terms.
     * @param array  $old_tt_ids Old term taxonomy IDs.
     */
    public function auto_inherit_parent( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
        if ( $taxonomy !== 'product_cat' ) {
            return;
        }

        $current_terms = wp_get_post_terms( $object_id, 'product_cat', array( 'fields' => 'ids' ) );
        if ( is_wp_error( $current_terms ) ) {
            return;
        }

        $parents_to_add = array();
        foreach ( $current_terms as $term_id ) {
            $term = get_term( $term_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) && $term->parent > 0 ) {
                // Walk up the parent chain.
                $parent_id = $term->parent;
                while ( $parent_id > 0 ) {
                    if ( ! in_array( $parent_id, $current_terms, true ) ) {
                        $parents_to_add[] = $parent_id;
                    }
                    $parent_term = get_term( $parent_id, 'product_cat' );
                    if ( ! $parent_term || is_wp_error( $parent_term ) ) {
                        break;
                    }
                    $parent_id = $parent_term->parent;
                }
            }
        }

        if ( ! empty( $parents_to_add ) ) {
            // Remove this filter temporarily to prevent infinite recursion.
            remove_action( 'set_object_terms', array( $this, 'auto_inherit_parent' ), 10 );
            $all_terms = array_unique( array_merge( $current_terms, $parents_to_add ) );
            wp_set_object_terms( $object_id, array_map( 'intval', $all_terms ), 'product_cat' );
            add_action( 'set_object_terms', array( $this, 'auto_inherit_parent' ), 10, 6 );
        }
    }

    /**
     * Enforce that the tigon_location taxonomy is assigned on product save.
     * Sets a transient notice if missing.
     *
     * @param int $post_id Post ID.
     */
    public function enforce_location_requirement( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $locations = wp_get_object_terms( $post_id, 'tigon_location', array( 'fields' => 'ids' ) );

        if ( empty( $locations ) || is_wp_error( $locations ) ) {
            set_transient( 'tigon_location_missing_' . get_current_user_id(), $post_id, 30 );
        }
    }

    /**
     * Display admin notice when a product is saved without a location.
     */
    public function display_location_notice() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'product' ) {
            return;
        }

        $user_id  = get_current_user_id();
        $post_id  = get_transient( 'tigon_location_missing_' . $user_id );

        if ( $post_id ) {
            delete_transient( 'tigon_location_missing_' . $user_id );
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Tigon Taxonomy:</strong> Product #<?php echo (int) $post_id; ?> was saved without a Dealership Location. Every product should have at least one location assigned.</p>
            </div>
            <?php
        }
    }

    /**
     * Widget args override to show hierarchical categories.
     *
     * @param array $args Widget args.
     * @return array Modified args.
     */
    public function widget_args( $args ) {
        $args['hierarchical'] = true;
        $args['show_children_only'] = false;
        return $args;
    }
}
