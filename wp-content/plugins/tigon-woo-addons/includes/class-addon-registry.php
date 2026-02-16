<?php
/**
 * Add-On Registry — Manages add-on groups and their manufacturer/model bindings
 *
 * @package TigonWooAddons
 */

defined('ABSPATH') || exit;

class Tigon_Addon_Registry {

    /**
     * Get all add-on groups for a specific product based on its manufacturer/model
     *
     * @param int $product_id
     * @return array Add-on groups with their addons
     */
    public static function get_addons_for_product($product_id) {
        global $wpdb;

        $manufacturers = wp_get_object_terms($product_id, 'manufacturers', ['fields' => 'slugs']);
        $models = wp_get_object_terms($product_id, 'models', ['fields' => 'slugs']);

        $mfg_slug = !empty($manufacturers) ? $manufacturers[0] : '';
        $model_slug = !empty($models) ? $models[0] : '';

        $groups_table = $wpdb->prefix . 'tigon_addon_groups';
        $addons_table = $wpdb->prefix . 'tigon_addons';

        // Get matching groups (specific manufacturer+model, manufacturer-only, or universal)
        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$groups_table}
             WHERE is_active = 1
             AND (
                 (manufacturer_slug = %s AND model_slug = %s)
                 OR (manufacturer_slug = %s AND (model_slug IS NULL OR model_slug = ''))
                 OR (applies_to = 'all' AND (manufacturer_slug IS NULL OR manufacturer_slug = ''))
             )
             ORDER BY priority ASC",
            $mfg_slug, $model_slug, $mfg_slug
        ));

        if (empty($groups)) return [];

        $result = [];
        foreach ($groups as $group) {
            $addons = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$addons_table} WHERE group_id = %d AND is_active = 1 ORDER BY sort_order ASC",
                $group->id
            ));

            // Parse addon options (JSON stored)
            foreach ($addons as &$addon) {
                $addon->addon_options = json_decode($addon->addon_options, true) ?: [];
            }

            $result[] = [
                'group' => $group,
                'addons' => $addons,
            ];
        }

        return $result;
    }

    /**
     * Create a new add-on group
     */
    public static function create_group($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'tigon_addon_groups', [
            'group_name'        => sanitize_text_field($data['group_name']),
            'manufacturer_slug' => sanitize_text_field($data['manufacturer_slug'] ?? ''),
            'model_slug'        => sanitize_text_field($data['model_slug'] ?? ''),
            'applies_to'        => sanitize_text_field($data['applies_to'] ?? 'all'),
            'priority'          => absint($data['priority'] ?? 10),
            'is_active'         => 1,
            'created_at'        => current_time('mysql'),
        ]);
    }

    /**
     * Add an addon to a group
     */
    public static function create_addon($group_id, $data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'tigon_addons', [
            'group_id'          => absint($group_id),
            'addon_name'        => sanitize_text_field($data['addon_name']),
            'addon_type'        => sanitize_text_field($data['addon_type'] ?? 'checkbox'),
            'addon_price'       => floatval($data['addon_price'] ?? 0),
            'addon_description' => sanitize_textarea_field($data['addon_description'] ?? ''),
            'addon_options'     => wp_json_encode($data['addon_options'] ?? []),
            'is_required'       => absint($data['is_required'] ?? 0),
            'sort_order'        => absint($data['sort_order'] ?? 0),
            'is_active'         => 1,
        ]);
    }
}
