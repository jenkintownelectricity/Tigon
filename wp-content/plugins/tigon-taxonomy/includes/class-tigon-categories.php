<?php
/**
 * Tigon Categories — Registers WooCommerce product categories for manufacturers and models.
 *
 * Reads taxonomy-seed.json and creates hierarchical product categories:
 * Manufacturer (parent) > Model (child).
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Categories {

    /**
     * Seed manufacturer and model categories from taxonomy-seed.json.
     *
     * Idempotent: will not duplicate existing terms (checked by slug).
     */
    public static function seed() {
        $seed_data = self::get_seed_data();

        if ( empty( $seed_data['manufacturers'] ) ) {
            return;
        }

        foreach ( $seed_data['manufacturers'] as $manufacturer ) {
            $parent_term_id = self::ensure_term(
                $manufacturer['name'],
                $manufacturer['slug'],
                0,
                isset( $manufacturer['description'] ) ? $manufacturer['description'] : ''
            );

            if ( is_wp_error( $parent_term_id ) || ! $parent_term_id ) {
                continue;
            }

            if ( ! empty( $manufacturer['models'] ) ) {
                foreach ( $manufacturer['models'] as $model ) {
                    self::ensure_term(
                        $model['name'],
                        $model['slug'],
                        $parent_term_id,
                        ''
                    );
                }
            }
        }
    }

    /**
     * Get seed data from taxonomy-seed.json.
     *
     * @return array Decoded seed data.
     */
    public static function get_seed_data() {
        $file = TIGON_TAXONOMY_PLUGIN_DIR . 'data/taxonomy-seed.json';

        if ( ! file_exists( $file ) ) {
            return array();
        }

        $json = file_get_contents( $file );
        $data = json_decode( $json, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return array();
        }

        return $data;
    }

    /**
     * Ensure a product_cat term exists. Create it if it does not.
     *
     * @param string $name        Term name.
     * @param string $slug        Term slug.
     * @param int    $parent_id   Parent term ID (0 for top-level).
     * @param string $description Term description.
     * @return int|WP_Error Term ID on success, WP_Error on failure.
     */
    private static function ensure_term( $name, $slug, $parent_id = 0, $description = '' ) {
        // Check if term already exists by slug under the same parent.
        $existing = get_term_by( 'slug', $slug, 'product_cat' );

        if ( $existing && ! is_wp_error( $existing ) ) {
            // Verify it has the correct parent.
            if ( (int) $existing->parent === (int) $parent_id ) {
                return (int) $existing->term_id;
            }
            // If slug exists but under a different parent, WP will auto-suffix.
        }

        $result = wp_insert_term(
            $name,
            'product_cat',
            array(
                'slug'        => $slug,
                'parent'      => (int) $parent_id,
                'description' => $description,
            )
        );

        if ( is_wp_error( $result ) ) {
            // If term exists error, try to get existing term ID.
            if ( 'term_exists' === $result->get_error_code() ) {
                $term_id = $result->get_error_data();
                return (int) $term_id;
            }
            return $result;
        }

        return (int) $result['term_id'];
    }
}
