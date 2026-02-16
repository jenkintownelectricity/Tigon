<?php
/**
 * Single Product — Tigon Golf Cart Detail Page
 */
defined('ABSPATH') || exit;
get_header();

while (have_posts()) : the_post();
    global $product;
    if (!$product) continue;

    $manufacturers = wp_get_object_terms(get_the_ID(), 'manufacturers', ['fields' => 'all']);
    $models = wp_get_object_terms(get_the_ID(), 'models', ['fields' => 'all']);
    $vehicle_classes = wp_get_object_terms(get_the_ID(), 'vehicle-class', ['fields' => 'names']);
    $drivetrains = wp_get_object_terms(get_the_ID(), 'drivetrain', ['fields' => 'names']);
    $features = wp_get_object_terms(get_the_ID(), 'added-features', ['fields' => 'names']);
    $location_terms = wp_get_object_terms(get_the_ID(), 'location', ['fields' => 'all']);

    $mfg_name = !empty($manufacturers) ? $manufacturers[0]->name : '';
    $model_name = !empty($models) ? $models[0]->name : '';
    $condition = get_post_meta(get_the_ID(), '_tigon_condition', true);
    $year = get_post_meta(get_the_ID(), '_tigon_year', true);
    $vin = get_post_meta(get_the_ID(), '_tigon_vin', true);
    $serial = get_post_meta(get_the_ID(), '_tigon_serial', true);
    $street_legal = get_post_meta(get_the_ID(), '_tigon_street_legal', true);
    $electric = get_post_meta(get_the_ID(), '_tigon_electric', true);
?>

<section class="tigon-single-cart">
    <!-- Breadcrumb -->
    <nav style="margin-bottom:1.5rem;font-size:0.85rem;color:var(--tigon-gray);">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &raquo;
        <a href="<?php echo esc_url(home_url('/shop/')); ?>">Inventory</a> &raquo;
        <?php if ($mfg_name && !empty($manufacturers)) : ?>
            <a href="<?php echo esc_url(get_term_link($manufacturers[0])); ?>"><?php echo esc_html($mfg_name); ?></a> &raquo;
        <?php endif; ?>
        <span><?php the_title(); ?></span>
    </nav>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:3rem;">
        <!-- Gallery -->
        <div>
            <div class="tigon-single-cart__main-image" style="border-radius:8px;overflow:hidden;margin-bottom:1rem;">
                <?php the_post_thumbnail('tigon-cart-hero', ['style' => 'width:100%;height:auto;']); ?>
            </div>
            <?php
            $gallery_ids = $product->get_gallery_image_ids();
            if (!empty($gallery_ids)) : ?>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem;">
                    <?php foreach ($gallery_ids as $img_id) : ?>
                        <div style="border-radius:4px;overflow:hidden;cursor:pointer;">
                            <?php echo wp_get_attachment_image($img_id, 'tigon-cart-thumb', false, ['style' => 'width:100%;height:auto;']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div>
            <?php if ($mfg_name) : ?>
                <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;color:var(--tigon-gold);letter-spacing:0.1em;margin-bottom:0.3rem;">
                    <?php echo esc_html($mfg_name); ?>
                </div>
            <?php endif; ?>

            <h1 style="font-family:var(--tigon-font-heading);font-size:2rem;font-weight:800;margin-bottom:0.5rem;">
                <?php the_title(); ?>
            </h1>

            <?php if ($condition) : ?>
                <span class="tigon-cart-card__badge tigon-cart-card__badge--<?php echo esc_attr($condition); ?>"
                      style="position:static;display:inline-block;margin-bottom:1rem;">
                    <?php echo esc_html(ucfirst($condition)); ?>
                </span>
            <?php endif; ?>

            <div style="font-family:var(--tigon-font-heading);font-size:2.5rem;font-weight:800;margin-bottom:1.5rem;">
                <?php echo $product->get_price_html(); ?>
            </div>

            <p style="margin-bottom:1rem;color:var(--tigon-gold);font-weight:600;">
                0% Financing Available on Select Models
            </p>

            <!-- Key Specs Grid -->
            <div class="tigon-single-cart__specs" style="margin-bottom:2rem;">
                <?php if ($year) : ?>
                    <div class="tigon-spec-card">
                        <div class="tigon-spec-card__label">Year</div>
                        <div class="tigon-spec-card__value"><?php echo esc_html($year); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($model_name) : ?>
                    <div class="tigon-spec-card">
                        <div class="tigon-spec-card__label">Model</div>
                        <div class="tigon-spec-card__value"><?php echo esc_html($model_name); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($street_legal === 'yes') : ?>
                    <div class="tigon-spec-card">
                        <div class="tigon-spec-card__label">Street Legal</div>
                        <div class="tigon-spec-card__value">Yes</div>
                    </div>
                <?php endif; ?>
                <?php if ($electric === 'yes') : ?>
                    <div class="tigon-spec-card">
                        <div class="tigon-spec-card__label">Powertrain</div>
                        <div class="tigon-spec-card__value">Electric</div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($vehicle_classes)) : ?>
                    <div class="tigon-spec-card">
                        <div class="tigon-spec-card__label">Vehicle Class</div>
                        <div class="tigon-spec-card__value"><?php echo esc_html(implode(', ', $vehicle_classes)); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($drivetrains)) : ?>
                    <div class="tigon-spec-card">
                        <div class="tigon-spec-card__label">Drivetrain</div>
                        <div class="tigon-spec-card__value"><?php echo esc_html(implode(', ', $drivetrains)); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($vin) : ?>
                    <div class="tigon-spec-card">
                        <div class="tigon-spec-card__label">VIN</div>
                        <div class="tigon-spec-card__value" style="font-size:0.85rem;"><?php echo esc_html($vin); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($location_terms)) : ?>
                    <div class="tigon-spec-card">
                        <div class="tigon-spec-card__label">Location</div>
                        <div class="tigon-spec-card__value"><?php echo esc_html($location_terms[0]->name); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- WooCommerce Add to Cart -->
            <?php woocommerce_template_single_add_to_cart(); ?>

            <!-- Added Features -->
            <?php if (!empty($features)) : ?>
                <div style="margin-top:2rem;">
                    <h3 style="font-family:var(--tigon-font-heading);font-weight:700;margin-bottom:0.5rem;">Added Features</h3>
                    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                        <?php foreach ($features as $feat) : ?>
                            <span class="tigon-cart-card__tag" style="padding:0.4rem 0.8rem;font-size:0.85rem;">
                                <?php echo esc_html($feat); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Contact CTA -->
            <div style="margin-top:2rem;padding:1.5rem;background:var(--tigon-off-white);border-radius:8px;">
                <p style="font-weight:700;margin-bottom:0.5rem;">Questions about this cart?</p>
                <a href="tel:1-844-844-6638" class="tigon-btn tigon-btn--primary" style="width:100%;justify-content:center;">
                    Call 1-844-844-6638
                </a>
            </div>
        </div>
    </div>

    <!-- Full Description -->
    <div style="margin-bottom:3rem;">
        <h2 style="font-family:var(--tigon-font-heading);font-weight:800;margin-bottom:1rem;">Description</h2>
        <div class="entry-content">
            <?php the_content(); ?>
        </div>
    </div>

    <!-- Product Attributes (WooCommerce) -->
    <?php
    $attributes = $product->get_attributes();
    if (!empty($attributes)) : ?>
        <div style="margin-bottom:3rem;">
            <h2 style="font-family:var(--tigon-font-heading);font-weight:800;margin-bottom:1rem;">Specifications</h2>
            <table style="width:100%;border-collapse:collapse;">
                <?php foreach ($attributes as $attr) :
                    $name = wc_attribute_label($attr->get_name());
                    $values = [];
                    if ($attr->is_taxonomy()) {
                        $values = wc_get_product_terms($product->get_id(), $attr->get_name(), ['fields' => 'names']);
                    } else {
                        $values = $attr->get_options();
                    }
                    if (empty($values)) continue;
                    ?>
                    <tr style="border-bottom:1px solid var(--tigon-gray-light);">
                        <td style="padding:0.75rem;font-weight:600;width:200px;color:var(--tigon-gray);">
                            <?php echo esc_html($name); ?>
                        </td>
                        <td style="padding:0.75rem;">
                            <?php echo esc_html(implode(', ', $values)); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

    <!-- WooCommerce Tabs (Reviews, etc) -->
    <?php woocommerce_output_product_data_tabs(); ?>

    <!-- Related Products -->
    <?php woocommerce_output_related_products(); ?>

</section>

<?php endwhile; ?>

<?php get_footer(); ?>
