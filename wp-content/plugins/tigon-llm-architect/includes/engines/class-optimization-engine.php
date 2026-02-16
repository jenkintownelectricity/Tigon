<?php
/**
 * Optimization Engine — Product performance optimization
 *
 * @package TigonLLMArchitect
 */

defined('ABSPATH') || exit;

class Tigon_Optimization_Engine {

    /**
     * Analyze inventory and generate optimization recommendations
     */
    public static function analyze_inventory() {
        $products = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ]);

        $analysis = [
            'total_products'     => count($products),
            'by_manufacturer'    => [],
            'by_condition'       => ['new' => 0, 'used' => 0, 'cpo' => 0, 'unknown' => 0],
            'by_location'        => [],
            'price_ranges'       => ['under_5k' => 0, '5k_10k' => 0, '10k_15k' => 0, '15k_25k' => 0, 'over_25k' => 0],
            'missing_data'       => 0,
            'avg_completeness'   => 0,
        ];

        $completeness_scores = [];

        foreach ($products as $pid) {
            $product = wc_get_product($pid);
            if (!$product) continue;

            // Manufacturer breakdown
            $mfg = wp_get_object_terms($pid, 'manufacturers', ['fields' => 'names']);
            $mfg_name = !empty($mfg) ? $mfg[0] : 'Unassigned';
            $analysis['by_manufacturer'][$mfg_name] = ($analysis['by_manufacturer'][$mfg_name] ?? 0) + 1;

            // Condition
            $condition = get_post_meta($pid, '_tigon_condition', true) ?: 'unknown';
            $analysis['by_condition'][$condition] = ($analysis['by_condition'][$condition] ?? 0) + 1;

            // Location
            $loc = wp_get_object_terms($pid, 'location', ['fields' => 'names']);
            $loc_name = !empty($loc) ? $loc[0] : 'Unassigned';
            $analysis['by_location'][$loc_name] = ($analysis['by_location'][$loc_name] ?? 0) + 1;

            // Price ranges
            $price = floatval($product->get_price());
            if ($price < 5000) $analysis['price_ranges']['under_5k']++;
            elseif ($price < 10000) $analysis['price_ranges']['5k_10k']++;
            elseif ($price < 15000) $analysis['price_ranges']['10k_15k']++;
            elseif ($price < 25000) $analysis['price_ranges']['15k_25k']++;
            else $analysis['price_ranges']['over_25k']++;

            // Completeness
            if (class_exists('Tigon_DNA_Hash')) {
                $score = Tigon_DNA_Hash::completeness_score($pid);
                $completeness_scores[] = $score;
                if ($score < 30) $analysis['missing_data']++;
            }
        }

        $analysis['avg_completeness'] = !empty($completeness_scores)
            ? round(array_sum($completeness_scores) / count($completeness_scores), 1)
            : 0;

        arsort($analysis['by_manufacturer']);
        arsort($analysis['by_location']);

        return $analysis;
    }
}

// REST endpoint
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/optimize/inventory-analysis', [
        'methods'  => 'GET',
        'callback' => function () {
            return new WP_REST_Response(Tigon_Optimization_Engine::analyze_inventory(), 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);
});
