<?php
/**
 * Tigon DNA Hash — Unique Cart Fingerprint Generator
 *
 * Generates a unique DNA hash for each golf cart based on all 50 taxonomy layers.
 * This creates a deterministic fingerprint that can be used for:
 * - Duplicate detection
 * - Configuration matching
 * - Similar vehicle recommendations
 * - Build specification exports
 *
 * @package TigonTaxonomyKernel
 */

defined('ABSPATH') || exit;

class Tigon_DNA_Hash {

    /**
     * Generate DNA hash for a product
     *
     * @param int $product_id WooCommerce product ID
     * @return string SHA-256 hash representing the cart's complete DNA
     */
    public static function generate($product_id) {
        $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
        $dna_components = [];

        foreach ($layers as $taxonomy_slug => $config) {
            $terms = wp_get_object_terms($product_id, $taxonomy_slug, ['fields' => 'slugs']);
            if (!is_wp_error($terms) && !empty($terms)) {
                sort($terms);
                $dna_components[$config[3]] = implode('|', $terms); // config[3] = layer number
            } else {
                $dna_components[$config[3]] = 'null';
            }
        }

        // Add meta fields to DNA
        $meta_keys = ['_tigon_vin', '_tigon_serial', '_tigon_year', '_tigon_condition'];
        foreach ($meta_keys as $key) {
            $value = get_post_meta($product_id, $key, true);
            $dna_components[] = $value ?: 'null';
        }

        // Sort by layer number
        ksort($dna_components);

        // Generate deterministic hash
        $dna_string = implode('::', $dna_components);
        $hash = hash('sha256', $dna_string);

        // Store as post meta
        update_post_meta($product_id, '_tigon_dna_hash', $hash);
        update_post_meta($product_id, '_tigon_dna_string', $dna_string);
        update_post_meta($product_id, '_tigon_dna_generated', current_time('mysql'));

        return $hash;
    }

    /**
     * Get human-readable DNA breakdown for a product
     *
     * @param int $product_id
     * @return array Associative array of layer_name => [terms]
     */
    public static function get_dna_breakdown($product_id) {
        $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
        $breakdown = [];

        foreach ($layers as $taxonomy_slug => $config) {
            $terms = wp_get_object_terms($product_id, $taxonomy_slug, ['fields' => 'names']);
            if (!is_wp_error($terms) && !empty($terms)) {
                $breakdown[] = [
                    'layer'    => $config[3],
                    'name'     => $config[0],
                    'taxonomy' => $taxonomy_slug,
                    'values'   => $terms,
                ];
            }
        }

        usort($breakdown, function ($a, $b) {
            return $a['layer'] - $b['layer'];
        });

        return $breakdown;
    }

    /**
     * Find products with similar DNA (matching on top N layers)
     *
     * @param int $product_id
     * @param int $match_layers Number of layers to match (default: 5)
     * @param int $limit Max results
     * @return array Array of product IDs
     */
    public static function find_similar($product_id, $match_layers = 5, $limit = 10) {
        $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
        $tax_query = ['relation' => 'AND'];
        $layer_count = 0;

        foreach ($layers as $taxonomy_slug => $config) {
            if ($layer_count >= $match_layers) break;

            $terms = wp_get_object_terms($product_id, $taxonomy_slug, ['fields' => 'ids']);
            if (!is_wp_error($terms) && !empty($terms)) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy_slug,
                    'field'    => 'term_id',
                    'terms'    => $terms,
                ];
                $layer_count++;
            }
        }

        if ($layer_count < 2) return [];

        $query = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'post__not_in'   => [$product_id],
            'post_status'    => 'publish',
            'tax_query'      => $tax_query,
            'fields'         => 'ids',
        ]);

        return $query->posts;
    }

    /**
     * Get DNA completeness score (0-100%)
     *
     * @param int $product_id
     * @return float Percentage of layers that have at least one term
     */
    public static function completeness_score($product_id) {
        $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
        $total = count($layers);
        $filled = 0;

        foreach ($layers as $taxonomy_slug => $config) {
            $terms = wp_get_object_terms($product_id, $taxonomy_slug, ['fields' => 'ids']);
            if (!is_wp_error($terms) && !empty($terms)) {
                $filled++;
            }
        }

        $score = ($filled / $total) * 100;
        update_post_meta($product_id, '_tigon_dna_completeness', round($score, 1));

        return round($score, 1);
    }
}

// Auto-generate DNA hash on product save
add_action('woocommerce_process_product_meta', function ($product_id) {
    Tigon_DNA_Hash::generate($product_id);
    Tigon_DNA_Hash::completeness_score($product_id);
});

// REST API endpoint for DNA
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/dna/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => function ($request) {
            $product_id = $request->get_param('id');
            return new WP_REST_Response([
                'product_id'  => $product_id,
                'hash'        => Tigon_DNA_Hash::generate($product_id),
                'breakdown'   => Tigon_DNA_Hash::get_dna_breakdown($product_id),
                'completeness' => Tigon_DNA_Hash::completeness_score($product_id),
                'similar'     => Tigon_DNA_Hash::find_similar($product_id),
            ], 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
