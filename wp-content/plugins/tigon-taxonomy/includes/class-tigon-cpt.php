<?php
/**
 * Tigon CPT — Registers the tigon_manufacturer custom post type.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_CPT {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
    }

    public static function register_post_type() {
        // Implemented in Phase 6.
    }
}
