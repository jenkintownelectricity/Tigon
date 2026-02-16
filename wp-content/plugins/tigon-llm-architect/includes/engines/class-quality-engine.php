<?php
/**
 * Quality Engine — Data quality checks and automated fixes
 *
 * @package TigonLLMArchitect
 */

defined('ABSPATH') || exit;

class Tigon_Quality_Engine {

    /**
     * Run full quality audit on all products
     */
    public static function audit_all() {
        $products = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ]);

        $report = [
            'total_products' => count($products),
            'issues'         => [],
            'scores'         => [],
        ];

        foreach ($products as $pid) {
            $issues = self::audit_product($pid);
            if (!empty($issues)) {
                $report['issues'][$pid] = $issues;
            }
            if (class_exists('Tigon_DNA_Hash')) {
                $report['scores'][$pid] = Tigon_DNA_Hash::completeness_score($pid);
            }
        }

        $report['products_with_issues'] = count($report['issues']);
        $report['avg_completeness'] = !empty($report['scores'])
            ? round(array_sum($report['scores']) / count($report['scores']), 1) . '%'
            : 'N/A';

        update_option('tigon_quality_last_audit', current_time('mysql'));
        update_option('tigon_quality_last_report', $report);

        return $report;
    }

    /**
     * Audit a single product
     */
    public static function audit_product($product_id) {
        $issues = [];

        $product = wc_get_product($product_id);
        if (!$product) return ['Product not found'];

        // Check required fields
        if (empty($product->get_name()) || strlen($product->get_name()) < 5) {
            $issues[] = 'Product name too short or missing';
        }

        if (!$product->get_regular_price()) {
            $issues[] = 'No regular price set';
        }

        // Check manufacturer
        $mfg = wp_get_object_terms($product_id, 'manufacturers', ['fields' => 'ids']);
        if (empty($mfg) || is_wp_error($mfg)) {
            $issues[] = 'No manufacturer assigned';
        }

        // Check model (PRIMARY CATEGORY)
        $model = wp_get_object_terms($product_id, 'models', ['fields' => 'ids']);
        if (empty($model) || is_wp_error($model)) {
            $issues[] = 'No model assigned (PRIMARY CATEGORY missing)';
        }

        // Check for product image
        if (!$product->get_image_id()) {
            $issues[] = 'No product image';
        }

        // Check description
        if (empty(trim($product->get_description()))) {
            $issues[] = 'No product description';
        }

        // Check condition meta
        $condition = get_post_meta($product_id, '_tigon_condition', true);
        if (!$condition) {
            $issues[] = 'Condition (new/used) not set';
        }

        return $issues;
    }
}

// REST endpoint
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/quality/audit', [
        'methods'  => 'POST',
        'callback' => function () {
            return new WP_REST_Response(Tigon_Quality_Engine::audit_all(), 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);

    register_rest_route('tigon/v1', '/quality/audit/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => function ($request) {
            return new WP_REST_Response(
                Tigon_Quality_Engine::audit_product($request->get_param('id')),
                200
            );
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);
});
