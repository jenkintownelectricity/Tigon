<?php
/**
 * Plugin Name: Tigon Groq Worker
 * Plugin URI: https://tigongolfcarts.com
 * Description: Lightning-fast AI worker powered by Groq LPU for automated taxonomy classification, product enrichment, and intelligent cart DNA analysis. Uses Groq's ultra-low-latency inference for real-time processing.
 * Version: 1.0.0
 * Author: Tigon Golf Carts Engineering
 * Author URI: https://tigongolfcarts.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: tigon-groq-worker
 *
 * == GROQ WORKER ARCHITECTURE ==
 * 1. Taxonomy Classifier — Auto-classifies products into 50-layer taxonomy
 * 2. Description Generator — AI-generated product descriptions
 * 3. Similarity Engine — Finds related products via DNA matching
 * 4. Enrichment Pipeline — Fills missing taxonomy layers from existing data
 * 5. Bulk Processor — Queue-based batch processing for inventory imports
 */

defined('ABSPATH') || exit;

define('TIGON_GROQ_VERSION', '1.0.0');
define('TIGON_GROQ_DIR', plugin_dir_path(__FILE__));
define('TIGON_GROQ_URL', plugin_dir_url(__FILE__));

// Default Groq config
define('TIGON_GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('TIGON_GROQ_DEFAULT_MODEL', 'llama-3.3-70b-versatile');
define('TIGON_GROQ_FAST_MODEL', 'llama-3.1-8b-instant');

add_action('plugins_loaded', function () {
    require_once TIGON_GROQ_DIR . 'includes/class-groq-client.php';
    require_once TIGON_GROQ_DIR . 'includes/class-groq-taxonomy-classifier.php';
    require_once TIGON_GROQ_DIR . 'includes/class-groq-enrichment-pipeline.php';
    require_once TIGON_GROQ_DIR . 'includes/queue/class-groq-job-queue.php';
    require_once TIGON_GROQ_DIR . 'includes/workers/class-taxonomy-worker.php';
    require_once TIGON_GROQ_DIR . 'includes/workers/class-description-worker.php';
    require_once TIGON_GROQ_DIR . 'includes/workers/class-similarity-worker.php';

    if (is_admin()) {
        require_once TIGON_GROQ_DIR . 'admin/class-groq-admin.php';
    }
});

// Settings default
register_activation_hook(__FILE__, function () {
    $defaults = [
        'api_key'         => '',
        'default_model'   => TIGON_GROQ_DEFAULT_MODEL,
        'fast_model'      => TIGON_GROQ_FAST_MODEL,
        'max_tokens'      => 4096,
        'temperature'     => 0.1,
        'batch_size'      => 10,
        'auto_classify'   => true,
        'auto_enrich'     => true,
        'auto_describe'   => false,
        'queue_enabled'   => true,
    ];
    if (!get_option('tigon_groq_settings')) {
        add_option('tigon_groq_settings', $defaults);
    }
});
