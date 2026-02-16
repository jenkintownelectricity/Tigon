<?php
/**
 * Intake Engine — Handles new product ingestion from DMS
 *
 * Processes DMS cart data into fully classified WooCommerce products.
 * Pipeline: Parse DMS → Create Product → Classify → Enrich → DNA Hash
 *
 * @package TigonLLMArchitect
 */

defined('ABSPATH') || exit;

class Tigon_Intake_Engine {

    /**
     * Process a DMS cart object into a WooCommerce product
     *
     * @param array $dms_cart Raw DMS cart data (from Tigon DMS API)
     * @return int|WP_Error Product ID on success
     */
    public static function process_dms_cart($dms_cart) {
        // Extract core fields
        $make = $dms_cart['cartType']['make'] ?? 'Unknown';
        $model = $dms_cart['cartType']['model'] ?? 'Golf Cart';
        $year = $dms_cart['cartType']['year'] ?? '';
        $color = $dms_cart['cartAttributes']['cartColor'] ?? '';
        $is_used = !empty($dms_cart['isUsed']);
        $is_electric = !empty($dms_cart['isElectric']);
        $is_street_legal = !empty($dms_cart['title']['isStreetLegal']);

        // Build product name: Year Make Model Color
        $name_parts = array_filter([$year, $make, $model, $color]);
        $product_name = implode(' ', $name_parts);

        // Check if product already exists (by VIN or DMS ID)
        $existing = self::find_existing_product($dms_cart);
        if ($existing) {
            return self::update_existing_product($existing, $dms_cart);
        }

        // Create WooCommerce product
        $product = new WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');

        // Pricing
        if (!empty($dms_cart['retailPrice'])) {
            $product->set_regular_price($dms_cart['retailPrice']);
        }
        if (!empty($dms_cart['salePrice']) && $dms_cart['salePrice'] < ($dms_cart['retailPrice'] ?? PHP_INT_MAX)) {
            $product->set_sale_price($dms_cart['salePrice']);
        }

        // SKU from DMS ID
        if (!empty($dms_cart['_id'])) {
            $product->set_sku('DMS-' . $dms_cart['_id']);
        }

        $product_id = $product->save();

        if (!$product_id) {
            return new WP_Error('product_create_failed', 'Failed to create WooCommerce product');
        }

        // Set custom meta
        update_post_meta($product_id, '_tigon_vin', sanitize_text_field($dms_cart['vinNo'] ?? ''));
        update_post_meta($product_id, '_tigon_serial', sanitize_text_field($dms_cart['serialNo'] ?? ''));
        update_post_meta($product_id, '_tigon_year', sanitize_text_field($year));
        update_post_meta($product_id, '_tigon_condition', $is_used ? 'used' : 'new');
        update_post_meta($product_id, '_tigon_street_legal', $is_street_legal ? 'yes' : 'no');
        update_post_meta($product_id, '_tigon_electric', $is_electric ? 'yes' : 'no');
        update_post_meta($product_id, '_tigon_dms_id', $dms_cart['_id'] ?? '');
        update_post_meta($product_id, '_tigon_dms_raw', wp_json_encode($dms_cart));

        // Assign taxonomies
        self::assign_taxonomies($product_id, $dms_cart);

        // Trigger AI classification pipeline
        if (class_exists('Tigon_Architect_Core')) {
            $architect = Tigon_Architect_Core::instance();
            $architect->run_pipeline('intake', $product_id);
        }

        return $product_id;
    }

    /**
     * Assign taxonomy terms from DMS data
     */
    private static function assign_taxonomies($product_id, $dms_cart) {
        $make = $dms_cart['cartType']['make'] ?? '';
        $model = $dms_cart['cartType']['model'] ?? '';

        // Manufacturer
        if ($make) {
            wp_set_object_terms($product_id, $make, 'manufacturers');
        }

        // Model (PRIMARY CATEGORY)
        if ($model) {
            wp_set_object_terms($product_id, $model, 'models');
        }

        // Year
        $year = $dms_cart['cartType']['year'] ?? '';
        if ($year) {
            wp_set_object_terms($product_id, $year, 'model-year');
        }

        // Location
        $location_id = $dms_cart['cartLocation']['locationId'] ?? '';
        if ($location_id) {
            // Map location ID to location term
            $location_map = [
                'T1' => 'Hatfield PA', 'T2' => 'Ocean View NJ', 'T3' => 'Pocono Pines PA',
                'T4' => 'Dover DE', 'T5' => 'Scranton PA', 'T6' => 'Raleigh NC',
                'T7' => 'South Bend IN', 'T8' => 'Gloucester Point VA', 'T9' => 'Lecanto FL',
                'T10' => 'Swanton OH', 'T11' => 'Orangeburg SC', 'T13' => 'Virginia Beach VA',
            ];
            if (isset($location_map[$location_id])) {
                wp_set_object_terms($product_id, $location_map[$location_id], 'location');
            }
        }

        // Inventory status
        if (!empty($dms_cart['isInStock'])) {
            wp_set_object_terms($product_id, 'In Stock', 'inventory-status');
        } elseif (!empty($dms_cart['isDelivered'])) {
            wp_set_object_terms($product_id, 'Delivered', 'inventory-status');
        } elseif (!empty($dms_cart['isService'])) {
            wp_set_object_terms($product_id, 'In Service', 'inventory-status');
        }

        // Powertrain
        if (!empty($dms_cart['isElectric'])) {
            wp_set_object_terms($product_id, 'Electric', 'powertrain-type');
        }

        // Battery
        $battery_type = $dms_cart['battery']['type'] ?? '';
        if ($battery_type) {
            wp_set_object_terms($product_id, $battery_type, 'battery-system');
        }

        // Seating
        $passengers = $dms_cart['cartAttributes']['passengers'] ?? '';
        if ($passengers) {
            wp_set_object_terms($product_id, $passengers, 'seating-config');
        }

        // Colors
        $cart_color = $dms_cart['cartAttributes']['cartColor'] ?? '';
        if ($cart_color) {
            wp_set_object_terms($product_id, $cart_color, 'color-exterior');
        }
        $seat_color = $dms_cart['cartAttributes']['seatColor'] ?? '';
        if ($seat_color) {
            wp_set_object_terms($product_id, $seat_color, 'color-seat');
        }

        // Tire info
        $tire_type = $dms_cart['cartAttributes']['tireType'] ?? '';
        if ($tire_type) {
            wp_set_object_terms($product_id, $tire_type, 'tire-type');
        }
        $tire_size = $dms_cart['cartAttributes']['tireRimSize'] ?? '';
        if ($tire_size) {
            wp_set_object_terms($product_id, $tire_size . ' Inch', 'tire-rim-size');
        }

        // Street legal
        if (!empty($dms_cart['title']['isStreetLegal'])) {
            wp_set_object_terms($product_id, 'Full Street Legal', 'compliance-class');
        }

        // Sound system
        if (!empty($dms_cart['cartAttributes']['hasSoundSystem'])) {
            wp_set_object_terms($product_id, 'Has Sound System', 'sound-systems');
        }

        // Added features
        $features = [];
        if (!empty($dms_cart['cartAttributes']['isLifted'])) $features[] = 'Lift Kit';
        if (!empty($dms_cart['cartAttributes']['hasExtendedTop'])) $features[] = 'Extended Top';
        if (!empty($dms_cart['cartAttributes']['hasFenderFlares'])) $features[] = 'Fender Flares';
        if (!empty($dms_cart['addedFeatures']['brushGuard'])) $features[] = 'Brush Guard';
        if (!empty($dms_cart['addedFeatures']['lightBar'])) $features[] = 'Light Bar';
        if (!empty($dms_cart['addedFeatures']['underGlow'])) $features[] = 'Under Glow LEDs';
        if (!empty($dms_cart['addedFeatures']['clayBasket'])) $features[] = 'Clay Basket';
        if (!empty($features)) {
            wp_set_object_terms($product_id, $features, 'added-features');
        }
    }

    private static function find_existing_product($dms_cart) {
        $dms_id = $dms_cart['_id'] ?? '';
        if (!$dms_id) return null;

        $existing = get_posts([
            'post_type'   => 'product',
            'meta_key'    => '_tigon_dms_id',
            'meta_value'  => $dms_id,
            'numberposts' => 1,
            'fields'      => 'ids',
        ]);

        return !empty($existing) ? $existing[0] : null;
    }

    private static function update_existing_product($product_id, $dms_cart) {
        // Update pricing
        $product = wc_get_product($product_id);
        if ($product && !empty($dms_cart['retailPrice'])) {
            $product->set_regular_price($dms_cart['retailPrice']);
            if (!empty($dms_cart['salePrice'])) {
                $product->set_sale_price($dms_cart['salePrice']);
            }
            $product->save();
        }

        // Re-assign taxonomies
        self::assign_taxonomies($product_id, $dms_cart);

        update_post_meta($product_id, '_tigon_dms_last_sync', current_time('mysql'));

        return $product_id;
    }
}

// REST endpoint for DMS intake
add_action('rest_api_init', function () {
    register_rest_route('tigon/v1', '/intake/dms-cart', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            $cart_data = $request->get_json_params();
            $result = Tigon_Intake_Engine::process_dms_cart($cart_data);
            if (is_wp_error($result)) {
                return new WP_REST_Response(['error' => $result->get_error_message()], 400);
            }
            return new WP_REST_Response(['product_id' => $result, 'status' => 'created'], 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);

    register_rest_route('tigon/v1', '/intake/dms-batch', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            $carts = $request->get_json_params();
            $results = [];
            foreach ($carts as $cart) {
                $pid = Tigon_Intake_Engine::process_dms_cart($cart);
                $results[] = is_wp_error($pid) ? ['error' => $pid->get_error_message()] : ['product_id' => $pid];
            }
            return new WP_REST_Response(['results' => $results, 'count' => count($results)], 200);
        },
        'permission_callback' => function () { return current_user_can('manage_woocommerce'); },
    ]);
});
