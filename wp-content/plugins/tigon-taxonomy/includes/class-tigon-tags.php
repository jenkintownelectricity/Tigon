<?php
/**
 * WooCommerce Product Tag registration.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Tags {

    /**
     * Seed default product tags from taxonomy-seed.json.
     */
    public static function seed() {
        $seed_file = TIGON_TAXONOMY_PLUGIN_DIR . 'data/taxonomy-seed.json';
        if ( ! file_exists( $seed_file ) ) {
            return;
        }

        $data = json_decode( file_get_contents( $seed_file ), true );
        if ( empty( $data['tags'] ) ) {
            return;
        }

        foreach ( $data['tags'] as $group ) {
            if ( empty( $group['terms'] ) ) {
                continue;
            }
            foreach ( $group['terms'] as $tag_name ) {
                if ( ! term_exists( $tag_name, 'product_tag' ) ) {
                    wp_insert_term( $tag_name, 'product_tag' );
                }
            }
        }
    }
}
