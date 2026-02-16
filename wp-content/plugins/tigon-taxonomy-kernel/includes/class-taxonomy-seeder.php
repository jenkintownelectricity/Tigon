<?php
/**
 * Tigon Taxonomy Seeder — Pre-populates all taxonomy terms
 *
 * Seeds the 50-layer taxonomy with real-world golf cart data:
 * Manufacturers, Models, Years, Features, Locations, etc.
 *
 * @package TigonTaxonomyKernel
 */

defined('ABSPATH') || exit;

class Tigon_Taxonomy_Seeder {

    /**
     * Seed all taxonomies with default terms
     */
    public static function seed_all() {
        self::seed_manufacturers();
        self::seed_model_families();
        self::seed_models();
        self::seed_years();
        self::seed_trim_levels();
        self::seed_body_styles();
        self::seed_seating();
        self::seed_powertrains();
        self::seed_batteries();
        self::seed_motors();
        self::seed_drivetrains();
        self::seed_suspension();
        self::seed_braking();
        self::seed_frame_materials();
        self::seed_wheels_tires();
        self::seed_lighting();
        self::seed_sound_systems();
        self::seed_features();
        self::seed_colors();
        self::seed_vehicle_classes();
        self::seed_locations();
        self::seed_inventory_statuses();
        self::seed_price_tiers();
        self::seed_shipping_zones();
        self::seed_warranty_tiers();
        self::seed_certifications();
        self::seed_compliance();
    }

    private static function insert_terms($taxonomy, $terms, $parent_slug = '') {
        $parent_id = 0;
        if ($parent_slug) {
            $parent = get_term_by('slug', $parent_slug, $taxonomy);
            if ($parent) $parent_id = $parent->term_id;
        }

        foreach ($terms as $term) {
            if (is_array($term)) {
                // Nested: term name => children array
                foreach ($term as $name => $children) {
                    $result = wp_insert_term($name, $taxonomy, ['parent' => $parent_id]);
                    if (!is_wp_error($result) && is_array($children)) {
                        $new_parent = get_term_by('name', $name, $taxonomy);
                        if ($new_parent) {
                            self::insert_terms($taxonomy, $children, $new_parent->slug);
                        }
                    }
                }
            } else {
                wp_insert_term($term, $taxonomy, ['parent' => $parent_id]);
            }
        }
    }

    private static function seed_manufacturers() {
        $manufacturers = [
            'Denago EV', 'Epic Carts', 'Evolution Electric Vehicles', 'Icon EV',
            'Club Car', 'Yamaha', 'EZGO', 'Royal EV',
            'Bintelli', 'Tomberlin', 'Star EV', 'Advanced EV',
            'Garia', 'Polaris GEM', 'Cushman', 'Columbia',
            'ACG (American Custom Golf Carts)', 'Kandi America', 'Massimo',
            'Vitacci', 'Cazador', 'TrailMaster', 'HDK Electric',
        ];
        self::insert_terms('manufacturers', $manufacturers);
    }

    private static function seed_model_families() {
        $families = [
            // Denago
            ['Denago Nomad Series' => ['Nomad', 'Nomad XL']],
            ['Denago Rover Series' => ['Rover', 'Rover XL']],
            // Epic
            ['Epic E-Series' => ['E40', 'E40L', 'E60', 'E60L']],
            // Evolution
            ['Evolution Classic Series' => ['Classic 2', 'Classic 4', 'Classic 4 Plus']],
            ['Evolution Forester Series' => ['Forester 4', 'Forester 4 Plus', 'Forester 6']],
            ['Evolution D Series' => ['D3', 'D5']],
            ['Evolution Carrier Series' => ['Carrier 6', 'Carrier 8']],
            ['Evolution Turfman Series' => ['Turfman 200', 'Turfman 500', 'Turfman 1000']],
            // Icon
            ['Icon i-Series' => ['i20', 'i40', 'i40L', 'i40F', 'i60', 'i60L']],
            // Club Car
            ['Club Car Onward' => ['Onward 2 Passenger', 'Onward 4 Passenger', 'Onward 6 Passenger', 'Onward Lifted 4 Passenger']],
            ['Club Car Tempo' => ['Tempo 2+2', 'Tempo Walk']],
            ['Club Car V4L' => ['V4L']],
            // Yamaha
            ['Yamaha Drive2' => ['Drive2 PTV', 'Drive2 Fleet', 'Drive2 QuieTech']],
            ['Yamaha UMAX' => ['UMAX One', 'UMAX Two', 'UMAX Rally']],
            // EZGO
            ['EZGO Liberty' => ['Liberty']],
            ['EZGO Express' => ['Express S4', 'Express S6', 'Express L6']],
            ['EZGO RXV' => ['RXV Elite', 'RXV Freedom']],
            // Royal EV
            ['Royal EV Ryder Series' => ['Ryder', 'Ryder XL']],
        ];
        self::insert_terms('model-family', $families);
    }

    private static function seed_models() {
        $models = [
            // Denago
            'Nomad', 'Nomad XL', 'Rover', 'Rover XL',
            // Epic
            'E40', 'E40L', 'E60', 'E60L',
            // Evolution
            'Classic 2', 'Classic 4', 'Classic 4 Plus',
            'Forester 4', 'Forester 4 Plus', 'Forester 6',
            'D3', 'D5', 'Carrier 6', 'Carrier 8',
            'Turfman 200', 'Turfman 500', 'Turfman 1000',
            // Icon
            'i20', 'i40', 'i40L', 'i40F', 'i60', 'i60L',
            // Club Car
            'Onward 2', 'Onward 4', 'Onward 6', 'Onward Lifted',
            'Tempo 2+2', 'Tempo Walk', 'V4L',
            'Precedent', 'DS',
            // Yamaha
            'Drive2 PTV', 'Drive2 Fleet', 'Drive2 QuieTech',
            'UMAX One', 'UMAX Two', 'UMAX Rally',
            // EZGO
            'Liberty', 'Express S4', 'Express S6', 'Express L6',
            'RXV Elite', 'RXV Freedom', 'TXT',
            // Royal EV
            'Ryder', 'Ryder XL',
        ];
        self::insert_terms('models', $models);
    }

    private static function seed_years() {
        $years = [];
        for ($y = 2020; $y <= 2027; $y++) {
            $years[] = (string) $y;
        }
        self::insert_terms('model-year', $years);
    }

    private static function seed_trim_levels() {
        self::insert_terms('trim-level', [
            'Base', 'Sport', 'Premium', 'XL', 'Limited',
            'Pro', 'Elite', 'Signature', 'Custom', 'Special Edition',
        ]);
    }

    private static function seed_body_styles() {
        self::insert_terms('body-style', [
            'Open', 'Enclosed Cab', 'Flatbed', 'Utility Bed',
            'Cargo Box', 'Convertible Top', 'Hard Top Enclosed',
        ]);
    }

    private static function seed_seating() {
        self::insert_terms('seating-config', [
            '2 Passenger', '2+2 Passenger', '4 Passenger',
            '4+2 Passenger', '5 Passenger', '6 Passenger',
            '6+2 Passenger', '8 Passenger',
        ]);
    }

    private static function seed_powertrains() {
        self::insert_terms('powertrain-type', [
            'Electric', 'Gas (4-Stroke)', 'Gas (2-Stroke)',
            'Hybrid', 'Solar-Assist Electric', 'Diesel',
        ]);
    }

    private static function seed_batteries() {
        self::insert_terms('battery-system', [
            'Lithium-Ion', 'Lithium Iron Phosphate (LiFePO4)', 'Lead-Acid',
            'AGM (Absorbed Glass Mat)', 'Gel Cell', 'Samsung SDI Lithium',
            'Trojan Lithium', 'RoyPow Lithium', 'Allied Lithium',
        ]);
    }

    private static function seed_motors() {
        self::insert_terms('motor-type', [
            'AC Induction', 'DC Series', 'DC Separately Excited',
            'Brushless DC (BLDC)', 'Permanent Magnet AC (PMAC)',
            'Hub Motor', 'Mid-Drive Motor',
        ]);
    }

    private static function seed_drivetrains() {
        self::insert_terms('drivetrain', [
            'Rear Wheel Drive (RWD)', 'Front Wheel Drive (FWD)',
            'All Wheel Drive (AWD)', '4 Wheel Drive (4WD)',
        ]);
    }

    private static function seed_suspension() {
        self::insert_terms('suspension-type', [
            'Independent Front & Rear', 'Leaf Spring Rear',
            'Coilover Shock', 'Air Ride Suspension',
            'Double A-Arm Front', 'McPherson Strut',
        ]);
    }

    private static function seed_braking() {
        self::insert_terms('braking-system', [
            'Four-Wheel Disc', 'Front Disc / Rear Drum',
            'Drum Brakes', 'Regenerative + Disc',
            'Hydraulic Disc', 'Mechanical Drum',
        ]);
    }

    private static function seed_frame_materials() {
        self::insert_terms('frame-material', [
            'Aircraft-Grade Aluminum', 'Tubular Steel',
            'High-Strength Steel', 'Carbon Fiber Composite',
            'Aluminum Alloy 6061', 'Galvanized Steel',
        ]);
    }

    private static function seed_wheels_tires() {
        self::insert_terms('wheel-type', [
            'Aluminum Alloy', 'Steel', 'Chrome', 'Forged Aluminum',
            'Machined', 'Matte Black', 'Gloss Black',
        ]);
        self::insert_terms('tire-type', [
            'All-Terrain', 'Street/Highway', 'Turf/Golf Course',
            'Off-Road Knobby', 'Low-Profile', 'Mud Terrain',
        ]);
        self::insert_terms('tire-rim-size', [
            '8 Inch', '10 Inch', '12 Inch', '14 Inch', '15 Inch',
        ]);
    }

    private static function seed_lighting() {
        self::insert_terms('lighting-package', [
            'Standard LED', 'Premium LED', 'Halo Lights',
            'Demon Eyes', 'Under Glow', 'Optic Fiber Trim',
            'Wheel Ring LEDs', 'Light Bar', 'LED Antenna Whips',
            'Full DOT Lighting Package',
        ]);
    }

    private static function seed_sound_systems() {
        self::insert_terms('sound-systems', [
            'None', 'Soundbar Only', 'ECOXGEAR SoundExtreme',
            '2-Speaker System', '4-Speaker System', '6-Speaker Premium',
            'Full System + Subwoofer', 'Bluetooth Soundbar',
            'JBL Premium Package', 'Kicker Audio Package',
        ]);
    }

    private static function seed_features() {
        self::insert_terms('added-features', [
            'Brush Guard', 'Light Bar', 'Fender Flares',
            'Under Glow LEDs', 'Lift Kit', 'Extended Top',
            'Rear Cargo Basket', 'Front Cargo Basket', 'Clay Basket',
            'Side Mirrors', 'Rearview Mirror', 'Backup Camera',
            'Seat Belts', 'Retractable Steps', 'Stationary Steps',
            'Hitch Receiver', 'Tow Bar', 'Enclosure',
            'Windshield (Folding)', 'Windshield (Fixed)',
            'Storage Cover', 'Dash Insert', 'USB Charging Ports',
            'Cup Holders', 'Armrests', 'Fan System',
            'Custom Steering Wheel', 'Carbon Fiber Dash',
            'Stake Sides', 'Grab Bars', '120V Inverter',
            'Charger Upgrade', 'Speed Programmer',
        ]);
    }

    private static function seed_colors() {
        // Exterior
        self::insert_terms('color-exterior', [
            'Lava Red', 'Matte Black', 'Gloss Black', 'Pearl White',
            'Silver Metallic', 'Ocean Blue', 'Forest Green', 'Sandstone',
            'Champagne', 'Burgundy', 'Orange Crush', 'Camo',
            'Matte Gray', 'Deep Purple', 'Electric Blue', 'Army Green',
            'Copper Bronze', 'Jet Black', 'Arctic White', 'Candy Red',
        ]);
        // Seat
        self::insert_terms('color-seat', [
            'Stone', 'Black', 'Tan', 'Gray', 'White',
            'Burgundy', 'Charcoal', 'Cream', 'Cognac', 'Navy',
            'Two-Tone Black/Tan', 'Two-Tone Black/Red', 'Custom',
        ]);
        // Accent
        self::insert_terms('color-accent', [
            'Chrome', 'Matte Black', 'Carbon Fiber', 'Brushed Aluminum',
            'Gold', 'Red', 'Blue', 'Custom Color Match',
        ]);
    }

    private static function seed_vehicle_classes() {
        self::insert_terms('vehicle-class', [
            'NEV (Neighborhood Electric Vehicle)',
            'LSV (Low Speed Vehicle)',
            'MSV (Medium Speed Vehicle)',
            'PTV (Personal Transportation Vehicle)',
            'ZEV (Zero Emission Vehicle)',
            'UTV (Utility Task Vehicle)',
            'Golf Cart (Course Use)',
        ]);
    }

    private static function seed_locations() {
        $locations = [
            'Hatfield PA' => 'T1 — 2333 Bethlehem Pike, Hatfield, PA 19440',
            'Ocean View NJ' => 'T2 — 101 NJ-50, Ocean View, NJ 08230',
            'Pocono Pines PA' => 'T3 — 1712 PA-940, Pocono Pines, PA 18350',
            'Dover DE' => 'T4 — 5158 N Dupont Hwy, Dover, DE 19901',
            'Scranton PA' => 'T5 — 1225 N Keyser Ave #2, Scranton, PA 18504',
            'Raleigh NC' => 'T6 — 2700 S Wilmington St, Raleigh, NC 27603',
            'South Bend IN' => 'T7 — 52129 State Road 933, South Bend, IN 46637',
            'Gloucester Point VA' => 'T8 — 2810 GW Memorial Hwy, Gloucester Point, VA 23072',
            'Lecanto FL' => 'T9 — 299 E Gulf to Lake Hwy, Lecanto, FL 34461',
            'Swanton OH' => 'T10 — 10420 Airport Hwy, Swanton, OH 43558',
            'Orangeburg SC' => 'T11 — 4166 North Rd, Orangeburg, SC 29118',
            'South Bend IN (T12)' => 'T12 — 52129 State Road 933, South Bend, IN 46637',
            'Virginia Beach VA' => 'T13 — 1101 Virginia Beach Blvd, VA 23451',
        ];
        foreach ($locations as $name => $description) {
            wp_insert_term($name, 'location', ['description' => $description]);
        }
    }

    private static function seed_inventory_statuses() {
        self::insert_terms('inventory-status', [
            'In Stock', 'Sold', 'On Order', 'In Transit',
            'Ready for Sale (RFS)', 'Not Ready for Sale',
            'In Service', 'Boneyard', 'Reserved', 'Delivered',
        ]);
    }

    private static function seed_price_tiers() {
        self::insert_terms('price-tier', [
            'Entry ($3,000-$6,000)',
            'Mid-Range ($6,000-$10,000)',
            'Premium ($10,000-$15,000)',
            'Ultra-Premium ($15,000-$25,000)',
            'Custom Build ($25,000+)',
        ]);
    }

    private static function seed_shipping_zones() {
        self::insert_terms('shipping-zone', [
            '1 to 3 Days — Local (PA, NJ, DE)',
            '3 to 7 Days — Regional (East Coast)',
            '5 to 9 Days — National (OTR)',
            'Dealer-to-Dealer Transfer',
            'Customer Pickup',
        ]);
    }

    private static function seed_warranty_tiers() {
        self::insert_terms('warranty-tier', [
            '1 Year Standard',
            '2 Year Extended',
            '3 Year Comprehensive',
            '5 Year Battery',
            '5 Year Frame',
            'Lifetime Frame',
            'TIGON Dealership Warranty',
        ]);
    }

    private static function seed_certifications() {
        self::insert_terms('certification', [
            'DOT Compliant', 'NHTSA Registered', 'UL Certified',
            'CE Marked', 'SAE Standards', 'EPA Compliant',
        ]);
    }

    private static function seed_compliance() {
        self::insert_terms('compliance-class', [
            'Federal LSV (max 25 mph)',
            'State NEV',
            'Federal MSV (max 35 mph)',
            'Private Property Only',
            'Full Street Legal',
            'Highway Capable',
        ]);
    }
}
