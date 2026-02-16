<?php
/**
 * Tigon Location — Registers the tigon_location custom taxonomy.
 *
 * @package Tigon_Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tigon_Location {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
    }

    public static function register_taxonomy() {
        // Implemented in Phase 7.
    }

    public static function seed() {
        // Implemented in Phase 7.
    }
}
