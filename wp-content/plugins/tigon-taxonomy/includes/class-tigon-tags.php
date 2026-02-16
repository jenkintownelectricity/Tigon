<?php
/**
 * Tigon Tags — WooCommerce product tag registration.
 *
 * Seeds default product tags from taxonomy-seed.json across four groups:
 * Location, Promo/Status, Use Case, Compliance.
 * Idempotent: does not duplicate existing tags.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Tags {

    /**
     * Seed all default product tags from taxonomy-seed.json.
     * Called on plugin activation.
     */
    public static function seed() {
        if ( ! taxonomy_exists( 'product_tag' ) ) {
            return;
        }

        $data = Tigon_Categories::load_seed_data();
        if ( ! $data || empty( $data['tags'] ) ) {
            return;
        }

        foreach ( $data['tags'] as $group => $tags ) {
            foreach ( $tags as $tag_name ) {
                if ( ! term_exists( $tag_name, 'product_tag' ) ) {
                    wp_insert_term( $tag_name, 'product_tag', array(
                        'description' => ucfirst( str_replace( '_', ' ', $group ) ) . ' tag.',
                    ) );
                }
            }
        }
    }
}
