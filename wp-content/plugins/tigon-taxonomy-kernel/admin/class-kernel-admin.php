<?php
/**
 * Kernel Admin — Taxonomy management dashboard
 *
 * @package TigonTaxonomyKernel
 */

defined('ABSPATH') || exit;

class Tigon_Kernel_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu() {
        add_menu_page(
            'Tigon Taxonomy Kernel',
            'Taxonomy Kernel',
            'manage_woocommerce',
            'tigon-kernel',
            [$this, 'render_dashboard'],
            'dashicons-category',
            56
        );

        add_submenu_page('tigon-kernel', 'Taxonomy Layers', 'All 50 Layers', 'manage_woocommerce', 'tigon-kernel-layers', [$this, 'render_layers']);
        add_submenu_page('tigon-kernel', 'Re-Seed', 'Re-Seed Terms', 'manage_woocommerce', 'tigon-kernel-reseed', [$this, 'render_reseed']);
    }

    public function render_dashboard() {
        $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
        $total_terms = 0;
        $layer_stats = [];

        foreach ($layers as $slug => $config) {
            $count = wp_count_terms(['taxonomy' => $slug, 'hide_empty' => false]);
            if (is_wp_error($count)) $count = 0;
            $total_terms += $count;
            $layer_stats[$slug] = [
                'label' => $config[0],
                'layer' => $config[3],
                'count' => $count,
                'hierarchical' => $config[2] ? 'Yes' : 'No',
            ];
        }

        $cpts = Tigon_Post_Type_Registry::get_post_types();
        $attributes = Tigon_Attribute_Registry::get_attributes();
        ?>
        <div class="wrap">
            <h1 style="color:#c8a84e;">Tigon Taxonomy Kernel — 50-Layer DNA System</h1>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin:2rem 0;">
                <div style="background:#fff;padding:1.5rem;border-left:4px solid #c8a84e;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;color:#666;font-size:0.8rem;text-transform:uppercase;">Taxonomy Layers</h3>
                    <p style="font-size:2.5rem;font-weight:800;margin:0.5rem 0 0;"><?php echo count($layers); ?></p>
                </div>
                <div style="background:#fff;padding:1.5rem;border-left:4px solid #28a745;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;color:#666;font-size:0.8rem;text-transform:uppercase;">Total Terms</h3>
                    <p style="font-size:2.5rem;font-weight:800;margin:0.5rem 0 0;"><?php echo number_format($total_terms); ?></p>
                </div>
                <div style="background:#fff;padding:1.5rem;border-left:4px solid #007bff;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;color:#666;font-size:0.8rem;text-transform:uppercase;">Custom Post Types</h3>
                    <p style="font-size:2.5rem;font-weight:800;margin:0.5rem 0 0;"><?php echo count($cpts); ?></p>
                </div>
                <div style="background:#fff;padding:1.5rem;border-left:4px solid #6f42c1;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;color:#666;font-size:0.8rem;text-transform:uppercase;">WC Attributes</h3>
                    <p style="font-size:2.5rem;font-weight:800;margin:0.5rem 0 0;"><?php echo count($attributes); ?></p>
                </div>
            </div>

            <h2>50-Layer Taxonomy Overview</h2>
            <table class="widefat striped" style="margin-bottom:2rem;">
                <thead>
                    <tr>
                        <th>Layer</th><th>Taxonomy</th><th>Slug</th><th>Type</th><th>Terms</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($layer_stats as $slug => $stat) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($stat['layer']); ?></strong></td>
                            <td><?php echo esc_html($stat['label']); ?></td>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td><?php echo $stat['hierarchical'] === 'Yes' ? 'Hierarchical' : 'Flat'; ?></td>
                            <td><?php echo esc_html($stat['count']); ?></td>
                            <td>
                                <a href="<?php echo admin_url("edit-tags.php?taxonomy={$slug}&post_type=product"); ?>">Manage</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Custom Post Types</h2>
            <table class="widefat striped">
                <thead><tr><th>Post Type</th><th>Label</th><th>Public</th><th>Taxonomies</th></tr></thead>
                <tbody>
                    <?php foreach ($cpts as $slug => $config) : ?>
                        <tr>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td><?php echo esc_html($config['plural']); ?></td>
                            <td><?php echo $config['public'] ? 'Yes' : 'Admin Only'; ?></td>
                            <td><?php echo esc_html(implode(', ', $config['taxonomies'] ?? [])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_layers() {
        $layers = Tigon_Taxonomy_Registry::get_taxonomy_layers();
        ?>
        <div class="wrap">
            <h1>All 50 Taxonomy Layers</h1>
            <table class="widefat striped">
                <thead><tr><th>#</th><th>Label</th><th>Slug</th><th>Description</th><th>Hierarchical</th></tr></thead>
                <tbody>
                    <?php foreach ($layers as $slug => $config) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($config[3]); ?></strong></td>
                            <td><a href="<?php echo admin_url("edit-tags.php?taxonomy={$slug}&post_type=product"); ?>"><?php echo esc_html($config[0]); ?></a></td>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td><?php echo esc_html($config[4]); ?></td>
                            <td><?php echo $config[2] ? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_reseed() {
        if (isset($_POST['tigon_reseed']) && wp_verify_nonce($_POST['_wpnonce'], 'tigon_reseed')) {
            Tigon_Taxonomy_Seeder::seed_all();
            echo '<div class="notice notice-success"><p>All taxonomy terms have been re-seeded.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Re-Seed Taxonomy Terms</h1>
            <p>This will re-populate all 50 taxonomy layers with default terms. Existing terms will not be duplicated.</p>
            <form method="post">
                <?php wp_nonce_field('tigon_reseed'); ?>
                <input type="hidden" name="tigon_reseed" value="1">
                <?php submit_button('Re-Seed All Taxonomies', 'primary', 'submit', true); ?>
            </form>
        </div>
        <?php
    }
}

new Tigon_Kernel_Admin();
