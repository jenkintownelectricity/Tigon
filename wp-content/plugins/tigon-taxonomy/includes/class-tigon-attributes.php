<?php
/**
 * Tigon Attributes — Registers WooCommerce global product attributes and their terms.
 *
 * Creates all 9 global attributes (pa_ prefix) defined in the taxonomy seed:
 * Manufacturer, Seating Capacity, Voltage, Vehicle Type, Power Type,
 * Drive Type, Color, Condition, Features.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Attributes {

    /**
     * Seed global product attributes and their default terms.
     *
     * Idempotent: checks for existing attributes by slug before creating.
     * Uses wc_create_attribute() for attribute creation and wp_insert_term() for terms.
     */
    public static function seed() {
        $seed_data = Tigon_Categories::get_seed_data();

        if ( empty( $seed_data['attributes'] ) ) {
            return;
        }

        foreach ( $seed_data['attributes'] as $attribute ) {
            $attribute_id = self::ensure_attribute( $attribute );

            if ( ! $attribute_id || is_wp_error( $attribute_id ) ) {
                continue;
            }

            // Insert default terms for this attribute.
            if ( ! empty( $attribute['terms'] ) ) {
                $taxonomy = 'pa_' . $attribute['slug'];

                // Ensure taxonomy is registered before inserting terms.
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    register_taxonomy( $taxonomy, 'product', array(
                        'hierarchical' => false,
                        'label'        => $attribute['name'],
                    ) );
                }

                foreach ( $attribute['terms'] as $term_name ) {
                    self::ensure_attribute_term( $term_name, $taxonomy );
                }
            }
        }
    }

    /**
     * Ensure a WooCommerce global attribute exists.
     *
     * @param array $attribute Attribute data from seed file.
     * @return int|WP_Error Attribute ID on success.
     */
    private static function ensure_attribute( $attribute ) {
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
            'has_archives' => isset( $attribute['has_archives'] ) ? (bool) $attribute['has_archives'] : true,
        );

        $result = wc_create_attribute( $args );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Register the taxonomy immediately so we can insert terms.
        $taxonomy = 'pa_' . $attribute['slug'];
        register_taxonomy( $taxonomy, 'product', array(
            'hierarchical' => false,
            'label'        => $attribute['name'],
        ) );

        return $result;
    }

    /**
     * Ensure an attribute term exists within a pa_ taxonomy.
     *
     * @param string $name     Term name.
     * @param string $taxonomy Taxonomy slug (e.g., pa_color).
     * @return int|WP_Error Term ID on success.
     */
    private static function ensure_attribute_term( $name, $taxonomy ) {
        $existing = get_term_by( 'name', $name, $taxonomy );

        if ( $existing && ! is_wp_error( $existing ) ) {
            return (int) $existing->term_id;
        }

        $result = wp_insert_term( $name, $taxonomy );

        if ( is_wp_error( $result ) ) {
            if ( 'term_exists' === $result->get_error_code() ) {
                return (int) $result->get_error_data();
            }
            return $result;
        }

        return (int) $result['term_id'];
    }
}
