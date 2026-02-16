<?php
/**
 * Tigon Tags — Registers default WooCommerce product tags.
 *
 * Creates flat (non-hierarchical) product tags for:
 * Location, Promo/Status, Use Case, and Compliance groups.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Tags {

    /**
     * Seed default product tags from taxonomy-seed.json.
     *
     * Idempotent: checks for existing tags by name before creating.
     */
    public static function seed() {
        $seed_data = Tigon_Categories::get_seed_data();

        if ( empty( $seed_data['tags'] ) ) {
            return;
        }

        foreach ( $seed_data['tags'] as $group => $tags ) {
            foreach ( $tags as $tag_name ) {
                self::ensure_tag( $tag_name );
            }
        }
    }

    /**
     * Ensure a product tag exists. Create it if it does not.
     *
     * @param string $name Tag name.
     * @return int|WP_Error Term ID on success.
     */
    private static function ensure_tag( $name ) {
        $existing = get_term_by( 'name', $name, 'product_tag' );

        if ( $existing && ! is_wp_error( $existing ) ) {
            return (int) $existing->term_id;
        }

        $result = wp_insert_term( $name, 'product_tag' );

        if ( is_wp_error( $result ) ) {
            if ( 'term_exists' === $result->get_error_code() ) {
                return (int) $result->get_error_data();
            }
            return $result;
        }

        return (int) $result['term_id'];
    }
}
