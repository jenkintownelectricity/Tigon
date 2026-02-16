<?php
/**
 * WooCommerce Archive Product — Tigon Inventory Grid
 */
defined('ABSPATH') || exit;
get_header();

$current_manufacturer = get_queried_object();
$is_manufacturer = is_tax('manufacturers');
$is_model = is_tax('models');
?>

<section class="tigon-section" style="padding-top:2rem;">
    <div class="tigon-container">

        <!-- Breadcrumb -->
        <nav style="margin-bottom:1.5rem;font-size:0.85rem;color:var(--tigon-gray);">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &raquo;
            <?php if ($is_manufacturer) : ?>
                <a href="<?php echo esc_url(home_url('/shop/')); ?>">Inventory</a> &raquo;
                <span><?php echo esc_html($current_manufacturer->name); ?></span>
            <?php elseif ($is_model) : ?>
                <a href="<?php echo esc_url(home_url('/shop/')); ?>">Inventory</a> &raquo;
                <span><?php echo esc_html($current_manufacturer->name); ?></span>
            <?php else : ?>
                <span>Inventory</span>
            <?php endif; ?>
        </nav>

        <h1 style="font-family:var(--tigon-font-heading);font-size:2rem;font-weight:800;margin-bottom:1.5rem;">
            <?php if ($is_manufacturer) : ?>
                <?php echo esc_html($current_manufacturer->name); ?> Golf Carts
            <?php elseif ($is_model) : ?>
                <?php echo esc_html($current_manufacturer->name); ?> Models
            <?php else : ?>
                Golf Cart Inventory
            <?php endif; ?>
        </h1>

        <!-- FILTERS -->
        <div class="tigon-filters">
            <form method="get" action="<?php echo esc_url(home_url('/shop/')); ?>" class="tigon-filters__row">
                <div class="tigon-filters__group">
                    <label class="tigon-filters__label">Manufacturer</label>
                    <select name="filter_manufacturer" class="tigon-filters__select" onchange="this.form.submit()">
                        <option value="">All Manufacturers</option>
                        <?php
                        $manufacturers = get_terms(['taxonomy' => 'manufacturers', 'hide_empty' => false]);
                        if (!is_wp_error($manufacturers)) :
                            foreach ($manufacturers as $mfg) : ?>
                                <option value="<?php echo esc_attr($mfg->slug); ?>"
                                    <?php selected(get_query_var('filter_manufacturer'), $mfg->slug); ?>>
                                    <?php echo esc_html($mfg->name); ?>
                                </option>
                            <?php endforeach;
                        endif; ?>
                    </select>
                </div>

                <div class="tigon-filters__group">
                    <label class="tigon-filters__label">Model</label>
                    <select name="filter_model" class="tigon-filters__select" onchange="this.form.submit()">
                        <option value="">All Models</option>
                        <?php
                        $models = get_terms(['taxonomy' => 'models', 'hide_empty' => false]);
                        if (!is_wp_error($models)) :
                            foreach ($models as $model) : ?>
                                <option value="<?php echo esc_attr($model->slug); ?>"
                                    <?php selected(get_query_var('filter_model'), $model->slug); ?>>
                                    <?php echo esc_html($model->name); ?>
                                </option>
                            <?php endforeach;
                        endif; ?>
                    </select>
                </div>

                <div class="tigon-filters__group">
                    <label class="tigon-filters__label">Vehicle Class</label>
                    <select name="filter_class" class="tigon-filters__select" onchange="this.form.submit()">
                        <option value="">All Classes</option>
                        <?php
                        $classes = get_terms(['taxonomy' => 'vehicle-class', 'hide_empty' => false]);
                        if (!is_wp_error($classes)) :
                            foreach ($classes as $vc) : ?>
                                <option value="<?php echo esc_attr($vc->slug); ?>"><?php echo esc_html($vc->name); ?></option>
                            <?php endforeach;
                        endif; ?>
                    </select>
                </div>

                <div class="tigon-filters__group">
                    <label class="tigon-filters__label">Condition</label>
                    <select name="filter_condition" class="tigon-filters__select" onchange="this.form.submit()">
                        <option value="">New & Used</option>
                        <option value="new">New</option>
                        <option value="used">Used</option>
                        <option value="cpo">Certified Pre-Owned</option>
                    </select>
                </div>

                <div class="tigon-filters__group">
                    <label class="tigon-filters__label">Location</label>
                    <select name="filter_location" class="tigon-filters__select" onchange="this.form.submit()">
                        <option value="">All Locations</option>
                        <?php
                        $locations = get_terms(['taxonomy' => 'location', 'hide_empty' => false]);
                        if (!is_wp_error($locations)) :
                            foreach ($locations as $loc) : ?>
                                <option value="<?php echo esc_attr($loc->slug); ?>"><?php echo esc_html($loc->name); ?></option>
                            <?php endforeach;
                        endif; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- PRODUCT GRID -->
        <?php if (woocommerce_product_loop()) : ?>
            <div class="tigon-inventory-grid">
                <?php while (have_posts()) : the_post();
                    global $product;
                    if (!$product) continue;
                    $manufacturers = wp_get_object_terms(get_the_ID(), 'manufacturers', ['fields' => 'names']);
                    $mfg_name = !empty($manufacturers) ? $manufacturers[0] : '';
                    $condition = get_post_meta(get_the_ID(), '_tigon_condition', true);
                    $year = get_post_meta(get_the_ID(), '_tigon_year', true);
                    $street_legal = get_post_meta(get_the_ID(), '_tigon_street_legal', true);
                    $location_terms = wp_get_object_terms(get_the_ID(), 'location', ['fields' => 'names']);
                    $location_name = !empty($location_terms) ? $location_terms[0] : '';
                    ?>
                    <a href="<?php the_permalink(); ?>" class="tigon-cart-card">
                        <?php if ($condition) : ?>
                            <span class="tigon-cart-card__badge tigon-cart-card__badge--<?php echo esc_attr($condition); ?>">
                                <?php echo esc_html(ucfirst($condition)); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($product->is_on_sale()) : ?>
                            <span class="tigon-cart-card__badge tigon-cart-card__badge--sale" style="right:1rem;left:auto;">Sale</span>
                        <?php endif; ?>
                        <div class="tigon-cart-card__image">
                            <?php the_post_thumbnail('tigon-cart-card'); ?>
                        </div>
                        <div class="tigon-cart-card__body">
                            <?php if ($mfg_name) : ?>
                                <div class="tigon-cart-card__manufacturer"><?php echo esc_html($mfg_name); ?></div>
                            <?php endif; ?>
                            <h3 class="tigon-cart-card__title"><?php the_title(); ?></h3>
                            <div class="tigon-cart-card__meta">
                                <?php if ($year) : ?>
                                    <span class="tigon-cart-card__tag"><?php echo esc_html($year); ?></span>
                                <?php endif; ?>
                                <?php if ($street_legal === 'yes') : ?>
                                    <span class="tigon-cart-card__tag">Street Legal</span>
                                <?php endif; ?>
                            </div>
                            <div class="tigon-cart-card__price">
                                <?php echo $product->get_price_html(); ?>
                            </div>
                        </div>
                        <?php if ($location_name) : ?>
                            <div class="tigon-cart-card__footer">
                                <span class="tigon-cart-card__location"><?php echo esc_html($location_name); ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            </div>

            <?php
            // Pagination
            the_posts_pagination([
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
            ]);
            ?>

        <?php else : ?>
            <p style="text-align:center;padding:3rem;color:var(--tigon-gray);">
                No golf carts found matching your criteria. Try adjusting your filters or
                <a href="tel:1-844-844-6638">call us at 1-844-844-6638</a>.
            </p>
        <?php endif; ?>

    </div>
</section>

<?php get_footer(); ?>
