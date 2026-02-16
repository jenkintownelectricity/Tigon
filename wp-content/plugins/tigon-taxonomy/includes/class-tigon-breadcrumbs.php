<?php
/**
 * Tigon Breadcrumbs — Overrides WooCommerce breadcrumbs for manufacturer > model hierarchy.
 *
 * Renders: Home > {Manufacturer} > {Model} > {Product Name}
 * Uses the primary category override logic from Tigon_Primary_Cat.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Breadcrumbs {

    /**
     * Hook into WooCommerce breadcrumb system.
     */
    public static function init() {
        add_filter( 'woocommerce_get_breadcrumb', array( __CLASS__, 'override_breadcrumb' ), 20, 2 );
    }

    /**
     * Override WooCommerce breadcrumbs on single product pages.
     *
     * Target output: Home > Manufacturer > Model > Product Name
     *
     * @param array                    $crumbs      Breadcrumb array.
     * @param WC_Breadcrumb            $breadcrumb  Breadcrumb instance.
     * @return array Modified breadcrumb array.
     */
    public static function override_breadcrumb( $crumbs, $breadcrumb ) {
        if ( ! is_product() ) {
            return $crumbs;
        }

        global $post;

        if ( ! $post || 'product' !== $post->post_type ) {
            return $crumbs;
        }

        $product_id   = $post->ID;
        $product_name = get_the_title( $product_id );

        // Get manufacturer and model categories.
        $manufacturer = Tigon_Primary_Cat::get_manufacturer_category( $product_id );
        $model        = Tigon_Primary_Cat::get_primary_category( $product_id );

        // Build new breadcrumb trail.
        $new_crumbs = array();

        // Home crumb (preserve from original).
        if ( ! empty( $crumbs[0] ) ) {
            $new_crumbs[] = $crumbs[0];
        }

        // Manufacturer crumb.
        if ( $manufacturer ) {
            $new_crumbs[] = array(
                $manufacturer->name,
                get_term_link( $manufacturer ),
            );
        }

        // Model crumb (only if different from manufacturer).
        if ( $model && ( ! $manufacturer || $model->term_id !== $manufacturer->term_id ) ) {
            $new_crumbs[] = array(
                $model->name,
                get_term_link( $model ),
            );
        }

        // Product name (current page, no link).
        $new_crumbs[] = array(
            $product_name,
            '',
        );

        return $new_crumbs;
    }
}
