<?php
/**
 * Add-On Data — Default add-on definitions per manufacturer/model
 *
 * Seeds the database with real Tigon Golf Cart add-on configurations
 * based on the existing mock.json options data.
 *
 * @package TigonWooAddons
 */

defined('ABSPATH') || exit;

class Tigon_Addon_Data {

    /**
     * Seed default add-on groups and addons
     */
    public static function seed_defaults() {
        // Universal Add-Ons (all carts)
        self::create_group_with_addons('Universal Accessories', '', '', [
            ['addon_name' => 'Side Mirrors (Pair)', 'addon_type' => 'checkbox', 'addon_price' => 89.00, 'addon_description' => 'Adjustable side mirrors for enhanced visibility'],
            ['addon_name' => 'Seat Belts (Set)', 'addon_type' => 'checkbox', 'addon_price' => 149.00, 'addon_description' => 'DOT-approved retractable seat belts'],
            ['addon_name' => 'Hitch Receiver (2")', 'addon_type' => 'checkbox', 'addon_price' => 129.00, 'addon_description' => 'Standard 2-inch receiver hitch'],
            ['addon_name' => 'Windshield', 'addon_type' => 'select', 'addon_options' => [
                ['label' => 'Folding Acrylic', 'value' => 'folding-acrylic', 'price' => 249.00],
                ['label' => 'Fixed Tinted', 'value' => 'fixed-tinted', 'price' => 299.00],
                ['label' => 'Fixed Clear', 'value' => 'fixed-clear', 'price' => 269.00],
            ]],
            ['addon_name' => 'LED Light Bar', 'addon_type' => 'checkbox', 'addon_price' => 199.00],
            ['addon_name' => 'Under Glow LED Kit', 'addon_type' => 'checkbox', 'addon_price' => 179.00, 'addon_description' => 'RGB under-body LED lighting'],
            ['addon_name' => 'Storage Cover', 'addon_type' => 'checkbox', 'addon_price' => 149.00, 'addon_description' => 'Weather-resistant full vehicle cover'],
            ['addon_name' => 'Cup Holders (Set of 4)', 'addon_type' => 'checkbox', 'addon_price' => 39.00],
            ['addon_name' => 'USB Charging Ports', 'addon_type' => 'checkbox', 'addon_price' => 59.00, 'addon_description' => 'Dual USB-A + USB-C ports'],
        ]);

        // Denago-Specific Add-Ons
        self::create_group_with_addons('Denago Nomad XL Options', 'denago-ev', 'nomad-xl', [
            ['addon_name' => 'Nomad XL Sound Package', 'addon_type' => 'select', 'addon_options' => [
                ['label' => 'Bluetooth Soundbar', 'value' => 'soundbar', 'price' => 349.00],
                ['label' => 'Full 4-Speaker + Subwoofer', 'value' => 'full-system', 'price' => 799.00],
                ['label' => 'ECOXGEAR SoundExtreme', 'value' => 'ecoxgear', 'price' => 599.00],
            ]],
            ['addon_name' => 'Nomad XL Lift Kit', 'addon_type' => 'select', 'addon_options' => [
                ['label' => '3" Lift', 'value' => '3-inch', 'price' => 399.00],
                ['label' => '6" Lift', 'value' => '6-inch', 'price' => 699.00],
            ]],
            ['addon_name' => 'Front Cargo Basket', 'addon_type' => 'checkbox', 'addon_price' => 189.00],
            ['addon_name' => 'Retractable Steps', 'addon_type' => 'checkbox', 'addon_price' => 249.00],
            ['addon_name' => 'Fender Flares', 'addon_type' => 'checkbox', 'addon_price' => 199.00],
            ['addon_name' => 'Brush Guard', 'addon_type' => 'checkbox', 'addon_price' => 179.00],
            ['addon_name' => 'Stake Sides', 'addon_type' => 'checkbox', 'addon_price' => 229.00],
        ]);

        // Denago Rover
        self::create_group_with_addons('Denago Rover XL Options', 'denago-ev', 'rover-xl', [
            ['addon_name' => 'Rover XL Sound Package', 'addon_type' => 'select', 'addon_options' => [
                ['label' => 'Bluetooth Soundbar', 'value' => 'soundbar', 'price' => 349.00],
                ['label' => 'Full System + Sub', 'value' => 'full-system', 'price' => 799.00],
            ]],
            ['addon_name' => 'Rover XL Enclosure', 'addon_type' => 'checkbox', 'addon_price' => 499.00],
            ['addon_name' => 'Rover XL Hitch Package', 'addon_type' => 'checkbox', 'addon_price' => 169.00],
        ]);

        // Evolution-Specific
        self::create_group_with_addons('Evolution D5 Options', 'evolution-electric-vehicles', 'd5', [
            ['addon_name' => 'D5 Enclosure', 'addon_type' => 'checkbox', 'addon_price' => 549.00],
            ['addon_name' => 'D5 Hitch System', 'addon_type' => 'checkbox', 'addon_price' => 149.00],
            ['addon_name' => '5-Passenger Enclosure', 'addon_type' => 'checkbox', 'addon_price' => 599.00],
            ['addon_name' => 'Fan System', 'addon_type' => 'checkbox', 'addon_price' => 299.00, 'addon_description' => 'Built-in fan cooling system'],
            ['addon_name' => 'Charger Upgrade (25A)', 'addon_type' => 'checkbox', 'addon_price' => 399.00],
        ]);

        // Evolution D3
        self::create_group_with_addons('Evolution D3 Options', 'evolution-electric-vehicles', 'd3', [
            ['addon_name' => 'Golf Bag Rack', 'addon_type' => 'checkbox', 'addon_price' => 189.00],
            ['addon_name' => '120V Inverter', 'addon_type' => 'checkbox', 'addon_price' => 349.00],
            ['addon_name' => 'Custom Steering Wheel', 'addon_type' => 'checkbox', 'addon_price' => 129.00],
        ]);

        // Epic-Specific
        self::create_group_with_addons('Epic Carts Options', 'epic-carts', '', [
            ['addon_name' => 'Armrests (4-Passenger)', 'addon_type' => 'checkbox', 'addon_price' => 119.00],
            ['addon_name' => 'Armrests (6-Passenger)', 'addon_type' => 'checkbox', 'addon_price' => 159.00],
            ['addon_name' => 'Interior Basket', 'addon_type' => 'checkbox', 'addon_price' => 99.00],
            ['addon_name' => '4-Passenger Storage Cover', 'addon_type' => 'checkbox', 'addon_price' => 149.00],
            ['addon_name' => '6-Passenger Storage Cover', 'addon_type' => 'checkbox', 'addon_price' => 179.00],
            ['addon_name' => 'Grab Bar Set', 'addon_type' => 'checkbox', 'addon_price' => 89.00],
        ]);

        // Sound System Add-Ons (all manufacturers)
        self::create_group_with_addons('Sound System Upgrades', '', '', [
            ['addon_name' => 'Sound System', 'addon_type' => 'select', 'addon_options' => [
                ['label' => 'Bluetooth Soundbar', 'value' => 'bt-soundbar', 'price' => 299.00],
                ['label' => '2-Speaker Basic', 'value' => '2-speaker', 'price' => 449.00],
                ['label' => '4-Speaker Premium', 'value' => '4-speaker', 'price' => 699.00],
                ['label' => 'Full System + Subwoofer', 'value' => 'full-sub', 'price' => 999.00],
            ]],
            ['addon_name' => 'Subwoofer Add-On', 'addon_type' => 'checkbox', 'addon_price' => 349.00],
        ]);

        // Speed/Performance
        self::create_group_with_addons('Performance Upgrades', '', '', [
            ['addon_name' => 'Speed Programmer', 'addon_type' => 'checkbox', 'addon_price' => 199.00, 'addon_description' => 'Unlock higher top speed'],
            ['addon_name' => 'Charger Upgrade', 'addon_type' => 'select', 'addon_options' => [
                ['label' => '15A Fast Charger', 'value' => '15a', 'price' => 249.00],
                ['label' => '25A Rapid Charger', 'value' => '25a', 'price' => 399.00],
            ]],
        ]);
    }

    private static function create_group_with_addons($group_name, $mfg_slug, $model_slug, $addons) {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . 'tigon_addon_groups', [
            'group_name'        => $group_name,
            'manufacturer_slug' => $mfg_slug,
            'model_slug'        => $model_slug,
            'applies_to'        => ($mfg_slug || $model_slug) ? 'specific' : 'all',
            'priority'          => $model_slug ? 5 : ($mfg_slug ? 10 : 20),
            'is_active'         => 1,
            'created_at'        => current_time('mysql'),
        ]);

        $group_id = $wpdb->insert_id;

        foreach ($addons as $i => $addon) {
            $wpdb->insert($wpdb->prefix . 'tigon_addons', [
                'group_id'          => $group_id,
                'addon_name'        => $addon['addon_name'],
                'addon_type'        => $addon['addon_type'] ?? 'checkbox',
                'addon_price'       => $addon['addon_price'] ?? 0,
                'addon_description' => $addon['addon_description'] ?? '',
                'addon_options'     => wp_json_encode($addon['addon_options'] ?? []),
                'is_required'       => $addon['is_required'] ?? 0,
                'sort_order'        => $i,
                'is_active'         => 1,
            ]);
        }
    }
}
