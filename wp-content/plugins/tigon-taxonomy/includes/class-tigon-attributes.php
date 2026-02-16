<?php
/**
 * Tigon Attributes — WooCommerce global product attribute registration.
 *
 * Registers 9 global product attributes (pa_ prefix) and seeds their
 * default terms from taxonomy-seed.json. Idempotent.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Attributes {

    /**
     * Seed all global WooCommerce product attributes and their terms.
     * Called on plugin activation.
     */
    public static function seed() {
        if ( ! function_exists( 'wc_create_attribute' ) ) {
            return;
        }

        $data = Tigon_Categories::load_seed_data();
        if ( ! $data || empty( $data['attributes'] ) ) {
            return;
        }

        foreach ( $data['attributes'] as $attribute ) {
            $attr_id = self::create_attribute( $attribute );

            if ( $attr_id && ! empty( $attribute['terms'] ) ) {
                $taxonomy = 'pa_' . $attribute['slug'];

                // Ensure the taxonomy is registered for this request.
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    register_taxonomy( $taxonomy, 'product', array(
                        'hierarchical' => false,
                        'labels'       => array( 'name' => $attribute['name'] ),
                        'public'       => true,
                        'rewrite'      => array( 'slug' => $attribute['slug'] ),
                    ) );
                }

                foreach ( $attribute['terms'] as $term_name ) {
                    if ( ! term_exists( $term_name, $taxonomy ) ) {
                        wp_insert_term( $term_name, $taxonomy );
                    }
                }
            }
        }
    }

    /**
     * Create a WooCommerce global attribute if it does not already exist.
     *
     * @param array $attribute Attribute data from seed JSON.
     * @return int|false Attribute ID on success, false on failure.
     */
    private static function create_attribute( $attribute ) {
        // Check if attribute already exists.
        $existing_id = wc_attribute_taxonomy_id_by_name( $attribute['slug'] );
        if ( $existing_id ) {
            return $existing_id;
        }

        $args = array(
            'name'         => $attribute['name'],
            'slug'         => $attribute['slug'],
            'type'         => isset( $attribute['type'] ) ? $attribute['type'] : 'select',
            'order_by'     => 'menu_order',
            'has_archives' => isset( $attribute['has_archives'] ) ? (bool) $attribute['has_archives'] : false,
        );

        $result = wc_create_attribute( $args );

        if ( is_wp_error( $result ) ) {
            return false;
        }

        return (int) $result;
    }
}
