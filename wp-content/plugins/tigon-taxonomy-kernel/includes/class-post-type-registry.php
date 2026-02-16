<?php
/**
 * Tigon Post Type Registry — Custom Post Types
 *
 * Registers CPTs beyond WooCommerce's 'product' for specialized content:
 * - tigon_service: Service records and maintenance logs
 * - tigon_build: Custom build specifications
 * - tigon_location: Dealership locations with full meta
 * - tigon_review: Customer reviews and testimonials
 * - tigon_spec_sheet: Manufacturer spec sheets
 * - tigon_warranty: Warranty claims and tracking
 *
 * @package TigonTaxonomyKernel
 */

defined('ABSPATH') || exit;

class Tigon_Post_Type_Registry {

    public static function get_post_types() {
        return [
            'tigon_service' => [
                'singular'    => 'Service Record',
                'plural'      => 'Service Records',
                'icon'        => 'dashicons-admin-tools',
                'supports'    => ['title', 'editor', 'thumbnail', 'custom-fields', 'revisions'],
                'public'      => false,
                'show_ui'     => true,
                'has_archive' => false,
                'description' => 'Golf cart service and maintenance records',
                'taxonomies'  => ['manufacturers', 'models', 'location'],
            ],
            'tigon_build' => [
                'singular'    => 'Custom Build',
                'plural'      => 'Custom Builds',
                'icon'        => 'dashicons-hammer',
                'supports'    => ['title', 'editor', 'thumbnail', 'custom-fields', 'excerpt', 'revisions'],
                'public'      => true,
                'show_ui'     => true,
                'has_archive' => true,
                'description' => 'Custom golf cart build specifications and configurations',
                'taxonomies'  => ['manufacturers', 'models', 'model-family', 'trim-level', 'body-style'],
            ],
            'tigon_location' => [
                'singular'    => 'Dealership',
                'plural'      => 'Dealerships',
                'icon'        => 'dashicons-location',
                'supports'    => ['title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'],
                'public'      => true,
                'show_ui'     => true,
                'has_archive' => true,
                'description' => 'Tigon Golf Cart dealership locations',
                'taxonomies'  => ['location'],
            ],
            'tigon_review' => [
                'singular'    => 'Customer Review',
                'plural'      => 'Customer Reviews',
                'icon'        => 'dashicons-star-filled',
                'supports'    => ['title', 'editor', 'thumbnail', 'custom-fields'],
                'public'      => true,
                'show_ui'     => true,
                'has_archive' => true,
                'description' => 'Customer reviews and testimonials',
                'taxonomies'  => ['manufacturers', 'models', 'location'],
            ],
            'tigon_spec_sheet' => [
                'singular'    => 'Spec Sheet',
                'plural'      => 'Spec Sheets',
                'icon'        => 'dashicons-media-document',
                'supports'    => ['title', 'editor', 'thumbnail', 'custom-fields', 'revisions'],
                'public'      => true,
                'show_ui'     => true,
                'has_archive' => true,
                'description' => 'Manufacturer specification sheets and technical documents',
                'taxonomies'  => ['manufacturers', 'models', 'model-family', 'model-year'],
            ],
            'tigon_warranty' => [
                'singular'    => 'Warranty Record',
                'plural'      => 'Warranty Records',
                'icon'        => 'dashicons-shield',
                'supports'    => ['title', 'editor', 'custom-fields', 'revisions'],
                'public'      => false,
                'show_ui'     => true,
                'has_archive' => false,
                'description' => 'Warranty claims, tracking, and coverage records',
                'taxonomies'  => ['manufacturers', 'models', 'warranty-tier', 'location'],
            ],
        ];
    }

    /**
     * Register all custom post types
     */
    public static function register_all() {
        foreach (self::get_post_types() as $slug => $config) {
            self::register_post_type($slug, $config);
        }
    }

    private static function register_post_type($slug, $config) {
        $labels = [
            'name'               => $config['plural'],
            'singular_name'      => $config['singular'],
            'add_new'            => "Add New {$config['singular']}",
            'add_new_item'       => "Add New {$config['singular']}",
            'edit_item'          => "Edit {$config['singular']}",
            'new_item'           => "New {$config['singular']}",
            'view_item'          => "View {$config['singular']}",
            'search_items'       => "Search {$config['plural']}",
            'not_found'          => "No {$config['plural']} found",
            'not_found_in_trash' => "No {$config['plural']} found in Trash",
            'menu_name'          => $config['plural'],
        ];

        register_post_type($slug, [
            'labels'       => $labels,
            'description'  => $config['description'],
            'public'       => $config['public'],
            'show_ui'      => $config['show_ui'],
            'show_in_rest' => true,
            'has_archive'  => $config['has_archive'],
            'menu_icon'    => $config['icon'],
            'supports'     => $config['supports'],
            'taxonomies'   => $config['taxonomies'] ?? [],
            'rewrite'      => [
                'slug'       => str_replace('tigon_', '', $slug),
                'with_front' => false,
            ],
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ]);
    }
}

add_action('init', ['Tigon_Post_Type_Registry', 'register_all'], 6);
