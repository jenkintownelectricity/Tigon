<?php
/**
 * WooCommerce Hooks — Tigon Golf Carts Customizations
 *
 * @package TigonGolfCarts
 */

defined('ABSPATH') || exit;

// Remove default WooCommerce wrappers
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

// Add Tigon-branded wrappers
add_action('woocommerce_before_main_content', function () {
    echo '<div class="tigon-woo-wrapper">';
});
add_action('woocommerce_after_main_content', function () {
    echo '</div>';
});

// Remove default product meta (category/tag display on single)
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

// Add manufacturer + model meta after price
add_action('woocommerce_single_product_summary', function () {
    global $product;
    $mfgs = wp_get_object_terms($product->get_id(), 'manufacturers', ['fields' => 'names']);
    $models_list = wp_get_object_terms($product->get_id(), 'models', ['fields' => 'names']);

    if (!empty($mfgs) || !empty($models_list)) {
        echo '<div class="tigon-product-meta" style="margin:1rem 0;font-size:0.9rem;">';
        if (!empty($mfgs)) {
            echo '<span style="color:var(--tigon-gray);">Manufacturer:</span> <strong>' . esc_html(implode(', ', $mfgs)) . '</strong><br>';
        }
        if (!empty($models_list)) {
            echo '<span style="color:var(--tigon-gray);">Model:</span> <strong>' . esc_html(implode(', ', $models_list)) . '</strong>';
        }
        echo '</div>';
    }
}, 25);

// Filter products by custom taxonomies on shop page
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_taxonomy())) {
        $tax_query = $query->get('tax_query') ?: [];

        if (!empty($_GET['filter_manufacturer'])) {
            $tax_query[] = [
                'taxonomy' => 'manufacturers',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['filter_manufacturer']),
            ];
        }

        if (!empty($_GET['filter_model'])) {
            $tax_query[] = [
                'taxonomy' => 'models',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['filter_model']),
            ];
        }

        if (!empty($_GET['filter_class'])) {
            $tax_query[] = [
                'taxonomy' => 'vehicle-class',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['filter_class']),
            ];
        }

        if (!empty($_GET['filter_location'])) {
            $tax_query[] = [
                'taxonomy' => 'location',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['filter_location']),
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $query->set('tax_query', $tax_query);
        }

        // Filter by condition meta
        if (!empty($_GET['filter_condition'])) {
            $meta_query = $query->get('meta_query') ?: [];
            $meta_query[] = [
                'key'   => '_tigon_condition',
                'value' => sanitize_text_field($_GET['filter_condition']),
            ];
            $query->set('meta_query', $meta_query);
        }
    }
});

// Register query vars for filters
add_filter('query_vars', function ($vars) {
    $vars[] = 'filter_manufacturer';
    $vars[] = 'filter_model';
    $vars[] = 'filter_class';
    $vars[] = 'filter_location';
    $vars[] = 'filter_condition';
    return $vars;
});
