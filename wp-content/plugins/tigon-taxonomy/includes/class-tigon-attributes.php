<?php
/**
 * WooCommerce Global Attribute registration.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Attributes {

    /**
     * Seed global attributes and their terms from taxonomy-seed.json.
     */
    public static function seed() {
        $seed_file = TIGON_TAXONOMY_PLUGIN_DIR . 'data/taxonomy-seed.json';
        if ( ! file_exists( $seed_file ) ) {
            return;
        }

        $data = json_decode( file_get_contents( $seed_file ), true );
        if ( empty( $data['attributes'] ) ) {
            return;
        }

        foreach ( $data['attributes'] as $attr ) {
            self::register_attribute( $attr );
        }
    }

    /**
     * Register a single global WooCommerce attribute and its terms.
     *
     * @param array $attr Attribute data from seed file.
     */
    private static function register_attribute( $attr ) {
        if ( ! function_exists( 'wc_create_attribute' ) ) {
            return;
        }

        $slug = sanitize_title( $attr['slug'] );

        // Check if attribute already exists.
        $existing = wc_attribute_taxonomy_id_by_name( $slug );
        if ( $existing ) {
            $attribute_id = $existing;
        } else {
            $attribute_id = wc_create_attribute( array(
                'name'         => $attr['name'],
                'slug'         => $slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ) );

            if ( is_wp_error( $attribute_id ) ) {
                return;
            }

            // Register the taxonomy so terms can be inserted in the same request.
            $taxonomy_name = wc_attribute_taxonomy_name( $slug );
            if ( ! taxonomy_exists( $taxonomy_name ) ) {
                register_taxonomy( $taxonomy_name, 'product', array(
                    'hierarchical' => false,
                    'labels'       => array( 'name' => $attr['name'] ),
                    'show_ui'      => false,
                    'query_var'    => true,
                    'rewrite'      => false,
                ) );
            }
        }

        // Insert default terms.
        if ( ! empty( $attr['terms'] ) ) {
            $taxonomy_name = wc_attribute_taxonomy_name( $slug );
            foreach ( $attr['terms'] as $term_name ) {
                if ( ! term_exists( $term_name, $taxonomy_name ) ) {
                    wp_insert_term( $term_name, $taxonomy_name );
                }
            }
        }
    }
}
