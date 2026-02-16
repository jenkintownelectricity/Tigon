<?php
/**
 * Groq Enrichment Pipeline — AI product data enrichment
 *
 * Uses Groq's LPU to fill missing taxonomy layers, generate descriptions,
 * suggest pricing, and enrich product data from minimal input.
 *
 * @package TigonGroqWorker
 */

defined('ABSPATH') || exit;

class Tigon_Groq_Enrichment_Pipeline {

    private $client;

    public function __construct() {
        $this->client = new Tigon_Groq_Client();
    }

    /**
     * Enrich a product with AI-generated data for empty taxonomy layers
     *
     * @param int $product_id
     * @return array|WP_Error Enrichment results
     */
    public function enrich($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) return new WP_Error('not_found', 'Product not found');

        // Get current DNA completeness
        if (class_exists('Tigon_DNA_Hash')) {
            $completeness = Tigon_DNA_Hash::completeness_score($product_id);
            if ($completeness >= 90) {
                return ['status' => 'already_complete', 'completeness' => $completeness];
            }
        }

        // Find missing layers
        $missing = $this->find_missing_layers($product_id);
        if (empty($missing)) {
            return ['status' => 'no_missing_layers'];
        }

        // Build enrichment prompt
        $known_data = $this->gather_known_data($product);
        $system = $this->get_enrichment_system_prompt();
        $prompt = $this->build_enrichment_prompt($known_data, $missing);

        $result = $this->client->full()->ask_json($prompt, $system, [
            'max_tokens'  => 4096,
            'temperature' => 0.15,
        ]);

        if (is_wp_error($result)) return $result;

        // Store enrichment data
        update_post_meta($product_id, '_tigon_groq_enrichment', $result);
        update_post_meta($product_id, '_tigon_groq_enriched_at', current_time('mysql'));

        return $result;
    }

    /**
     * Generate AI product description
     */
    public function generate_description($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) return new WP_Error('not_found', 'Product not found');

        $known_data = $this->gather_known_data($product);
        $data_json = wp_json_encode($known_data, JSON_PRETTY_PRINT);

        $system = "You are a premium golf cart copywriter for Tigon Golf Carts. Write compelling, SEO-optimized product descriptions that highlight features, performance, and value. Use the brand voice: professional, knowledgeable, enthusiastic about electric mobility. Include key specs naturally in the copy.";

        $prompt = <<<PROMPT
Write a product description for this golf cart. Include:
1. A compelling opening paragraph
2. Key features and specifications
3. Build quality highlights
4. Ideal use cases
5. A call to action mentioning 0% financing and 1-844-844-6638

Product data:
{$data_json}

Return JSON: {"short_description": "...", "full_description": "...", "seo_title": "...", "seo_description": "..."}
PROMPT;

        return $this->client->full()->ask_json($prompt, $system);
    }

    /**
     * Suggest similar products based on taxonomy overlap
     */
    public function suggest_related($product_id, $count = 6) {
        if (!class_exists('Tigon_DNA_Hash')) {
            return new WP_Error('dependency', 'Tigon Taxonomy Kernel required');
        }
        return Tigon_DNA_Hash::find_similar($product_id, 5, $count);
    }

    private function find_missing_layers($product_id) {
        if (!class_exists('Tigon_Taxonomy_Registry')) return [];

        $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
        $missing = [];

        foreach ($layers as $slug => $config) {
            $terms = wp_get_object_terms($product_id, $slug, ['fields' => 'ids']);
            if (is_wp_error($terms) || empty($terms)) {
                $missing[$slug] = $config[0]; // singular label
            }
        }

        return $missing;
    }

    private function gather_known_data($product) {
        $id = $product->get_id();
        $data = [
            'name'  => $product->get_name(),
            'sku'   => $product->get_sku(),
            'price' => $product->get_price(),
        ];

        // All taxonomy terms
        if (class_exists('Tigon_Taxonomy_Registry')) {
            $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
            foreach ($layers as $slug => $config) {
                $terms = wp_get_object_terms($id, $slug, ['fields' => 'names']);
                if (!is_wp_error($terms) && !empty($terms)) {
                    $data['taxonomies'][$slug] = $terms;
                }
            }
        }

        // Meta fields
        $meta_keys = ['_tigon_vin', '_tigon_serial', '_tigon_year', '_tigon_condition',
                      '_tigon_street_legal', '_tigon_electric'];
        foreach ($meta_keys as $key) {
            $val = get_post_meta($id, $key, true);
            if ($val) $data['meta'][str_replace('_tigon_', '', $key)] = $val;
        }

        // Attributes
        foreach ($product->get_attributes() as $attr) {
            $name = wc_attribute_label($attr->get_name());
            if ($attr->is_taxonomy()) {
                $values = wc_get_product_terms($id, $attr->get_name(), ['fields' => 'names']);
            } else {
                $values = $attr->get_options();
            }
            if (!empty($values)) $data['attributes'][$name] = $values;
        }

        return $data;
    }

    private function get_enrichment_system_prompt() {
        return <<<PROMPT
You are the Tigon Golf Cart Data Enrichment Engine. Given known data about a golf cart, infer and suggest values for missing taxonomy layers.

RULES:
1. Only suggest values you are reasonably confident about (>70% confidence)
2. Base inferences on the manufacturer, model, and year when available
3. Use industry-standard specs for known models
4. Mark each suggestion with a confidence score
5. Do NOT fabricate VIN numbers, serial numbers, or pricing
6. Return valid JSON
PROMPT;
    }

    private function build_enrichment_prompt($known_data, $missing_layers) {
        $known_json = wp_json_encode($known_data, JSON_PRETTY_PRINT);
        $missing_json = wp_json_encode($missing_layers, JSON_PRETTY_PRINT);

        return <<<PROMPT
Based on the known product data, suggest values for the missing taxonomy layers.

## KNOWN DATA:
{$known_json}

## MISSING LAYERS (taxonomy_slug => label):
{$missing_json}

Return JSON:
{
    "suggestions": {
        "taxonomy-slug": {
            "value": "suggested term",
            "confidence": 0.85,
            "reasoning": "why this value"
        }
    },
    "enrichment_summary": "Brief summary of what was enriched"
}
PROMPT;
    }
}

// REST endpoints for enrichment
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/groq/enrich/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => function ($request) {
            $pipeline = new Tigon_Groq_Enrichment_Pipeline();
            $result = $pipeline->enrich($request->get_param('id'));
            if (is_wp_error($result)) {
                return new WP_REST_Response(['error' => $result->get_error_message()], 400);
            }
            return new WP_REST_Response($result, 200);
        },
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        },
    ]);

    register_rest_route('tigon/v1', '/groq/describe/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => function ($request) {
            $pipeline = new Tigon_Groq_Enrichment_Pipeline();
            $result = $pipeline->generate_description($request->get_param('id'));
            if (is_wp_error($result)) {
                return new WP_REST_Response(['error' => $result->get_error_message()], 400);
            }
            return new WP_REST_Response($result, 200);
        },
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        },
    ]);
});
