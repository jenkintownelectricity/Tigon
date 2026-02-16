<?php
/**
 * Groq Worker Admin — Settings and Dashboard
 *
 * @package TigonGroqWorker
 */

defined('ABSPATH') || exit;

class Tigon_Groq_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_menu() {
        add_menu_page(
            'Tigon Groq Worker',
            'Groq Worker',
            'manage_woocommerce',
            'tigon-groq-worker',
            [$this, 'render_dashboard'],
            'dashicons-superhero',
            58
        );

        add_submenu_page('tigon-groq-worker', 'Settings', 'Settings', 'manage_woocommerce', 'tigon-groq-settings', [$this, 'render_settings']);
        add_submenu_page('tigon-groq-worker', 'Job Queue', 'Job Queue', 'manage_woocommerce', 'tigon-groq-queue', [$this, 'render_queue']);
    }

    public function register_settings() {
        register_setting('tigon_groq', 'tigon_groq_settings', [
            'sanitize_callback' => function ($input) {
                $sanitized = [];
                $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
                $sanitized['default_model'] = sanitize_text_field($input['default_model'] ?? TIGON_GROQ_DEFAULT_MODEL);
                $sanitized['fast_model'] = sanitize_text_field($input['fast_model'] ?? TIGON_GROQ_FAST_MODEL);
                $sanitized['max_tokens'] = absint($input['max_tokens'] ?? 4096);
                $sanitized['temperature'] = floatval($input['temperature'] ?? 0.1);
                $sanitized['batch_size'] = absint($input['batch_size'] ?? 10);
                $sanitized['auto_classify'] = !empty($input['auto_classify']);
                $sanitized['auto_enrich'] = !empty($input['auto_enrich']);
                $sanitized['auto_describe'] = !empty($input['auto_describe']);
                $sanitized['queue_enabled'] = !empty($input['queue_enabled']);
                return $sanitized;
            },
        ]);
    }

    public function render_dashboard() {
        $settings = get_option('tigon_groq_settings', []);
        $configured = !empty($settings['api_key']);
        $stats = class_exists('Tigon_Groq_Job_Queue') ? Tigon_Groq_Job_Queue::get_stats() : [];
        ?>
        <div class="wrap">
            <h1 style="color:#c8a84e;">Tigon Groq Worker — AI Dashboard</h1>

            <?php if (!$configured) : ?>
                <div class="notice notice-warning"><p>Groq API key not configured. <a href="<?php echo admin_url('admin.php?page=tigon-groq-settings'); ?>">Configure now</a></p></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin:2rem 0;">
                <div style="background:#fff;padding:1.5rem;border-left:4px solid #c8a84e;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;color:#666;font-size:0.8rem;text-transform:uppercase;">Pending Jobs</h3>
                    <p style="font-size:2rem;font-weight:800;margin:0.5rem 0 0;"><?php echo esc_html($stats['pending'] ?? 0); ?></p>
                </div>
                <div style="background:#fff;padding:1.5rem;border-left:4px solid #28a745;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;color:#666;font-size:0.8rem;text-transform:uppercase;">Completed</h3>
                    <p style="font-size:2rem;font-weight:800;margin:0.5rem 0 0;"><?php echo esc_html($stats['completed'] ?? 0); ?></p>
                </div>
                <div style="background:#fff;padding:1.5rem;border-left:4px solid #007bff;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;color:#666;font-size:0.8rem;text-transform:uppercase;">Processing</h3>
                    <p style="font-size:2rem;font-weight:800;margin:0.5rem 0 0;"><?php echo esc_html($stats['processing'] ?? 0); ?></p>
                </div>
                <div style="background:#fff;padding:1.5rem;border-left:4px solid #dc3545;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;color:#666;font-size:0.8rem;text-transform:uppercase;">Failed</h3>
                    <p style="font-size:2rem;font-weight:800;margin:0.5rem 0 0;"><?php echo esc_html($stats['failed'] ?? 0); ?></p>
                </div>
            </div>

            <h2>Quick Actions</h2>
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin:1rem 0;">
                <button class="button button-primary" onclick="tigonGroqAction('classify-all')">Classify All Unclassified</button>
                <button class="button button-primary" onclick="tigonGroqAction('reclassify-stale')">Reclassify Stale Products</button>
                <button class="button" onclick="tigonGroqAction('generate-descriptions')">Generate Missing Descriptions</button>
                <button class="button" onclick="tigonGroqAction('cleanup')">Cleanup Old Jobs</button>
            </div>

            <div id="tigon-groq-result" style="margin:1rem 0;padding:1rem;background:#f9f9f9;display:none;"></div>

            <h2>Model Configuration</h2>
            <table class="widefat" style="max-width:600px;">
                <tr><td><strong>Default Model:</strong></td><td><?php echo esc_html($settings['default_model'] ?? 'Not set'); ?></td></tr>
                <tr><td><strong>Fast Model:</strong></td><td><?php echo esc_html($settings['fast_model'] ?? 'Not set'); ?></td></tr>
                <tr><td><strong>Auto-Classify:</strong></td><td><?php echo !empty($settings['auto_classify']) ? 'Enabled' : 'Disabled'; ?></td></tr>
                <tr><td><strong>Auto-Enrich:</strong></td><td><?php echo !empty($settings['auto_enrich']) ? 'Enabled' : 'Disabled'; ?></td></tr>
                <tr><td><strong>Batch Size:</strong></td><td><?php echo esc_html($settings['batch_size'] ?? 10); ?></td></tr>
            </table>
        </div>

        <script>
        function tigonGroqAction(action) {
            const resultDiv = document.getElementById('tigon-groq-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Processing...';

            fetch(ajaxurl + '?action=tigon_groq_action&groq_action=' + action + '&_wpnonce=<?php echo wp_create_nonce("tigon_groq_action"); ?>', {method: 'POST'})
                .then(r => r.json())
                .then(data => { resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>'; })
                .catch(e => { resultDiv.innerHTML = 'Error: ' + e.message; });
        }
        </script>
        <?php
    }

    public function render_settings() {
        $settings = get_option('tigon_groq_settings', []);
        ?>
        <div class="wrap">
            <h1>Groq Worker Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('tigon_groq'); ?>
                <table class="form-table">
                    <tr>
                        <th>Groq API Key</th>
                        <td><input type="password" name="tigon_groq_settings[api_key]" value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" class="regular-text" />
                        <p class="description">Get your API key at <a href="https://console.groq.com" target="_blank">console.groq.com</a></p></td>
                    </tr>
                    <tr>
                        <th>Default Model (Complex Tasks)</th>
                        <td><select name="tigon_groq_settings[default_model]">
                            <?php foreach (['llama-3.3-70b-versatile', 'llama-3.1-70b-versatile', 'mixtral-8x7b-32768'] as $m) : ?>
                                <option value="<?php echo esc_attr($m); ?>" <?php selected($settings['default_model'] ?? '', $m); ?>><?php echo esc_html($m); ?></option>
                            <?php endforeach; ?>
                        </select></td>
                    </tr>
                    <tr>
                        <th>Fast Model (Quick Tasks)</th>
                        <td><select name="tigon_groq_settings[fast_model]">
                            <?php foreach (['llama-3.1-8b-instant', 'llama-3.2-3b-preview', 'gemma2-9b-it'] as $m) : ?>
                                <option value="<?php echo esc_attr($m); ?>" <?php selected($settings['fast_model'] ?? '', $m); ?>><?php echo esc_html($m); ?></option>
                            <?php endforeach; ?>
                        </select></td>
                    </tr>
                    <tr><th>Max Tokens</th><td><input type="number" name="tigon_groq_settings[max_tokens]" value="<?php echo esc_attr($settings['max_tokens'] ?? 4096); ?>" /></td></tr>
                    <tr><th>Temperature</th><td><input type="number" step="0.05" min="0" max="2" name="tigon_groq_settings[temperature]" value="<?php echo esc_attr($settings['temperature'] ?? 0.1); ?>" /></td></tr>
                    <tr><th>Batch Size</th><td><input type="number" name="tigon_groq_settings[batch_size]" value="<?php echo esc_attr($settings['batch_size'] ?? 10); ?>" /></td></tr>
                    <tr><th>Auto-Classify New Products</th><td><input type="checkbox" name="tigon_groq_settings[auto_classify]" value="1" <?php checked(!empty($settings['auto_classify'])); ?> /></td></tr>
                    <tr><th>Auto-Enrich Products</th><td><input type="checkbox" name="tigon_groq_settings[auto_enrich]" value="1" <?php checked(!empty($settings['auto_enrich'])); ?> /></td></tr>
                    <tr><th>Auto-Generate Descriptions</th><td><input type="checkbox" name="tigon_groq_settings[auto_describe]" value="1" <?php checked(!empty($settings['auto_describe'])); ?> /></td></tr>
                    <tr><th>Enable Job Queue</th><td><input type="checkbox" name="tigon_groq_settings[queue_enabled]" value="1" <?php checked(!empty($settings['queue_enabled'])); ?> /></td></tr>
                </table>
                <?php submit_button('Save Groq Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function render_queue() {
        $stats = class_exists('Tigon_Groq_Job_Queue') ? Tigon_Groq_Job_Queue::get_stats() : [];
        ?>
        <div class="wrap">
            <h1>Groq Job Queue</h1>
            <p>Total jobs: <?php echo esc_html($stats['total'] ?? 0); ?></p>
            <pre><?php echo esc_html(wp_json_encode($stats, JSON_PRETTY_PRINT)); ?></pre>
        </div>
        <?php
    }
}

new Tigon_Groq_Admin();

// AJAX handler for admin actions
add_action('wp_ajax_tigon_groq_action', function () {
    check_ajax_referer('tigon_groq_action');
    if (!current_user_can('manage_woocommerce')) wp_die('Forbidden');

    $action = sanitize_text_field($_GET['groq_action'] ?? '');
    $result = [];

    switch ($action) {
        case 'classify-all':
            $result = Tigon_Taxonomy_Worker::classify_all_unclassified();
            break;
        case 'reclassify-stale':
            $result = Tigon_Taxonomy_Worker::reclassify_stale();
            break;
        case 'generate-descriptions':
            $result = Tigon_Description_Worker::generate_missing_descriptions();
            break;
        case 'cleanup':
            $deleted = Tigon_Groq_Job_Queue::cleanup();
            $result = ['status' => 'cleaned', 'deleted' => $deleted];
            break;
        default:
            $result = ['error' => 'Unknown action'];
    }

    wp_send_json($result);
});
