<?php
/**
 * Groq API Client — Ultra-low-latency LLM inference
 *
 * Handles all communication with the Groq API (OpenAI-compatible).
 * Supports streaming, function calling, and JSON mode.
 *
 * @package TigonGroqWorker
 */

defined('ABSPATH') || exit;

class Tigon_Groq_Client {

    private $api_key;
    private $model;
    private $api_url;
    private $max_tokens;
    private $temperature;

    public function __construct($model = null) {
        $settings = get_option('tigon_groq_settings', []);
        $this->api_key     = $settings['api_key'] ?? '';
        $this->model       = $model ?: ($settings['default_model'] ?? TIGON_GROQ_DEFAULT_MODEL);
        $this->api_url     = TIGON_GROQ_API_URL;
        $this->max_tokens  = $settings['max_tokens'] ?? 4096;
        $this->temperature = $settings['temperature'] ?? 0.1;
    }

    /**
     * Send a chat completion request to Groq
     *
     * @param array $messages Array of {role, content} messages
     * @param array $options Additional options (json_mode, tools, etc.)
     * @return array|WP_Error
     */
    public function chat($messages, $options = []) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Groq API key not configured. Go to Tigon Groq Worker > Settings.');
        }

        $body = [
            'model'       => $options['model'] ?? $this->model,
            'messages'    => $messages,
            'max_tokens'  => $options['max_tokens'] ?? $this->max_tokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
        ];

        // JSON mode
        if (!empty($options['json_mode'])) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        // Function calling / tools
        if (!empty($options['tools'])) {
            $body['tools'] = $options['tools'];
            $body['tool_choice'] = $options['tool_choice'] ?? 'auto';
        }

        $response = wp_remote_post($this->api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        $data = json_decode($body_raw, true);

        if ($status !== 200) {
            $error_msg = $data['error']['message'] ?? "HTTP {$status}: Unknown error";
            return new WP_Error('groq_api_error', $error_msg, ['status' => $status]);
        }

        return $data;
    }

    /**
     * Quick helper — send a single user message and get text response
     */
    public function ask($prompt, $system = '', $options = []) {
        $messages = [];
        if ($system) {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $result = $this->chat($messages, $options);

        if (is_wp_error($result)) return $result;

        return $result['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Ask with JSON mode — returns parsed array
     */
    public function ask_json($prompt, $system = '', $options = []) {
        $options['json_mode'] = true;
        $result = $this->ask($prompt, $system, $options);

        if (is_wp_error($result)) return $result;

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_parse_error', 'Failed to parse Groq JSON response: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Use the fast model for quick operations
     */
    public function fast() {
        $settings = get_option('tigon_groq_settings', []);
        $this->model = $settings['fast_model'] ?? TIGON_GROQ_FAST_MODEL;
        return $this;
    }

    /**
     * Use the full model for complex operations
     */
    public function full() {
        $settings = get_option('tigon_groq_settings', []);
        $this->model = $settings['default_model'] ?? TIGON_GROQ_DEFAULT_MODEL;
        return $this;
    }

    /**
     * Get usage stats from the last API call
     */
    public function get_model() {
        return $this->model;
    }

    public function is_configured() {
        return !empty($this->api_key);
    }
}
