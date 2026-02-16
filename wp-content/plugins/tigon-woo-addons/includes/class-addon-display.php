<?php
/**
 * Add-On Display — Renders add-on options on product pages
 *
 * @package TigonWooAddons
 */

defined('ABSPATH') || exit;

class Tigon_Addon_Display {

    public function __construct() {
        // Display add-ons before add to cart button
        add_action('woocommerce_before_add_to_cart_button', [$this, 'render_addons'], 20);

        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        if (!is_product()) return;

        wp_enqueue_style(
            'tigon-addons',
            TIGON_ADDONS_URL . 'assets/css/addons.css',
            [],
            TIGON_ADDONS_VERSION
        );

        wp_enqueue_script(
            'tigon-addons',
            TIGON_ADDONS_URL . 'assets/js/addons.js',
            ['jquery'],
            TIGON_ADDONS_VERSION,
            true
        );
    }

    /**
     * Render add-on options on the single product page
     */
    public function render_addons() {
        global $product;
        if (!$product) return;

        $addon_groups = Tigon_Addon_Registry::get_addons_for_product($product->get_id());
        if (empty($addon_groups)) return;

        echo '<div class="tigon-product-addons">';
        echo '<h3 class="tigon-addons-title">Customize Your Cart</h3>';

        foreach ($addon_groups as $group_data) {
            $group = $group_data['group'];
            $addons = $group_data['addons'];

            echo '<div class="tigon-addon-group" data-group-id="' . esc_attr($group->id) . '">';
            echo '<h4 class="tigon-addon-group__title">' . esc_html($group->group_name) . '</h4>';

            foreach ($addons as $addon) {
                $this->render_addon_field($addon);
            }

            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Render a single add-on field based on type
     */
    private function render_addon_field($addon) {
        $field_name = 'tigon_addon_' . $addon->id;
        $price_display = $addon->addon_price > 0 ? ' (+$' . number_format($addon->addon_price, 2) . ')' : '';

        echo '<div class="tigon-addon-field tigon-addon-field--' . esc_attr($addon->addon_type) . '">';

        switch ($addon->addon_type) {
            case 'checkbox':
                printf(
                    '<label><input type="checkbox" name="%s" value="1" data-price="%s"> %s%s</label>',
                    esc_attr($field_name),
                    esc_attr($addon->addon_price),
                    esc_html($addon->addon_name),
                    $price_display
                );
                break;

            case 'select':
                echo '<label>' . esc_html($addon->addon_name) . '</label>';
                echo '<select name="' . esc_attr($field_name) . '">';
                echo '<option value="">-- Select --</option>';
                if (!empty($addon->addon_options)) {
                    foreach ($addon->addon_options as $opt) {
                        $opt_price = isset($opt['price']) ? ' (+$' . number_format($opt['price'], 2) . ')' : '';
                        printf(
                            '<option value="%s" data-price="%s">%s%s</option>',
                            esc_attr($opt['value'] ?? $opt['label']),
                            esc_attr($opt['price'] ?? 0),
                            esc_html($opt['label']),
                            $opt_price
                        );
                    }
                }
                echo '</select>';
                break;

            case 'radio':
                echo '<label class="tigon-addon-field__label">' . esc_html($addon->addon_name) . '</label>';
                if (!empty($addon->addon_options)) {
                    foreach ($addon->addon_options as $i => $opt) {
                        $opt_price = isset($opt['price']) ? ' (+$' . number_format($opt['price'], 2) . ')' : '';
                        printf(
                            '<label class="tigon-addon-radio"><input type="radio" name="%s" value="%s" data-price="%s"> %s%s</label>',
                            esc_attr($field_name),
                            esc_attr($opt['value'] ?? $opt['label']),
                            esc_attr($opt['price'] ?? 0),
                            esc_html($opt['label']),
                            $opt_price
                        );
                    }
                }
                break;

            case 'text':
                printf(
                    '<label>%s</label><input type="text" name="%s" placeholder="%s">',
                    esc_html($addon->addon_name),
                    esc_attr($field_name),
                    esc_attr($addon->addon_description ?: '')
                );
                break;

            case 'number':
                printf(
                    '<label>%s</label><input type="number" name="%s" min="0" step="1" data-price="%s">',
                    esc_html($addon->addon_name . $price_display),
                    esc_attr($field_name),
                    esc_attr($addon->addon_price)
                );
                break;
        }

        if ($addon->addon_description && $addon->addon_type !== 'text') {
            echo '<small class="tigon-addon-desc">' . esc_html($addon->addon_description) . '</small>';
        }

        echo '</div>';
    }
}

// Initialize display
new Tigon_Addon_Display();
