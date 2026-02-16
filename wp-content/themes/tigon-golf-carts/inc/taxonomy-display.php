<?php
/**
 * Taxonomy Display — Template functions for Tigon taxonomy pages
 *
 * @package TigonGolfCarts
 */

defined('ABSPATH') || exit;

/**
 * Build a hierarchical taxonomy tree for display
 */
function tigon_get_taxonomy_hierarchy($taxonomy, $parent = 0, $depth = 0, $max_depth = 50) {
    if ($depth >= $max_depth) return [];

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'parent'     => $parent,
        'hide_empty' => false,
        'orderby'    => 'name',
    ]);

    if (is_wp_error($terms) || empty($terms)) return [];

    $tree = [];
    foreach ($terms as $term) {
        $children = tigon_get_taxonomy_hierarchy($taxonomy, $term->term_id, $depth + 1, $max_depth);
        $tree[] = [
            'term'     => $term,
            'depth'    => $depth,
            'children' => $children,
        ];
    }
    return $tree;
}

/**
 * Render taxonomy tree as nested HTML
 */
function tigon_render_taxonomy_tree($tree, $taxonomy_slug = '') {
    if (empty($tree)) return;

    echo '<ul class="tigon-taxonomy-tree">';
    foreach ($tree as $node) {
        $term = $node['term'];
        $link = get_term_link($term);
        $has_children = !empty($node['children']);

        printf(
            '<li class="tigon-taxonomy-tree__item depth-%d %s">',
            $node['depth'],
            $has_children ? 'has-children' : ''
        );

        printf(
            '<a href="%s" class="tigon-taxonomy-tree__link">%s <span class="count">(%d)</span></a>',
            esc_url($link),
            esc_html($term->name),
            $term->count
        );

        if ($has_children) {
            tigon_render_taxonomy_tree($node['children'], $taxonomy_slug);
        }

        echo '</li>';
    }
    echo '</ul>';
}

/**
 * Get the full taxonomy path for a term (all ancestors)
 */
function tigon_get_term_path($term_id, $taxonomy) {
    $path = [];
    $term = get_term($term_id, $taxonomy);

    while ($term && !is_wp_error($term)) {
        array_unshift($path, $term);
        if ($term->parent === 0) break;
        $term = get_term($term->parent, $taxonomy);
    }

    return $path;
}

/**
 * Render taxonomy breadcrumb trail
 */
function tigon_taxonomy_breadcrumb_trail($term_id, $taxonomy) {
    $path = tigon_get_term_path($term_id, $taxonomy);
    $parts = [];

    foreach ($path as $term) {
        $parts[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(get_term_link($term)),
            esc_html($term->name)
        );
    }

    return implode(' &raquo; ', $parts);
}
