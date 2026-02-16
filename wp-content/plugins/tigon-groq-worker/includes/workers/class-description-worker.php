<?php
/**
 * Description Worker — AI-generated product copy
 *
 * @package TigonGroqWorker
 */

defined('ABSPATH') || exit;

class Tigon_Description_Worker {

    /**
     * Generate and apply descriptions for products missing descriptions
     */
    public static function generate_missing_descriptions() {
        $products = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ]);

        $needs_description = [];
        foreach ($products as $pid) {
            $content = get_post_field('post_content', $pid);
            if (empty(trim($content)) || strlen($content) < 50) {
                $needs_description[] = $pid;
            }
        }

        if (empty($needs_description)) return ['status' => 'all_described'];

        $job_ids = Tigon_Groq_Job_Queue::bulk_enqueue('describe', $needs_description);

        return [
            'status'  => 'queued',
            'count'   => count($job_ids),
            'job_ids' => $job_ids,
        ];
    }

    /**
     * Generate SEO metadata for a product
     */
    public static function generate_seo($product_id) {
        $client = new Tigon_Groq_Client();
        $product = wc_get_product($product_id);
        if (!$product) return new WP_Error('not_found', 'Product not found');

        $name = $product->get_name();
        $price = $product->get_price();
        $mfg = wp_get_object_terms($product_id, 'manufacturers', ['fields' => 'names']);
        $mfg_name = !empty($mfg) ? $mfg[0] : '';
        $model = wp_get_object_terms($product_id, 'models', ['fields' => 'names']);
        $model_name = !empty($model) ? $model[0] : '';

        $prompt = "Generate SEO metadata for this golf cart:\n\nName: {$name}\nManufacturer: {$mfg_name}\nModel: {$model_name}\nPrice: \${$price}\n\nReturn JSON: {\"seo_title\": \"(max 60 chars)\", \"meta_description\": \"(max 155 chars)\", \"focus_keyword\": \"...\", \"secondary_keywords\": [\"...\"], \"og_title\": \"...\", \"og_description\": \"...\"}";

        return $client->fast()->ask_json($prompt, "You are an SEO expert for Tigon Golf Carts (tigongolfcarts.com). Generate SEO-optimized metadata.");
    }
}

// REST endpoints
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/groq/worker/generate-descriptions', [
        'methods'  => 'POST',
        'callback' => function () {
            return new WP_REST_Response(Tigon_Description_Worker::generate_missing_descriptions(), 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);

    register_rest_route('tigon/v1', '/groq/worker/seo/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            return new WP_REST_Response(
                Tigon_Description_Worker::generate_seo($request->get_param('id')),
                200
            );
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);
});
