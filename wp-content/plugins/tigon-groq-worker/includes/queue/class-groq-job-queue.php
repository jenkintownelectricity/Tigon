<?php
/**
 * Groq Job Queue — Background processing for bulk operations
 *
 * Uses WordPress cron + custom DB table for async job processing.
 * Supports: classify, enrich, describe, similarity
 *
 * @package TigonGroqWorker
 */

defined('ABSPATH') || exit;

class Tigon_Groq_Job_Queue {

    const TABLE_NAME = 'tigon_groq_jobs';
    const CRON_HOOK  = 'tigon_groq_process_queue';

    /**
     * Create the jobs table
     */
    public static function install() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT,
            job_type VARCHAR(50) NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            priority INT(3) NOT NULL DEFAULT 10,
            payload LONGTEXT,
            result LONGTEXT,
            error_message TEXT,
            attempts INT(3) NOT NULL DEFAULT 0,
            max_attempts INT(3) NOT NULL DEFAULT 3,
            created_at DATETIME NOT NULL,
            started_at DATETIME,
            completed_at DATETIME,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_product (product_id),
            KEY idx_type_status (job_type, status)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Add a job to the queue
     */
    public static function enqueue($job_type, $product_id, $payload = [], $priority = 10) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $wpdb->insert($table, [
            'job_type'   => $job_type,
            'product_id' => $product_id,
            'status'     => 'pending',
            'priority'   => $priority,
            'payload'    => wp_json_encode($payload),
            'created_at' => current_time('mysql'),
        ]);

        // Schedule processing if not already scheduled
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 5, self::CRON_HOOK);
        }

        return $wpdb->insert_id;
    }

    /**
     * Bulk enqueue multiple products for a job type
     */
    public static function bulk_enqueue($job_type, $product_ids, $payload = []) {
        $job_ids = [];
        foreach ($product_ids as $pid) {
            $job_ids[] = self::enqueue($job_type, $pid, $payload);
        }
        return $job_ids;
    }

    /**
     * Process pending jobs (called by cron)
     */
    public static function process() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $settings = get_option('tigon_groq_settings', []);
        $batch_size = $settings['batch_size'] ?? 10;

        // Get pending jobs ordered by priority
        $jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = 'pending' AND attempts < max_attempts ORDER BY priority ASC, created_at ASC LIMIT %d",
            $batch_size
        ));

        if (empty($jobs)) return;

        foreach ($jobs as $job) {
            // Mark as processing
            $wpdb->update($table, [
                'status'     => 'processing',
                'started_at' => current_time('mysql'),
                'attempts'   => $job->attempts + 1,
            ], ['id' => $job->id]);

            try {
                $result = self::execute_job($job);

                $wpdb->update($table, [
                    'status'       => 'completed',
                    'result'       => wp_json_encode($result),
                    'completed_at' => current_time('mysql'),
                ], ['id' => $job->id]);

            } catch (\Exception $e) {
                $new_status = ($job->attempts + 1 >= $job->max_attempts) ? 'failed' : 'pending';
                $wpdb->update($table, [
                    'status'        => $new_status,
                    'error_message' => $e->getMessage(),
                ], ['id' => $job->id]);
            }
        }

        // If there are more pending jobs, schedule another run
        $remaining = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'");
        if ($remaining > 0 && !wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 10, self::CRON_HOOK);
        }
    }

    /**
     * Execute a single job based on type
     */
    private static function execute_job($job) {
        $payload = json_decode($job->payload, true) ?: [];

        switch ($job->job_type) {
            case 'classify':
                $classifier = new Tigon_Groq_Taxonomy_Classifier();
                return $classifier->classify($job->product_id, $payload['apply'] ?? true);

            case 'enrich':
                $pipeline = new Tigon_Groq_Enrichment_Pipeline();
                return $pipeline->enrich($job->product_id);

            case 'describe':
                $pipeline = new Tigon_Groq_Enrichment_Pipeline();
                return $pipeline->generate_description($job->product_id);

            case 'similarity':
                if (class_exists('Tigon_DNA_Hash')) {
                    return Tigon_DNA_Hash::find_similar($job->product_id);
                }
                return [];

            default:
                throw new \Exception("Unknown job type: {$job->job_type}");
        }
    }

    /**
     * Get queue stats
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        return [
            'pending'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'"),
            'processing' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'processing'"),
            'completed'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'completed'"),
            'failed'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'failed'"),
            'total'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
        ];
    }

    /**
     * Clear completed/failed jobs older than N days
     */
    public static function cleanup($days = 7) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE status IN ('completed', 'failed') AND completed_at < %s",
            $cutoff
        ));
    }
}

// Register cron handler
add_action(Tigon_Groq_Job_Queue::CRON_HOOK, ['Tigon_Groq_Job_Queue', 'process']);

// Install table on activation
register_activation_hook(TIGON_GROQ_DIR . 'tigon-groq-worker.php', ['Tigon_Groq_Job_Queue', 'install']);

// REST endpoints for queue management
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/groq/queue/stats', [
        'methods'  => 'GET',
        'callback' => function () {
            return new WP_REST_Response(Tigon_Groq_Job_Queue::get_stats(), 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);

    register_rest_route('tigon/v1', '/groq/queue/enqueue', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            $type = $request->get_param('job_type');
            $ids = $request->get_param('product_ids') ?: [];
            $payload = $request->get_param('payload') ?: [];
            $job_ids = Tigon_Groq_Job_Queue::bulk_enqueue($type, $ids, $payload);
            return new WP_REST_Response(['job_ids' => $job_ids, 'count' => count($job_ids)], 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);
});
