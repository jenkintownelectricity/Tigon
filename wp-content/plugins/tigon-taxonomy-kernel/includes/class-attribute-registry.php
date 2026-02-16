<?php
/**
 * Tigon Attribute Registry — WooCommerce Product Attributes
 *
 * Registers all WooCommerce product attributes that map to the
 * golf cart specification matrix. These are the visible attributes
 * on single product pages.
 *
 * @package TigonTaxonomyKernel
 */

defined('ABSPATH') || exit;

class Tigon_Attribute_Registry {

    /**
     * WooCommerce product attributes definition
     * name => [label, type (select/text), order_by, has_archives]
     */
    public static function get_attributes() {
        return [
            // Physical Attributes
            'cart-color'       => ['Cart Color', 'select', 'menu_order', true],
            'seat-color'       => ['Seat Color', 'select', 'menu_order', true],
            'tire-rim-size'    => ['Tire & Rim Size', 'select', 'menu_order', true],
            'tire-type'        => ['Tire Type', 'select', 'menu_order', true],
            'passengers'       => ['Passengers', 'select', 'menu_order', true],

            // Performance
            'top-speed'        => ['Top Speed', 'select', 'menu_order', false],
            'range'            => ['Range (Miles)', 'select', 'menu_order', false],
            'horsepower'       => ['Horsepower', 'select', 'menu_order', false],
            'torque'           => ['Torque', 'select', 'menu_order', false],

            // Battery / Motor
            'battery-voltage'  => ['Battery Voltage', 'select', 'menu_order', false],
            'battery-amp-hours' => ['Battery Amp Hours', 'select', 'menu_order', false],
            'battery-type'     => ['Battery Type', 'select', 'menu_order', true],
            'battery-brand'    => ['Battery Brand', 'select', 'menu_order', true],
            'motor-power'      => ['Motor Power', 'select', 'menu_order', false],
            'charge-time'      => ['Charge Time', 'select', 'menu_order', false],

            // Dimensions
            'overall-length'   => ['Overall Length', 'select', 'menu_order', false],
            'overall-width'    => ['Overall Width', 'select', 'menu_order', false],
            'overall-height'   => ['Overall Height', 'select', 'menu_order', false],
            'wheelbase'        => ['Wheelbase', 'select', 'menu_order', false],
            'ground-clearance' => ['Ground Clearance', 'select', 'menu_order', false],
            'curb-weight'      => ['Curb Weight', 'select', 'menu_order', false],
            'payload-capacity' => ['Payload Capacity', 'select', 'menu_order', false],
            'towing-capacity'  => ['Towing Capacity', 'select', 'menu_order', false],

            // Features (boolean-like)
            'has-sound-system' => ['Sound System', 'select', 'menu_order', false],
            'is-lifted'        => ['Lifted', 'select', 'menu_order', false],
            'has-hitch'        => ['Hitch', 'select', 'menu_order', false],
            'has-extended-top' => ['Extended Top', 'select', 'menu_order', false],
            'has-windshield'   => ['Windshield', 'select', 'menu_order', false],
            'has-turn-signals' => ['Turn Signals', 'select', 'menu_order', false],
            'has-mirrors'      => ['Mirrors', 'select', 'menu_order', false],
            'has-seat-belts'   => ['Seat Belts', 'select', 'menu_order', false],
            'has-backup-camera' => ['Backup Camera', 'select', 'menu_order', false],
            'led-package'      => ['LED Package', 'select', 'menu_order', false],

            // Business
            'warranty-length'  => ['Warranty Length', 'select', 'menu_order', false],
            'financing'        => ['Financing Available', 'select', 'menu_order', false],
        ];
    }

    /**
     * Register all WooCommerce attributes
     */
    public static function register_all() {
        if (!function_exists('wc_create_attribute')) return;

        $existing = wc_get_attribute_taxonomies();
        $existing_slugs = [];
        foreach ($existing as $attr) {
            $existing_slugs[] = $attr->attribute_name;
        }

        foreach (self::get_attributes() as $slug => $config) {
            list($label, $type, $order_by, $has_archives) = $config;

            if (in_array($slug, $existing_slugs)) continue;

            wc_create_attribute([
                'name'         => $label,
                'slug'         => $slug,
                'type'         => $type,
                'order_by'     => $order_by,
                'has_archives' => $has_archives,
            ]);
        }
    }
}

// Register on init after WooCommerce loads
add_action('init', function () {
    if (class_exists('WooCommerce')) {
        Tigon_Attribute_Registry::register_all();
    }
}, 20);
