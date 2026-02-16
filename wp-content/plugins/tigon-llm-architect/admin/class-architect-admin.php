<?php
/**
 * Architect Admin — Dashboard for AI pipeline management
 *
 * @package TigonLLMArchitect
 */

defined('ABSPATH') || exit;

class Tigon_Architect_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu() {
        add_menu_page(
            'Tigon LLM Architect',
            'LLM Architect',
            'manage_woocommerce',
            'tigon-architect',
            [$this, 'render_dashboard'],
            'dashicons-networking',
            57
        );
    }

    public function render_dashboard() {
        $settings = get_option('tigon_architect_settings', []);
        $last_audit = get_option('tigon_quality_last_audit', 'Never');
        ?>
        <div class="wrap">
            <h1 style="color:#c8a84e;">Tigon LLM Architect — Smart Orchestration</h1>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin:2rem 0;">
                <!-- Pipelines -->
                <div style="background:#fff;padding:2rem;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h2 style="color:#c8a84e;margin-top:0;">AI Pipelines</h2>
                    <table class="widefat striped">
                        <thead><tr><th>Pipeline</th><th>Description</th><th>Action</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><strong>Intake</strong></td>
                                <td>Classify + Enrich + Describe + DNA</td>
                                <td><button class="button" onclick="runPipeline('intake')">Run</button></td>
                            </tr>
                            <tr>
                                <td><strong>Quality</strong></td>
                                <td>Validate + Fix + Score</td>
                                <td><button class="button" onclick="runPipeline('quality')">Run</button></td>
                            </tr>
                            <tr>
                                <td><strong>Optimize</strong></td>
                                <td>SEO + Cross-sell + Pricing</td>
                                <td><button class="button" onclick="runPipeline('optimize')">Run</button></td>
                            </tr>
                            <tr>
                                <td><strong>Full</strong></td>
                                <td>All three pipelines combined</td>
                                <td><button class="button button-primary" onclick="runPipeline('full')">Run All</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- System Status -->
                <div style="background:#fff;padding:2rem;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h2 style="color:#c8a84e;margin-top:0;">System Status</h2>
                    <table class="widefat">
                        <tr>
                            <td>Taxonomy Kernel</td>
                            <td><?php echo class_exists('Tigon_Taxonomy_Registry') ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>Groq Worker</td>
                            <td><?php echo class_exists('Tigon_Groq_Client') ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>Groq API Key</td>
                            <td><?php
                                $groq_settings = get_option('tigon_groq_settings', []);
                                echo !empty($groq_settings['api_key']) ? '<span style="color:green;">Configured</span>' : '<span style="color:orange;">Not Set</span>';
                            ?></td>
                        </tr>
                        <tr>
                            <td>WooCommerce</td>
                            <td><?php echo class_exists('WooCommerce') ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>Last Quality Audit</td>
                            <td><?php echo esc_html($last_audit); ?></td>
                        </tr>
                        <tr>
                            <td>Taxonomy Layers</td>
                            <td><?php echo class_exists('Tigon_Taxonomy_Registry') ? count(Tigon_Taxonomy_Registry::get_taxonomy_layers()) . ' layers' : 'N/A'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Quick Run -->
            <div style="margin:2rem 0;">
                <h2>Run Pipeline on Product</h2>
                <div style="display:flex;gap:1rem;align-items:flex-end;">
                    <div>
                        <label>Product ID:</label><br>
                        <input type="number" id="architect-product-id" class="regular-text" placeholder="Enter product ID">
                    </div>
                    <div>
                        <label>Pipeline:</label><br>
                        <select id="architect-pipeline-select">
                            <option value="intake">Intake (Classify + Enrich)</option>
                            <option value="quality">Quality (Validate + Fix)</option>
                            <option value="optimize">Optimize (SEO + Cross-sell)</option>
                            <option value="full">Full Pipeline</option>
                        </select>
                    </div>
                    <button class="button button-primary" onclick="runProductPipeline()">Execute Pipeline</button>
                </div>
            </div>

            <div id="architect-result" style="margin:1rem 0;padding:1rem;background:#f9f9f9;display:none;max-height:500px;overflow:auto;"></div>
        </div>

        <script>
        function runProductPipeline() {
            const pid = document.getElementById('architect-product-id').value;
            const pipeline = document.getElementById('architect-pipeline-select').value;
            if (!pid) return alert('Enter a product ID');

            const resultDiv = document.getElementById('architect-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Running ' + pipeline + ' pipeline on product #' + pid + '...';

            fetch('<?php echo rest_url('tigon/v1/architect/pipeline/'); ?>' + pipeline + '/' + pid, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            })
            .then(r => r.json())
            .then(data => { resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>'; })
            .catch(e => { resultDiv.innerHTML = 'Error: ' + e.message; });
        }

        function runPipeline(name) {
            alert('Batch pipeline "' + name + '" will be queued for all products. Check Groq Worker > Job Queue for progress.');
        }
        </script>
        <?php
    }
}

new Tigon_Architect_Admin();
