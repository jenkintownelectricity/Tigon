<?php
/**
 * Add-On Cart Handler — Processes add-ons through WooCommerce cart
 *
 * @package TigonWooAddons
 */

defined('ABSPATH') || exit;

class Tigon_Addon_Cart_Handler {

    public function __construct() {
        // Capture addon selections when adding to cart
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_addon_data'], 10, 3);

        // Display addons in cart
        add_filter('woocommerce_get_item_data', [$this, 'display_addon_data'], 10, 2);

        // Adjust price for addons
        add_action('woocommerce_before_calculate_totals', [$this, 'adjust_price_for_addons'], 20, 1);

        // Save addon data to order
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_addon_to_order'], 10, 4);
    }

    /**
     * Capture addon selections from POST data
     */
    public function add_addon_data($cart_item_data, $product_id, $variation_id) {
        $addons = [];

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'tigon_addon_') === 0 && !empty($value)) {
                $addon_id = absint(str_replace('tigon_addon_', '', $key));
                $addon = $this->get_addon($addon_id);
                if ($addon) {
                    $addons[] = [
                        'id'    => $addon_id,
                        'name'  => $addon->addon_name,
                        'value' => sanitize_text_field($value),
                        'price' => $this->calculate_addon_price($addon, $value),
                    ];
                }
            }
        }

        if (!empty($addons)) {
            $cart_item_data['tigon_addons'] = $addons;
        }

        return $cart_item_data;
    }

    /**
     * Display addon selections in cart
     */
    public function display_addon_data($item_data, $cart_item) {
        if (!empty($cart_item['tigon_addons'])) {
            foreach ($cart_item['tigon_addons'] as $addon) {
                $display_value = $addon['value'];
                if ($addon['price'] > 0) {
                    $display_value .= ' (+' . wc_price($addon['price']) . ')';
                }
                $item_data[] = [
                    'key'   => $addon['name'],
                    'value' => $display_value,
                ];
            }
        }
        return $item_data;
    }

    /**
     * Adjust product price based on selected addons
     */
    public function adjust_price_for_addons($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;

        foreach ($cart->get_cart() as $cart_item) {
            if (!empty($cart_item['tigon_addons'])) {
                $addon_total = 0;
                foreach ($cart_item['tigon_addons'] as $addon) {
                    $addon_total += floatval($addon['price']);
                }
                if ($addon_total > 0) {
                    $base_price = floatval($cart_item['data']->get_price());
                    $cart_item['data']->set_price($base_price + $addon_total);
                }
            }
        }
    }

    /**
     * Save addon data to the order for reference
     */
    public function save_addon_to_order($item, $cart_item_key, $values, $order) {
        if (!empty($values['tigon_addons'])) {
            foreach ($values['tigon_addons'] as $addon) {
                $meta_value = $addon['value'];
                if ($addon['price'] > 0) {
                    $meta_value .= ' ($' . number_format($addon['price'], 2) . ')';
                }
                $item->add_meta_data($addon['name'], $meta_value);
            }
            $item->add_meta_data('_tigon_addons_raw', wp_json_encode($values['tigon_addons']));
        }
    }

    private function get_addon($addon_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tigon_addons WHERE id = %d AND is_active = 1",
            $addon_id
        ));
    }

    private function calculate_addon_price($addon, $value) {
        if ($addon->addon_type === 'select' || $addon->addon_type === 'radio') {
            $options = json_decode($addon->addon_options, true) ?: [];
            foreach ($options as $opt) {
                if (($opt['value'] ?? $opt['label']) === $value) {
                    return floatval($opt['price'] ?? 0);
                }
            }
            return 0;
        }

        if ($addon->addon_type === 'number') {
            return floatval($addon->addon_price) * absint($value);
        }

        return floatval($addon->addon_price);
    }
}

new Tigon_Addon_Cart_Handler();
