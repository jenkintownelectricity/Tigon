<?php
/**
 * Groq Taxonomy Classifier — AI-powered 50-layer classification
 *
 * Takes a golf cart product and uses Groq's LPU to automatically
 * classify it across all 50 taxonomy layers. Lightning fast.
 *
 * @package TigonGroqWorker
 */

defined('ABSPATH') || exit;

class Tigon_Groq_Taxonomy_Classifier {

    private $client;

    public function __construct() {
        $this->client = new Tigon_Groq_Client();
    }

    /**
     * Classify a product across all 50 taxonomy layers
     *
     * @param int $product_id WooCommerce product ID
     * @param bool $apply Whether to automatically apply the classifications
     * @return array|WP_Error Classification results
     */
    public function classify($product_id, $apply = false) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', 'Product not found');
        }

        // Build product context
        $context = $this->build_product_context($product);

        // Get available taxonomy terms
        $available_terms = $this->get_available_terms();

        $system_prompt = $this->get_system_prompt();
        $user_prompt = $this->build_classification_prompt($context, $available_terms);

        // Use full model for accuracy
        $result = $this->client->full()->ask_json($user_prompt, $system_prompt, [
            'max_tokens' => 8192,
            'temperature' => 0.05,
        ]);

        if (is_wp_error($result)) return $result;

        // Log the classification
        update_post_meta($product_id, '_tigon_groq_classification', $result);
        update_post_meta($product_id, '_tigon_groq_classified_at', current_time('mysql'));

        // Apply classifications if requested
        if ($apply && isset($result['classifications'])) {
            $this->apply_classifications($product_id, $result['classifications']);
        }

        return $result;
    }

    /**
     * Batch classify multiple products
     */
    public function batch_classify($product_ids, $apply = false) {
        $results = [];
        foreach ($product_ids as $pid) {
            $results[$pid] = $this->classify($pid, $apply);
        }
        return $results;
    }

    /**
     * Build product context string from all available data
     */
    private function build_product_context($product) {
        $id = $product->get_id();
        $context = [
            'name'            => $product->get_name(),
            'description'     => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku'             => $product->get_sku(),
            'price'           => $product->get_price(),
            'regular_price'   => $product->get_regular_price(),
            'attributes'      => [],
            'existing_terms'  => [],
            'meta'            => [],
        ];

        // Get all product attributes
        foreach ($product->get_attributes() as $attr) {
            $name = wc_attribute_label($attr->get_name());
            if ($attr->is_taxonomy()) {
                $values = wc_get_product_terms($id, $attr->get_name(), ['fields' => 'names']);
            } else {
                $values = $attr->get_options();
            }
            $context['attributes'][$name] = $values;
        }

        // Get existing taxonomy terms
        $taxonomies = ['manufacturers', 'models', 'model-family', 'vehicle-class', 'location',
                       'drivetrain', 'added-features', 'sound-systems', 'inventory-status'];
        foreach ($taxonomies as $tax) {
            $terms = wp_get_object_terms($id, $tax, ['fields' => 'names']);
            if (!is_wp_error($terms) && !empty($terms)) {
                $context['existing_terms'][$tax] = $terms;
            }
        }

        // Get custom meta
        $meta_keys = ['_tigon_vin', '_tigon_serial', '_tigon_year', '_tigon_condition',
                      '_tigon_street_legal', '_tigon_electric'];
        foreach ($meta_keys as $key) {
            $val = get_post_meta($id, $key, true);
            if ($val) $context['meta'][str_replace('_tigon_', '', $key)] = $val;
        }

        return $context;
    }

    /**
     * Get all available terms organized by taxonomy
     */
    private function get_available_terms() {
        if (!class_exists('Tigon_Taxonomy_Registry')) return [];

        $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
        $available = [];

        foreach ($layers as $slug => $config) {
            $terms = get_terms([
                'taxonomy'   => $slug,
                'hide_empty' => false,
                'fields'     => 'names',
            ]);
            if (!is_wp_error($terms) && !empty($terms)) {
                $available[$slug] = [
                    'label'  => $config[0],
                    'layer'  => $config[3],
                    'terms'  => array_slice($terms, 0, 50), // Limit for token budget
                ];
            }
        }

        return $available;
    }

    private function get_system_prompt() {
        return <<<PROMPT
You are the Tigon Golf Cart DNA Classifier — an expert system that classifies golf carts across a 50-layer taxonomy.

You work for Tigon Golf Carts, a premium dealer of electric golf carts, street legal vehicles, and low-speed transportation vehicles. Brands include Denago EV, Epic Carts, Evolution, Icon EV, Club Car, Yamaha, EZGO, and Royal EV.

Your job is to analyze a golf cart product and assign it to the correct taxonomy terms across all applicable layers of the 50-layer classification system.

RULES:
1. Only assign terms that actually exist in the provided available terms lists
2. If you can confidently infer a classification from the product data, include it
3. If insufficient data exists for a layer, omit that layer entirely
4. The PRIMARY CATEGORY is always the MODEL — this must be classified first
5. Manufacturer must always be classified if the product name contains a brand
6. Be precise — do not guess wildly, but DO make reasonable inferences
7. Return valid JSON with a "classifications" object mapping taxonomy_slug => [term_names]
8. Include a "confidence" score (0.0-1.0) for each classification
9. Include a "reasoning" field explaining your logic
PROMPT;
    }

    private function build_classification_prompt($context, $available_terms) {
        $context_json = wp_json_encode($context, JSON_PRETTY_PRINT);
        $terms_json = wp_json_encode($available_terms, JSON_PRETTY_PRINT);

        return <<<PROMPT
Classify this golf cart product across the 50-layer taxonomy.

## PRODUCT DATA:
{$context_json}

## AVAILABLE TAXONOMY TERMS:
{$terms_json}

Return JSON in this exact format:
{
    "classifications": {
        "taxonomy-slug": ["term1", "term2"],
        ...
    },
    "confidence_scores": {
        "taxonomy-slug": 0.95,
        ...
    },
    "primary_model": "Model Name",
    "primary_manufacturer": "Brand Name",
    "dna_summary": "One-line summary of this cart's identity",
    "reasoning": "Brief explanation of classification logic"
}
PROMPT;
    }

    /**
     * Apply classifications to a product
     */
    private function apply_classifications($product_id, $classifications) {
        foreach ($classifications as $taxonomy => $terms) {
            if (!taxonomy_exists($taxonomy)) continue;
            if (!is_array($terms)) $terms = [$terms];

            $term_ids = [];
            foreach ($terms as $term_name) {
                $term = get_term_by('name', $term_name, $taxonomy);
                if ($term) {
                    $term_ids[] = $term->term_id;
                } else {
                    // Auto-create term if it doesn't exist
                    $new_term = wp_insert_term($term_name, $taxonomy);
                    if (!is_wp_error($new_term)) {
                        $term_ids[] = $new_term['term_id'];
                    }
                }
            }

            if (!empty($term_ids)) {
                wp_set_object_terms($product_id, $term_ids, $taxonomy, true); // append
            }
        }

        // Regenerate DNA hash after classification
        if (class_exists('Tigon_DNA_Hash')) {
            Tigon_DNA_Hash::generate($product_id);
        }
    }
}

// Auto-classify on product creation if enabled
add_action('woocommerce_new_product', function ($product_id) {
    $settings = get_option('tigon_groq_settings', []);
    if (!empty($settings['auto_classify']) && !empty($settings['api_key'])) {
        $classifier = new Tigon_Groq_Taxonomy_Classifier();
        $classifier->classify($product_id, true);
    }
});

// REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/groq/classify/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => function ($request) {
            $classifier = new Tigon_Groq_Taxonomy_Classifier();
            $apply = $request->get_param('apply') ?? false;
            $result = $classifier->classify($request->get_param('id'), $apply);
            if (is_wp_error($result)) {
                return new WP_REST_Response(['error' => $result->get_error_message()], 400);
            }
            return new WP_REST_Response($result, 200);
        },
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        },
    ]);

    register_rest_route('tigon/v1', '/groq/batch-classify', [
        'methods'             => 'POST',
        'callback'            => function ($request) {
            $ids = $request->get_param('product_ids') ?? [];
            $apply = $request->get_param('apply') ?? false;
            $classifier = new Tigon_Groq_Taxonomy_Classifier();
            $results = $classifier->batch_classify($ids, $apply);
            return new WP_REST_Response($results, 200);
        },
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        },
    ]);
});
