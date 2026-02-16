<?php
/**
 * Tigon Golf Carts Theme Functions
 *
 * @package TigonGolfCarts
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

define('TIGON_THEME_VERSION', '1.0.0');
define('TIGON_THEME_DIR', get_template_directory());
define('TIGON_THEME_URI', get_template_directory_uri());

/* ============================================
   THEME SETUP
   ============================================ */
add_action('after_setup_theme', function () {
    // Core WordPress support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('automatic-feed-links');

    // WooCommerce support
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    // Custom image sizes for golf cart inventory
    add_image_size('tigon-cart-card', 640, 480, true);
    add_image_size('tigon-cart-hero', 1200, 800, true);
    add_image_size('tigon-cart-thumb', 300, 225, true);
    add_image_size('tigon-manufacturer-logo', 400, 200, false);

    // Navigation menus
    register_nav_menus([
        'primary'       => __('Primary Navigation', 'tigon-golf-carts'),
        'inventory'     => __('Inventory Menu', 'tigon-golf-carts'),
        'manufacturers' => __('Manufacturers Menu', 'tigon-golf-carts'),
        'footer'        => __('Footer Navigation', 'tigon-golf-carts'),
        'mobile'        => __('Mobile Navigation', 'tigon-golf-carts'),
    ]);
});

/* ============================================
   ENQUEUE ASSETS
   ============================================ */
add_action('wp_enqueue_scripts', function () {
    // Google Fonts
    wp_enqueue_style(
        'tigon-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@600;700;800;900&display=swap',
        [],
        null
    );

    // Theme stylesheet
    wp_enqueue_style('tigon-theme', get_stylesheet_uri(), [], TIGON_THEME_VERSION);

    // Theme scripts
    wp_enqueue_script(
        'tigon-theme-js',
        TIGON_THEME_URI . '/assets/js/tigon-theme.js',
        ['jquery'],
        TIGON_THEME_VERSION,
        true
    );

    // Localize for AJAX
    wp_localize_script('tigon-theme-js', 'tigonData', [
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'restUrl'  => rest_url('tigon/v1/'),
        'nonce'    => wp_create_nonce('tigon_nonce'),
        'siteUrl'  => get_site_url(),
        'themeUrl' => TIGON_THEME_URI,
    ]);
});

/* ============================================
   WIDGET AREAS
   ============================================ */
add_action('widgets_init', function () {
    $sidebars = [
        'sidebar-shop'      => 'Shop Sidebar',
        'sidebar-cart-filter' => 'Cart Filter Sidebar',
        'footer-1'          => 'Footer Column 1',
        'footer-2'          => 'Footer Column 2',
        'footer-3'          => 'Footer Column 3',
        'footer-4'          => 'Footer Column 4',
    ];

    foreach ($sidebars as $id => $name) {
        register_sidebar([
            'name'          => __($name, 'tigon-golf-carts'),
            'id'            => $id,
            'before_widget' => '<div id="%1$s" class="tigon-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="tigon-widget__title">',
            'after_title'   => '</h3>',
        ]);
    }
});

/* ============================================
   WOOCOMMERCE CUSTOMIZATIONS
   ============================================ */

// Change products per page
add_filter('loop_shop_per_page', function () {
    return 24;
});

// Change product columns
add_filter('loop_shop_columns', function () {
    return 3;
});

// Ensure product taxonomies show in REST API
add_action('init', function () {
    // Make WooCommerce product categories/tags available in REST
    global $wp_taxonomies;
    if (isset($wp_taxonomies['product_cat'])) {
        $wp_taxonomies['product_cat']->show_in_rest = true;
    }
    if (isset($wp_taxonomies['product_tag'])) {
        $wp_taxonomies['product_tag']->show_in_rest = true;
    }
}, 25);

// Add custom fields to WooCommerce product data
add_action('woocommerce_product_options_general_product_data', function () {
    global $post;

    echo '<div class="options_group tigon-cart-fields">';
    echo '<h4 style="padding-left:12px;color:#c8a84e;">Tigon Cart DNA Fields</h4>';

    woocommerce_wp_text_input([
        'id'          => '_tigon_vin',
        'label'       => __('VIN Number', 'tigon-golf-carts'),
        'description' => 'Vehicle Identification Number',
        'desc_tip'    => true,
    ]);

    woocommerce_wp_text_input([
        'id'    => '_tigon_serial',
        'label' => __('Serial Number', 'tigon-golf-carts'),
    ]);

    woocommerce_wp_text_input([
        'id'    => '_tigon_year',
        'label' => __('Model Year', 'tigon-golf-carts'),
        'type'  => 'number',
    ]);

    woocommerce_wp_select([
        'id'      => '_tigon_condition',
        'label'   => __('Condition', 'tigon-golf-carts'),
        'options' => [
            ''     => 'Select',
            'new'  => 'New',
            'used' => 'Used',
            'cpo'  => 'Certified Pre-Owned',
        ],
    ]);

    woocommerce_wp_checkbox([
        'id'    => '_tigon_street_legal',
        'label' => __('Street Legal', 'tigon-golf-carts'),
    ]);

    woocommerce_wp_checkbox([
        'id'    => '_tigon_electric',
        'label' => __('Electric Powertrain', 'tigon-golf-carts'),
    ]);

    echo '</div>';
});

// Save custom product fields
add_action('woocommerce_process_product_meta', function ($post_id) {
    $fields = ['_tigon_vin', '_tigon_serial', '_tigon_year', '_tigon_condition', '_tigon_street_legal', '_tigon_electric'];
    foreach ($fields as $field) {
        $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
        update_post_meta($post_id, $field, $value);
    }
});

/* ============================================
   INCLUDE THEME MODULES
   ============================================ */
$theme_includes = [
    '/inc/template-tags.php',
    '/inc/woocommerce-hooks.php',
    '/inc/taxonomy-display.php',
];

foreach ($theme_includes as $file) {
    $filepath = TIGON_THEME_DIR . $file;
    if (file_exists($filepath)) {
        require_once $filepath;
    }
}

/* ============================================
   REST API EXTENSIONS
   ============================================ */
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/inventory', [
        'methods'             => 'GET',
        'callback'            => 'tigon_get_inventory',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('tigon/v1', '/taxonomy-tree', [
        'methods'             => 'GET',
        'callback'            => 'tigon_get_taxonomy_tree',
        'permission_callback' => '__return_true',
    ]);
});

function tigon_get_inventory($request) {
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => $request->get_param('per_page') ?: 24,
        'paged'          => $request->get_param('page') ?: 1,
        'post_status'    => 'publish',
        'tax_query'      => [],
    ];

    // Filter by manufacturer
    if ($manufacturer = $request->get_param('manufacturer')) {
        $args['tax_query'][] = [
            'taxonomy' => 'manufacturers',
            'field'    => 'slug',
            'terms'    => $manufacturer,
        ];
    }

    // Filter by model
    if ($model = $request->get_param('model')) {
        $args['tax_query'][] = [
            'taxonomy' => 'models',
            'field'    => 'slug',
            'terms'    => $model,
        ];
    }

    $query = new WP_Query($args);
    $products = [];

    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if (!$product) continue;

        $products[] = [
            'id'           => $product->get_id(),
            'name'         => $product->get_name(),
            'slug'         => $product->get_slug(),
            'price'        => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price'   => $product->get_sale_price(),
            'image'        => wp_get_attachment_url($product->get_image_id()),
            'permalink'    => $product->get_permalink(),
            'manufacturer' => wp_get_object_terms($post->ID, 'manufacturers', ['fields' => 'names']),
            'model'        => wp_get_object_terms($post->ID, 'models', ['fields' => 'names']),
            'year'         => get_post_meta($post->ID, '_tigon_year', true),
            'condition'    => get_post_meta($post->ID, '_tigon_condition', true),
            'street_legal' => get_post_meta($post->ID, '_tigon_street_legal', true),
            'electric'     => get_post_meta($post->ID, '_tigon_electric', true),
        ];
    }

    return new WP_REST_Response([
        'products' => $products,
        'total'    => $query->found_posts,
        'pages'    => $query->max_num_pages,
    ], 200);
}

function tigon_get_taxonomy_tree($request) {
    $taxonomy = $request->get_param('taxonomy') ?: 'product_cat';
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'orderby'    => 'name',
    ]);

    if (is_wp_error($terms)) {
        return new WP_REST_Response(['error' => $terms->get_error_message()], 400);
    }

    $tree = tigon_build_term_tree($terms);
    return new WP_REST_Response($tree, 200);
}

function tigon_build_term_tree($terms, $parent_id = 0) {
    $tree = [];
    foreach ($terms as $term) {
        if ($term->parent == $parent_id) {
            $children = tigon_build_term_tree($terms, $term->term_id);
            $node = [
                'id'       => $term->term_id,
                'name'     => $term->name,
                'slug'     => $term->slug,
                'count'    => $term->count,
                'children' => $children,
            ];
            $tree[] = $node;
        }
    }
    return $tree;
}
