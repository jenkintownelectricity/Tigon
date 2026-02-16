<?php
/**
 * Tigon Categories — WooCommerce product category registration.
 *
 * Reads taxonomy-seed.json and creates manufacturer parent categories
 * and model child categories in the WooCommerce product_cat taxonomy.
 * Idempotent: does not duplicate existing terms.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Categories {

    /**
     * Load the seed data from taxonomy-seed.json.
     *
     * @return array|false Decoded JSON data or false on failure.
     */
    public static function load_seed_data() {
        $seed_file = TIGON_TAXONOMY_PLUGIN_DIR . 'data/taxonomy-seed.json';
        if ( ! file_exists( $seed_file ) ) {
            return false;
        }
        $json = file_get_contents( $seed_file );
        $data = json_decode( $json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return false;
        }
        return $data;
    }

    /**
     * Seed all manufacturer and model categories from taxonomy-seed.json.
     * Called on plugin activation.
     */
    public static function seed() {
        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return;
        }

        $data = self::load_seed_data();
        if ( ! $data || empty( $data['manufacturers'] ) ) {
            return;
        }

        foreach ( $data['manufacturers'] as $manufacturer ) {
            $parent_id = self::create_term(
                $manufacturer['name'],
                $manufacturer['slug'],
                0,
                isset( $manufacturer['description'] ) ? $manufacturer['description'] : ''
            );

            if ( ! $parent_id ) {
                continue;
            }

            if ( ! empty( $manufacturer['models'] ) ) {
                foreach ( $manufacturer['models'] as $model ) {
                    self::create_term(
                        $model['name'],
                        $model['slug'],
                        $parent_id,
                        ''
                    );
                }
            }
        }
    }

    /**
     * Create a product_cat term if it does not already exist.
     *
     * @param string $name        Term name.
     * @param string $slug        Term slug.
     * @param int    $parent_id   Parent term ID (0 for top-level).
     * @param string $description Term description.
     * @return int|false Term ID on success, false on failure.
     */
    private static function create_term( $name, $slug, $parent_id = 0, $description = '' ) {
        $existing = get_term_by( 'slug', $slug, 'product_cat' );
        if ( $existing ) {
            return $existing->term_id;
        }

        $args = array(
            'slug'        => $slug,
            'parent'      => $parent_id,
            'description' => $description,
        );

        $result = wp_insert_term( $name, 'product_cat', $args );

        if ( is_wp_error( $result ) ) {
            // If the term exists under a different slug, try to retrieve it.
            if ( $result->get_error_code() === 'term_exists' ) {
                $term_id = $result->get_error_data( 'term_exists' );
                return (int) $term_id;
            }
            return false;
        }

        return (int) $result['term_id'];
    }
}
