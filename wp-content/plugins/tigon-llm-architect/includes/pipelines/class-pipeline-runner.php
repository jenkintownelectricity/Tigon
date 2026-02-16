<?php
/**
 * Pipeline Runner — Executes multi-step AI pipelines
 *
 * @package TigonLLMArchitect
 */

defined('ABSPATH') || exit;

class Tigon_Pipeline_Runner {

    private $pipeline_id;
    private $steps = [];
    private $results = [];
    private $status = 'pending';
    private $start_time;

    public function __construct($pipeline_id = null) {
        $this->pipeline_id = $pipeline_id ?: wp_generate_uuid4();
        $this->start_time = microtime(true);
    }

    /**
     * Add a step to the pipeline
     */
    public function add_step($name, callable $callback, $options = []) {
        $this->steps[] = [
            'name'     => $name,
            'callback' => $callback,
            'options'  => $options,
            'status'   => 'pending',
        ];
        return $this;
    }

    /**
     * Execute all pipeline steps
     */
    public function run($context = []) {
        $this->status = 'running';

        foreach ($this->steps as $i => &$step) {
            $step['status'] = 'running';
            $step_start = microtime(true);

            try {
                $result = call_user_func($step['callback'], $context, $this->results);
                $step['status'] = 'completed';
                $step['result'] = $result;
                $step['duration'] = round(microtime(true) - $step_start, 3) . 's';
                $this->results[$step['name']] = $result;

                // Pass result to next step's context
                $context[$step['name']] = $result;

            } catch (\Exception $e) {
                $step['status'] = 'failed';
                $step['error'] = $e->getMessage();
                $step['duration'] = round(microtime(true) - $step_start, 3) . 's';

                // Check if step is required
                if (!empty($step['options']['required'])) {
                    $this->status = 'failed';
                    break;
                }
            }
        }

        if ($this->status !== 'failed') {
            $this->status = 'completed';
        }

        return $this->get_report();
    }

    /**
     * Get pipeline execution report
     */
    public function get_report() {
        return [
            'pipeline_id'     => $this->pipeline_id,
            'status'          => $this->status,
            'total_steps'     => count($this->steps),
            'completed_steps' => count(array_filter($this->steps, fn($s) => $s['status'] === 'completed')),
            'failed_steps'    => count(array_filter($this->steps, fn($s) => $s['status'] === 'failed')),
            'total_duration'  => round(microtime(true) - $this->start_time, 3) . 's',
            'steps'           => array_map(function ($s) {
                return [
                    'name'     => $s['name'],
                    'status'   => $s['status'],
                    'duration' => $s['duration'] ?? null,
                    'error'    => $s['error'] ?? null,
                ];
            }, $this->steps),
            'results'         => $this->results,
        ];
    }

    /**
     * Create a pre-configured intake pipeline
     */
    public static function intake_pipeline($product_id) {
        $runner = new self('intake-' . $product_id);

        $runner->add_step('classify', function ($ctx) use ($product_id) {
            if (!class_exists('Tigon_Groq_Taxonomy_Classifier')) return 'skipped';
            $c = new Tigon_Groq_Taxonomy_Classifier();
            return $c->classify($product_id, true);
        }, ['required' => false]);

        $runner->add_step('enrich', function ($ctx) use ($product_id) {
            if (!class_exists('Tigon_Groq_Enrichment_Pipeline')) return 'skipped';
            $p = new Tigon_Groq_Enrichment_Pipeline();
            return $p->enrich($product_id);
        }, ['required' => false]);

        $runner->add_step('dna_hash', function ($ctx) use ($product_id) {
            if (!class_exists('Tigon_DNA_Hash')) return 'skipped';
            return [
                'hash' => Tigon_DNA_Hash::generate($product_id),
                'completeness' => Tigon_DNA_Hash::completeness_score($product_id),
            ];
        });

        return $runner->run(['product_id' => $product_id]);
    }
}
