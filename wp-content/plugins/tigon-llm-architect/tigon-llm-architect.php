<?php
/**
 * Plugin Name: Tigon LLM Architect
 * Plugin URI: https://tigongolfcarts.com
 * Description: Smart LLM orchestration layer that acts as the AI architect for Tigon Golf Carts. Coordinates between Groq workers, taxonomy kernel, and WooCommerce to automate inventory management, product classification, and content generation pipelines.
 * Version: 1.0.0
 * Author: Tigon Golf Carts Engineering
 * Author URI: https://tigongolfcarts.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: tigon-llm-architect
 *
 * == ARCHITECT PATTERN ==
 * The LLM Architect orchestrates multi-step AI pipelines:
 *
 * 1. INTAKE PIPELINE: DMS Import → Classify → Enrich → Describe → Publish
 * 2. QUALITY PIPELINE: Validate → Fix → Regenerate DNA → Score
 * 3. OPTIMIZATION PIPELINE: Analyze → Suggest Pricing → Cross-sell → SEO
 * 4. REPORTING PIPELINE: Inventory Analysis → Market Insights → Recommendations
 */

defined('ABSPATH') || exit;

define('TIGON_ARCHITECT_VERSION', '1.0.0');
define('TIGON_ARCHITECT_DIR', plugin_dir_path(__FILE__));

add_action('plugins_loaded', function () {
    require_once TIGON_ARCHITECT_DIR . 'includes/class-architect-core.php';
    require_once TIGON_ARCHITECT_DIR . 'includes/engines/class-intake-engine.php';
    require_once TIGON_ARCHITECT_DIR . 'includes/engines/class-quality-engine.php';
    require_once TIGON_ARCHITECT_DIR . 'includes/engines/class-optimization-engine.php';
    require_once TIGON_ARCHITECT_DIR . 'includes/pipelines/class-pipeline-runner.php';

    if (is_admin()) {
        require_once TIGON_ARCHITECT_DIR . 'admin/class-architect-admin.php';
    }
});

register_activation_hook(__FILE__, function () {
    add_option('tigon_architect_settings', [
        'intake_auto'    => true,
        'quality_auto'   => true,
        'optimize_auto'  => false,
        'log_level'      => 'info',
        'max_pipeline_steps' => 10,
    ]);
});
