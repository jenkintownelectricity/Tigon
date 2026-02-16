<?php
/**
 * Front Page Template — Tigon Golf Carts Homepage
 */
get_header(); ?>

<!-- HERO -->
<section class="tigon-hero">
    <div class="tigon-hero__overlay"></div>
    <div class="tigon-hero__content">
        <h1 class="tigon-hero__title">
            Premium <span>Electric Golf Carts</span><br>
            Built for Every Terrain
        </h1>
        <p class="tigon-hero__subtitle">
            New &amp; certified pre-owned golf carts from Denago, Epic, Evolution, Icon, Club Car, Yamaha, EZGO &amp; Royal EV.
            Aircraft-grade aluminum. Lithium-ion. Nationwide delivery.
        </p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="tigon-btn tigon-btn--primary">Shop Inventory</a>
            <a href="tel:1-844-844-6638" class="tigon-btn tigon-btn--outline">Call 1-844-TIGON</a>
        </div>
    </div>
</section>

<!-- MANUFACTURER SHOWCASE -->
<section class="tigon-section">
    <div class="tigon-container">
        <h2 style="font-family:var(--tigon-font-heading);font-size:2rem;font-weight:800;text-align:center;margin-bottom:0.5rem;">
            Shop by Manufacturer
        </h2>
        <p style="text-align:center;color:var(--tigon-gray);margin-bottom:2rem;">
            We carry the industry's top brands. Click a manufacturer to explore models.
        </p>
        <div class="tigon-manufacturers">
            <?php
            $manufacturers = get_terms([
                'taxonomy'   => 'manufacturers',
                'hide_empty' => false,
                'orderby'    => 'count',
                'order'      => 'DESC',
            ]);
            if (!is_wp_error($manufacturers) && !empty($manufacturers)) :
                foreach ($manufacturers as $mfg) :
                    $link = get_term_link($mfg);
                    if (is_wp_error($link)) continue;
                    ?>
                    <a href="<?php echo esc_url($link); ?>" class="tigon-manufacturer-card">
                        <h3 class="tigon-manufacturer-card__name"><?php echo esc_html($mfg->name); ?></h3>
                        <span class="tigon-manufacturer-card__count"><?php echo esc_html($mfg->count); ?> vehicles</span>
                    </a>
                <?php endforeach;
            else : ?>
                <?php
                $brands = ['Denago EV', 'Epic Carts', 'Evolution', 'Icon EV', 'Club Car', 'Yamaha', 'EZGO', 'Royal EV'];
                foreach ($brands as $brand) : ?>
                    <div class="tigon-manufacturer-card">
                        <h3 class="tigon-manufacturer-card__name"><?php echo esc_html($brand); ?></h3>
                        <span class="tigon-manufacturer-card__count">View Models</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FEATURED INVENTORY -->
<section class="tigon-section tigon-section--dark">
    <div class="tigon-container">
        <h2 style="font-family:var(--tigon-font-heading);font-size:2rem;font-weight:800;text-align:center;margin-bottom:0.5rem;color:var(--tigon-gold);">
            Featured Inventory
        </h2>
        <p style="text-align:center;color:var(--tigon-gray-light);margin-bottom:2rem;">
            Explore our latest arrivals and best sellers across all locations.
        </p>
        <div class="tigon-inventory-grid">
            <?php
            $featured = new WP_Query([
                'post_type'      => 'product',
                'posts_per_page' => 6,
                'post_status'    => 'publish',
                'meta_key'       => 'total_sales',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
            ]);

            if ($featured->have_posts()) :
                while ($featured->have_posts()) : $featured->the_post();
                    global $product;
                    if (!$product) continue;
                    $manufacturers = wp_get_object_terms(get_the_ID(), 'manufacturers', ['fields' => 'names']);
                    $mfg_name = !empty($manufacturers) ? $manufacturers[0] : '';
                    $condition = get_post_meta(get_the_ID(), '_tigon_condition', true);
                    ?>
                    <a href="<?php the_permalink(); ?>" class="tigon-cart-card">
                        <?php if ($condition) : ?>
                            <span class="tigon-cart-card__badge tigon-cart-card__badge--<?php echo esc_attr($condition); ?>">
                                <?php echo esc_html(ucfirst($condition)); ?>
                            </span>
                        <?php endif; ?>
                        <div class="tigon-cart-card__image">
                            <?php the_post_thumbnail('tigon-cart-card'); ?>
                        </div>
                        <div class="tigon-cart-card__body">
                            <?php if ($mfg_name) : ?>
                                <div class="tigon-cart-card__manufacturer"><?php echo esc_html($mfg_name); ?></div>
                            <?php endif; ?>
                            <h3 class="tigon-cart-card__title"><?php the_title(); ?></h3>
                            <div class="tigon-cart-card__price">
                                <?php echo $product->get_price_html(); ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile;
                wp_reset_postdata();
            else : ?>
                <p style="color:var(--tigon-gray-light);text-align:center;grid-column:1/-1;">
                    Inventory coming soon. Contact us at 1-844-844-6638 for current availability.
                </p>
            <?php endif; ?>
        </div>
        <div style="text-align:center;margin-top:2rem;">
            <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="tigon-btn tigon-btn--primary">View All Inventory</a>
        </div>
    </div>
</section>

<!-- VEHICLE CLASSES -->
<section class="tigon-section">
    <div class="tigon-container">
        <h2 style="font-family:var(--tigon-font-heading);font-size:2rem;font-weight:800;text-align:center;margin-bottom:2rem;">
            Shop by Vehicle Class
        </h2>
        <div class="tigon-manufacturers">
            <?php
            $classes = [
                'NEV'  => 'Neighborhood Electric Vehicles',
                'MSV'  => 'Medium Speed Vehicles',
                'PTV'  => 'Personal Transportation Vehicles',
                'ZEV'  => 'Zero Emission Vehicles',
                'UTV'  => 'Utility Task Vehicles',
                'LSV'  => 'Low Speed Vehicles',
            ];
            foreach ($classes as $abbr => $full) : ?>
                <div class="tigon-manufacturer-card">
                    <h3 class="tigon-manufacturer-card__name"><?php echo esc_html($abbr); ?></h3>
                    <span class="tigon-manufacturer-card__count"><?php echo esc_html($full); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- LOCATIONS -->
<section class="tigon-section tigon-section--gold">
    <div class="tigon-container">
        <h2 style="font-family:var(--tigon-font-heading);font-size:2rem;font-weight:800;text-align:center;margin-bottom:2rem;">
            13 Locations Nationwide
        </h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1.5rem;">
            <?php
            $locations = [
                ['name' => 'Hatfield, PA', 'phone' => '215-595-8736', 'address' => '2333 Bethlehem Pike'],
                ['name' => 'Ocean View, NJ', 'phone' => '609-840-0404', 'address' => '101 NJ-50'],
                ['name' => 'Pocono Pines, PA', 'phone' => '570-643-0152', 'address' => '1712 PA-940'],
                ['name' => 'Dover, DE', 'phone' => '302-546-0010', 'address' => '5158 N Dupont Hwy'],
                ['name' => 'Scranton, PA', 'phone' => '570-344-4443', 'address' => '1225 N Keyser Ave #2'],
                ['name' => 'Raleigh, NC', 'phone' => '984-489-0298', 'address' => '2700 S Wilmington St'],
                ['name' => 'South Bend, IN', 'phone' => '574-703-0456', 'address' => '52129 State Road 933'],
                ['name' => 'Gloucester Point, VA', 'phone' => '804-792-0234', 'address' => '2810 George Washington Memorial Hwy'],
                ['name' => 'Lecanto, FL', 'phone' => '352-453-0345', 'address' => '299 E Gulf to Lake Hwy'],
                ['name' => 'Swanton, OH', 'phone' => '419-402-8400', 'address' => '10420 Airport Hwy'],
                ['name' => 'Orangeburg, SC', 'phone' => '803-596-0246', 'address' => '4166 North Rd'],
                ['name' => 'Virginia Beach, VA', 'phone' => '1-844-844-6638', 'address' => '1101 Virginia Beach Blvd'],
            ];
            foreach ($locations as $loc) : ?>
                <div style="background:rgba(0,0,0,0.1);padding:1.25rem;border-radius:8px;">
                    <strong style="font-size:1.1rem;"><?php echo esc_html($loc['name']); ?></strong><br>
                    <span style="font-size:0.85rem;"><?php echo esc_html($loc['address']); ?></span><br>
                    <a href="tel:<?php echo esc_attr($loc['phone']); ?>" style="color:var(--tigon-black);font-weight:600;">
                        <?php echo esc_html($loc['phone']); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="tigon-section" style="text-align:center;">
    <div class="tigon-container">
        <h2 style="font-family:var(--tigon-font-heading);font-size:2.5rem;font-weight:800;margin-bottom:1rem;">
            Ready to Roll?
        </h2>
        <p style="font-size:1.1rem;color:var(--tigon-gray);max-width:600px;margin:0 auto 2rem;">
            0% financing available on select models. Nationwide delivery. Full warranty coverage.
        </p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="tigon-btn tigon-btn--primary">Shop Now</a>
            <a href="tel:1-844-844-6638" class="tigon-btn tigon-btn--dark">Call 1-844-TIGON</a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
