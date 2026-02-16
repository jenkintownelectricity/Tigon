<?php
/**
 * WooCommerce Product Category registration.
 *
 * Registers manufacturer parent categories and model child categories
 * from taxonomy-seed.json during plugin activation.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Categories {

    /**
     * Seed categories from taxonomy-seed.json.
     */
    public static function seed() {
        $seed_file = TIGON_TAXONOMY_PLUGIN_DIR . 'data/taxonomy-seed.json';
        if ( ! file_exists( $seed_file ) ) {
            return;
        }

        $data = json_decode( file_get_contents( $seed_file ), true );
        if ( empty( $data['manufacturers'] ) ) {
            return;
        }

        foreach ( $data['manufacturers'] as $manufacturer ) {
            $parent_id = self::insert_category(
                $manufacturer['name'],
                $manufacturer['slug'],
                0,
                isset( $manufacturer['description'] ) ? $manufacturer['description'] : ''
            );

            if ( is_wp_error( $parent_id ) || ! $parent_id ) {
                continue;
            }

            if ( ! empty( $manufacturer['models'] ) ) {
                foreach ( $manufacturer['models'] as $model ) {
                    self::insert_category(
                        $model['name'],
                        $model['slug'],
                        $parent_id,
                        isset( $model['description'] ) ? $model['description'] : ''
                    );
                }
            }
        }
    }

    /**
     * Insert a product category if it does not exist.
     *
     * @param string $name        Term name.
     * @param string $slug        Term slug.
     * @param int    $parent_id   Parent term ID (0 for top-level).
     * @param string $description Term description.
     * @return int|false Term ID on success, false on failure.
     */
    private static function insert_category( $name, $slug, $parent_id = 0, $description = '' ) {
        // Check if term already exists.
        $existing = get_term_by( 'slug', $slug, 'product_cat' );
        if ( $existing ) {
            return $existing->term_id;
        }

        $result = wp_insert_term( $name, 'product_cat', array(
            'slug'        => $slug,
            'parent'      => $parent_id,
            'description' => $description,
        ) );

        if ( is_wp_error( $result ) ) {
            return false;
        }

        return $result['term_id'];
    }
}
