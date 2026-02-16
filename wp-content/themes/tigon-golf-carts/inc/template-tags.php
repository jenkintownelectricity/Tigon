<?php
/**
 * Template Tags — Reusable display helpers for Tigon Golf Carts
 *
 * @package TigonGolfCarts
 */

defined('ABSPATH') || exit;

/**
 * Display manufacturer badge for a product
 */
function tigon_manufacturer_badge($product_id = null) {
    $product_id = $product_id ?: get_the_ID();
    $manufacturers = wp_get_object_terms($product_id, 'manufacturers', ['fields' => 'all']);
    if (empty($manufacturers) || is_wp_error($manufacturers)) return;
    $mfg = $manufacturers[0];
    printf(
        '<a href="%s" class="tigon-cart-card__manufacturer">%s</a>',
        esc_url(get_term_link($mfg)),
        esc_html($mfg->name)
    );
}

/**
 * Display condition badge
 */
function tigon_condition_badge($product_id = null) {
    $product_id = $product_id ?: get_the_ID();
    $condition = get_post_meta($product_id, '_tigon_condition', true);
    if (!$condition) return;
    printf(
        '<span class="tigon-cart-card__badge tigon-cart-card__badge--%s">%s</span>',
        esc_attr($condition),
        esc_html(ucfirst($condition))
    );
}

/**
 * Display model taxonomy links
 */
function tigon_model_links($product_id = null) {
    $product_id = $product_id ?: get_the_ID();
    $models = wp_get_object_terms($product_id, 'models', ['fields' => 'all']);
    if (empty($models) || is_wp_error($models)) return;
    echo '<div class="tigon-model-links">';
    foreach ($models as $model) {
        printf(
            '<a href="%s" class="tigon-cart-card__tag">%s</a> ',
            esc_url(get_term_link($model)),
            esc_html($model->name)
        );
    }
    echo '</div>';
}

/**
 * Display location info
 */
function tigon_location_display($product_id = null) {
    $product_id = $product_id ?: get_the_ID();
    $locations = wp_get_object_terms($product_id, 'location', ['fields' => 'all']);
    if (empty($locations) || is_wp_error($locations)) return;
    $loc = $locations[0];
    printf(
        '<span class="tigon-cart-card__location">%s</span>',
        esc_html($loc->name)
    );
}

/**
 * Get full taxonomy breadcrumb for a product
 */
function tigon_taxonomy_breadcrumb($product_id = null) {
    $product_id = $product_id ?: get_the_ID();
    $parts = [];

    $mfg = wp_get_object_terms($product_id, 'manufacturers', ['fields' => 'names']);
    if (!empty($mfg)) $parts[] = $mfg[0];

    $model = wp_get_object_terms($product_id, 'models', ['fields' => 'names']);
    if (!empty($model)) $parts[] = $model[0];

    $year = get_post_meta($product_id, '_tigon_year', true);
    if ($year) $parts[] = $year;

    return implode(' > ', $parts);
}
