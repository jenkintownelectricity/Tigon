<?php
/**
 * Taxonomy Worker — Dedicated worker for taxonomy classification jobs
 *
 * Handles both single and bulk taxonomy classification operations
 * using Groq's ultra-fast LPU inference.
 *
 * @package TigonGroqWorker
 */

defined('ABSPATH') || exit;

class Tigon_Taxonomy_Worker {

    /**
     * Classify all unclassified products
     */
    public static function classify_all_unclassified() {
        $products = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => '_tigon_groq_classified_at',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        if (empty($products)) return ['status' => 'no_unclassified_products'];

        $job_ids = Tigon_Groq_Job_Queue::bulk_enqueue('classify', $products, ['apply' => true]);

        return [
            'status'  => 'queued',
            'count'   => count($job_ids),
            'job_ids' => $job_ids,
        ];
    }

    /**
     * Reclassify products that have changed since last classification
     */
    public static function reclassify_stale($days = 30) {
        global $wpdb;

        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $products = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_tigon_groq_classified_at'
             WHERE p.post_type = 'product'
             AND p.post_status = 'publish'
             AND (pm.meta_value IS NULL OR pm.meta_value < %s)
             ORDER BY p.post_modified DESC",
            $cutoff
        ));

        if (empty($products)) return ['status' => 'all_current'];

        $job_ids = Tigon_Groq_Job_Queue::bulk_enqueue('classify', $products, ['apply' => true]);

        return [
            'status'  => 'queued',
            'count'   => count($job_ids),
        ];
    }

    /**
     * Validate existing classifications (check for inconsistencies)
     */
    public static function validate_product($product_id) {
        $client = new Tigon_Groq_Client();

        $product = wc_get_product($product_id);
        if (!$product) return new WP_Error('not_found', 'Product not found');

        // Get current taxonomy assignments
        $current = [];
        if (class_exists('Tigon_Taxonomy_Registry')) {
            $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
            foreach ($layers as $slug => $config) {
                $terms = wp_get_object_terms($product_id, $slug, ['fields' => 'names']);
                if (!is_wp_error($terms) && !empty($terms)) {
                    $current[$slug] = $terms;
                }
            }
        }

        $current_json = wp_json_encode($current, JSON_PRETTY_PRINT);
        $system = "You are a golf cart taxonomy validator. Check if the assigned taxonomy terms are consistent and correct for the given product. Flag any inconsistencies.";
        $prompt = "Product: {$product->get_name()}\n\nCurrent classifications:\n{$current_json}\n\nReturn JSON: {\"valid\": true/false, \"issues\": [\"issue description\"], \"suggestions\": {\"taxonomy\": \"correct_value\"}}";

        return $client->fast()->ask_json($prompt, $system);
    }
}

// REST endpoint for worker operations
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/groq/worker/classify-all', [
        'methods'  => 'POST',
        'callback' => function () {
            return new WP_REST_Response(Tigon_Taxonomy_Worker::classify_all_unclassified(), 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);

    register_rest_route('tigon/v1', '/groq/worker/reclassify-stale', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            $days = $request->get_param('days') ?? 30;
            return new WP_REST_Response(Tigon_Taxonomy_Worker::reclassify_stale($days), 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);

    register_rest_route('tigon/v1', '/groq/worker/validate/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            return new WP_REST_Response(
                Tigon_Taxonomy_Worker::validate_product($request->get_param('id')),
                200
            );
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);
});
