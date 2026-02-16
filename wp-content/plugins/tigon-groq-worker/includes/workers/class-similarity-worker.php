<?php
/**
 * Similarity Worker — AI-powered similar product discovery
 *
 * @package TigonGroqWorker
 */

defined('ABSPATH') || exit;

class Tigon_Similarity_Worker {

    /**
     * Find semantically similar products using AI
     */
    public static function find_similar_ai($product_id, $count = 6) {
        $client = new Tigon_Groq_Client();
        $product = wc_get_product($product_id);
        if (!$product) return new WP_Error('not_found', 'Product not found');

        // First try DNA-based similarity
        if (class_exists('Tigon_DNA_Hash')) {
            $dna_similar = Tigon_DNA_Hash::find_similar($product_id, 3, $count);
            if (!empty($dna_similar)) {
                return [
                    'method'   => 'dna_hash',
                    'products' => $dna_similar,
                ];
            }
        }

        // Fallback to AI-powered similarity
        $all_products = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'post__not_in'   => [$product_id],
            'fields'         => 'ids',
        ]);

        if (empty($all_products)) return ['method' => 'none', 'products' => []];

        $candidates = [];
        foreach (array_slice($all_products, 0, 50) as $pid) {
            $p = wc_get_product($pid);
            if (!$p) continue;
            $mfg = wp_get_object_terms($pid, 'manufacturers', ['fields' => 'names']);
            $mdl = wp_get_object_terms($pid, 'models', ['fields' => 'names']);
            $candidates[] = [
                'id'           => $pid,
                'name'         => $p->get_name(),
                'price'        => $p->get_price(),
                'manufacturer' => $mfg[0] ?? '',
                'model'        => $mdl[0] ?? '',
            ];
        }

        $source_mfg = wp_get_object_terms($product_id, 'manufacturers', ['fields' => 'names']);
        $source_mdl = wp_get_object_terms($product_id, 'models', ['fields' => 'names']);

        $source_json = wp_json_encode([
            'name'         => $product->get_name(),
            'price'        => $product->get_price(),
            'manufacturer' => $source_mfg[0] ?? '',
            'model'        => $source_mdl[0] ?? '',
        ]);
        $candidates_json = wp_json_encode($candidates);

        $prompt = "Given this source product:\n{$source_json}\n\nFind the {$count} most similar products from this list:\n{$candidates_json}\n\nReturn JSON: {\"similar_ids\": [id1, id2, ...], \"reasoning\": \"...\"}";

        $result = $client->fast()->ask_json($prompt, "You are a golf cart similarity engine. Rank products by similarity based on manufacturer, model family, price range, and features.");

        if (is_wp_error($result)) return $result;

        return [
            'method'   => 'ai_similarity',
            'products' => $result['similar_ids'] ?? [],
            'reasoning' => $result['reasoning'] ?? '',
        ];
    }
}
