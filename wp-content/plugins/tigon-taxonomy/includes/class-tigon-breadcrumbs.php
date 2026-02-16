<?php
/**
 * Tigon Breadcrumbs — WooCommerce breadcrumb override.
 *
 * Overrides WooCommerce breadcrumbs on product pages and category archives
 * to render: Home > Manufacturer > Model > Product Name.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Breadcrumbs {

    /**
     * Register breadcrumb filter.
     */
    public function register() {
        add_filter( 'woocommerce_get_breadcrumb', array( $this, 'override_breadcrumb' ), 20, 2 );
    }

    /**
     * Override WooCommerce breadcrumbs for product pages.
     *
     * Target output: Home > Manufacturer > Model > Product Name
     *
     * @param array                    $crumbs      Breadcrumb array of [name, url] pairs.
     * @param WC_Breadcrumb            $breadcrumb  WooCommerce breadcrumb object.
     * @return array Modified breadcrumbs.
     */
    public function override_breadcrumb( $crumbs, $breadcrumb ) {
        if ( is_singular( 'product' ) ) {
            return $this->product_breadcrumb( $crumbs );
        }

        if ( is_tax( 'product_cat' ) ) {
            return $this->category_breadcrumb( $crumbs );
        }

        return $crumbs;
    }

    /**
     * Build breadcrumb for a single product page.
     * Home > Manufacturer > Model > Product Name
     *
     * @param array $crumbs Original breadcrumbs.
     * @return array Modified breadcrumbs.
     */
    private function product_breadcrumb( $crumbs ) {
        global $post;

        if ( ! $post ) {
            return $crumbs;
        }

        $product_id   = $post->ID;
        $manufacturer = Tigon_Primary_Cat::get_manufacturer_category( $product_id );
        $model        = Tigon_Primary_Cat::get_primary_category( $product_id );

        // Start with Home (keep the first crumb from WooCommerce).
        $new_crumbs = array();
        if ( ! empty( $crumbs[0] ) ) {
            $new_crumbs[] = $crumbs[0]; // Home
        }

        // Add manufacturer.
        if ( $manufacturer ) {
            $new_crumbs[] = array(
                $manufacturer->name,
                get_term_link( $manufacturer ),
            );
        }

        // Add model (only if different from manufacturer).
        if ( $model && ( ! $manufacturer || $model->term_id !== $manufacturer->term_id ) ) {
            $new_crumbs[] = array(
                $model->name,
                get_term_link( $model ),
            );
        }

        // Add product name (no link — current page).
        $new_crumbs[] = array(
            get_the_title( $product_id ),
            '',
        );

        return $new_crumbs;
    }

    /**
     * Build breadcrumb for a product category archive page.
     * For manufacturer (parent): Home > Manufacturer
     * For model (child):         Home > Manufacturer > Model
     *
     * @param array $crumbs Original breadcrumbs.
     * @return array Modified breadcrumbs.
     */
    private function category_breadcrumb( $crumbs ) {
        $term = get_queried_object();

        if ( ! $term || ! is_a( $term, 'WP_Term' ) ) {
            return $crumbs;
        }

        $new_crumbs = array();
        if ( ! empty( $crumbs[0] ) ) {
            $new_crumbs[] = $crumbs[0]; // Home
        }

        if ( $term->parent > 0 ) {
            // This is a model (child) category — add parent manufacturer first.
            $parent = get_term( $term->parent, 'product_cat' );
            if ( $parent && ! is_wp_error( $parent ) ) {
                // Walk up to get full hierarchy.
                $ancestors = array();
                $current   = $parent;
                while ( $current ) {
                    array_unshift( $ancestors, $current );
                    if ( $current->parent > 0 ) {
                        $current = get_term( $current->parent, 'product_cat' );
                        if ( is_wp_error( $current ) ) {
                            break;
                        }
                    } else {
                        break;
                    }
                }
                foreach ( $ancestors as $ancestor ) {
                    $new_crumbs[] = array(
                        $ancestor->name,
                        get_term_link( $ancestor ),
                    );
                }
            }
        }

        // Current category (no link — current page).
        $new_crumbs[] = array(
            $term->name,
            '',
        );

        return $new_crumbs;
    }
}
