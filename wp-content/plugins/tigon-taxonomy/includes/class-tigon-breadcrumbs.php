<?php
/**
 * WooCommerce Breadcrumb Override.
 *
 * Renders: Home > Manufacturer > Model > Product Name.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Breadcrumbs {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_filter( 'woocommerce_get_breadcrumb', array( __CLASS__, 'override_breadcrumb' ), 20, 2 );
    }

    /**
     * Override WooCommerce breadcrumbs for product pages and category archives.
     *
     * Target: Home > Manufacturer > Model > Product Name
     *
     * @param array                     $crumbs      Breadcrumb trail.
     * @param WC_Breadcrumb|null        $breadcrumb  Breadcrumb object.
     * @return array
     */
    public static function override_breadcrumb( $crumbs, $breadcrumb = null ) {
        if ( is_product() ) {
            return self::product_breadcrumb( $crumbs );
        }

        if ( is_product_category() ) {
            return self::category_breadcrumb( $crumbs );
        }

        return $crumbs;
    }

    /**
     * Build breadcrumb for single product pages.
     * Home > Manufacturer > Model > Product Name
     *
     * @param array $crumbs Existing crumbs.
     * @return array
     */
    private static function product_breadcrumb( $crumbs ) {
        global $post;
        if ( ! $post ) {
            return $crumbs;
        }

        $product_id   = $post->ID;
        $primary      = Tigon_Primary_Cat::get_primary_category( $product_id );
        $manufacturer = Tigon_Primary_Cat::get_manufacturer_category( $product_id );

        $new_crumbs = array();

        // Home (keep existing first crumb).
        if ( ! empty( $crumbs[0] ) ) {
            $new_crumbs[] = $crumbs[0];
        }

        // Manufacturer.
        if ( $manufacturer ) {
            $new_crumbs[] = array(
                $manufacturer->name,
                get_term_link( $manufacturer ),
            );
        }

        // Model (primary category) — only if different from manufacturer.
        if ( $primary && ( ! $manufacturer || $primary->term_id !== $manufacturer->term_id ) ) {
            $new_crumbs[] = array(
                $primary->name,
                get_term_link( $primary ),
            );
        }

        // Product name (current page, no link).
        $new_crumbs[] = array(
            get_the_title( $product_id ),
            '',
        );

        return $new_crumbs;
    }

    /**
     * Build breadcrumb for product category archives.
     * For manufacturer: Home > Manufacturer
     * For model: Home > Manufacturer > Model
     *
     * @param array $crumbs Existing crumbs.
     * @return array
     */
    private static function category_breadcrumb( $crumbs ) {
        $queried = get_queried_object();
        if ( ! $queried || ! isset( $queried->taxonomy ) || 'product_cat' !== $queried->taxonomy ) {
            return $crumbs;
        }

        $new_crumbs = array();

        // Home.
        if ( ! empty( $crumbs[0] ) ) {
            $new_crumbs[] = $crumbs[0];
        }

        // If this is a child category (model), add parent (manufacturer) first.
        if ( $queried->parent > 0 ) {
            $parent = get_term( $queried->parent, 'product_cat' );
            if ( $parent && ! is_wp_error( $parent ) ) {
                $new_crumbs[] = array(
                    $parent->name,
                    get_term_link( $parent ),
                );
            }
        }

        // Current category (no link — it's the current page).
        $new_crumbs[] = array(
            $queried->name,
            '',
        );

        return $new_crumbs;
    }
}
