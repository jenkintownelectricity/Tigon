<?php
/**
 * Architect Core — Central orchestration engine
 *
 * The "brain" that coordinates all AI operations. Decides which
 * pipeline to run, manages state, and handles error recovery.
 *
 * @package TigonLLMArchitect
 */

defined('ABSPATH') || exit;

class Tigon_Architect_Core {

    private static $instance = null;
    private $log = [];

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Run a complete pipeline for a product
     *
     * @param string $pipeline_name Pipeline identifier
     * @param int $product_id Product to process
     * @param array $options Pipeline options
     * @return array Pipeline execution results
     */
    public function run_pipeline($pipeline_name, $product_id, $options = []) {
        $this->log("Starting pipeline: {$pipeline_name} for product #{$product_id}");

        $start_time = microtime(true);
        $results = ['pipeline' => $pipeline_name, 'product_id' => $product_id, 'steps' => []];

        switch ($pipeline_name) {
            case 'intake':
                $results['steps'] = $this->run_intake_pipeline($product_id, $options);
                break;
            case 'quality':
                $results['steps'] = $this->run_quality_pipeline($product_id, $options);
                break;
            case 'optimize':
                $results['steps'] = $this->run_optimization_pipeline($product_id, $options);
                break;
            case 'full':
                $results['steps'] = array_merge(
                    $this->run_intake_pipeline($product_id, $options),
                    $this->run_quality_pipeline($product_id, $options),
                    $this->run_optimization_pipeline($product_id, $options)
                );
                break;
            default:
                $results['error'] = "Unknown pipeline: {$pipeline_name}";
        }

        $results['execution_time'] = round(microtime(true) - $start_time, 3) . 's';
        $results['log'] = $this->log;

        // Store pipeline results
        update_post_meta($product_id, '_tigon_architect_last_pipeline', $pipeline_name);
        update_post_meta($product_id, '_tigon_architect_last_run', current_time('mysql'));
        update_post_meta($product_id, '_tigon_architect_results', $results);

        return $results;
    }

    /**
     * Intake Pipeline: New product ingestion
     * DMS Import → Classify → Enrich → Describe → Generate DNA → Publish
     */
    private function run_intake_pipeline($product_id, $options) {
        $steps = [];

        // Step 1: Classify across 50 layers
        $this->log("Step 1: Taxonomy classification");
        if (class_exists('Tigon_Groq_Taxonomy_Classifier')) {
            $classifier = new Tigon_Groq_Taxonomy_Classifier();
            $classify_result = $classifier->classify($product_id, true);
            $steps[] = [
                'step' => 'classify',
                'status' => is_wp_error($classify_result) ? 'failed' : 'success',
                'result' => is_wp_error($classify_result) ? $classify_result->get_error_message() : 'classified',
            ];
        }

        // Step 2: Enrich missing data
        $this->log("Step 2: Data enrichment");
        if (class_exists('Tigon_Groq_Enrichment_Pipeline')) {
            $pipeline = new Tigon_Groq_Enrichment_Pipeline();
            $enrich_result = $pipeline->enrich($product_id);
            $steps[] = [
                'step' => 'enrich',
                'status' => is_wp_error($enrich_result) ? 'failed' : 'success',
                'result' => is_wp_error($enrich_result) ? $enrich_result->get_error_message() : 'enriched',
            ];
        }

        // Step 3: Generate description if missing
        $this->log("Step 3: Description generation");
        $content = get_post_field('post_content', $product_id);
        if (empty(trim($content)) && class_exists('Tigon_Groq_Enrichment_Pipeline')) {
            $pipeline = new Tigon_Groq_Enrichment_Pipeline();
            $desc_result = $pipeline->generate_description($product_id);
            if (!is_wp_error($desc_result) && isset($desc_result['full_description'])) {
                wp_update_post([
                    'ID'           => $product_id,
                    'post_content' => $desc_result['full_description'],
                    'post_excerpt' => $desc_result['short_description'] ?? '',
                ]);
            }
            $steps[] = [
                'step' => 'describe',
                'status' => is_wp_error($desc_result) ? 'failed' : 'success',
            ];
        }

        // Step 4: Generate DNA hash
        $this->log("Step 4: DNA hash generation");
        if (class_exists('Tigon_DNA_Hash')) {
            $hash = Tigon_DNA_Hash::generate($product_id);
            $completeness = Tigon_DNA_Hash::completeness_score($product_id);
            $steps[] = [
                'step'         => 'dna_hash',
                'status'       => 'success',
                'hash'         => $hash,
                'completeness' => $completeness . '%',
            ];
        }

        return $steps;
    }

    /**
     * Quality Pipeline: Validate and fix data
     */
    private function run_quality_pipeline($product_id, $options) {
        $steps = [];

        // Step 1: Validate classifications
        $this->log("Quality Step 1: Validation");
        if (class_exists('Tigon_Taxonomy_Worker')) {
            $validation = Tigon_Taxonomy_Worker::validate_product($product_id);
            $steps[] = [
                'step' => 'validate',
                'status' => is_wp_error($validation) ? 'failed' : 'success',
                'result' => $validation,
            ];
        }

        // Step 2: Check DNA completeness
        $this->log("Quality Step 2: Completeness check");
        if (class_exists('Tigon_DNA_Hash')) {
            $completeness = Tigon_DNA_Hash::completeness_score($product_id);
            $steps[] = [
                'step'   => 'completeness_check',
                'status' => $completeness >= 50 ? 'pass' : 'needs_work',
                'score'  => $completeness . '%',
            ];
        }

        return $steps;
    }

    /**
     * Optimization Pipeline: Improve product performance
     */
    private function run_optimization_pipeline($product_id, $options) {
        $steps = [];

        // Step 1: SEO optimization
        $this->log("Optimize Step 1: SEO");
        if (class_exists('Tigon_Description_Worker')) {
            $seo = Tigon_Description_Worker::generate_seo($product_id);
            $steps[] = [
                'step' => 'seo',
                'status' => is_wp_error($seo) ? 'failed' : 'success',
                'result' => $seo,
            ];
        }

        // Step 2: Find similar products for cross-sell
        $this->log("Optimize Step 2: Cross-sell");
        if (class_exists('Tigon_Similarity_Worker')) {
            $similar = Tigon_Similarity_Worker::find_similar_ai($product_id, 4);
            $steps[] = [
                'step' => 'cross_sell',
                'status' => is_wp_error($similar) ? 'failed' : 'success',
                'similar_products' => is_wp_error($similar) ? [] : ($similar['products'] ?? []),
            ];

            // Set WooCommerce cross-sells if we got results
            if (!is_wp_error($similar) && !empty($similar['products'])) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $product->set_cross_sell_ids($similar['products']);
                    $product->save();
                }
            }
        }

        return $steps;
    }

    /**
     * Batch process multiple products through a pipeline
     */
    public function batch_pipeline($pipeline_name, $product_ids, $options = []) {
        if (class_exists('Tigon_Groq_Job_Queue')) {
            // Use the job queue for batch processing
            $job_ids = [];
            foreach ($product_ids as $pid) {
                $job_ids[] = Tigon_Groq_Job_Queue::enqueue('pipeline_' . $pipeline_name, $pid, $options);
            }
            return ['status' => 'queued', 'count' => count($job_ids)];
        }

        // Direct processing fallback
        $results = [];
        foreach ($product_ids as $pid) {
            $results[$pid] = $this->run_pipeline($pipeline_name, $pid, $options);
        }
        return $results;
    }

    private function log($message) {
        $this->log[] = '[' . current_time('H:i:s') . '] ' . $message;
    }
}

// REST API endpoints for Architect
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/architect/pipeline/(?P<pipeline>[a-z]+)/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            $architect = Tigon_Architect_Core::instance();
            $result = $architect->run_pipeline(
                $request->get_param('pipeline'),
                $request->get_param('id'),
                $request->get_params()
            );
            return new WP_REST_Response($result, 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);

    register_rest_route('tigon/v1', '/architect/batch', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            $architect = Tigon_Architect_Core::instance();
            return new WP_REST_Response(
                $architect->batch_pipeline(
                    $request->get_param('pipeline'),
                    $request->get_param('product_ids') ?: []
                ),
                200
            );
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);
});
