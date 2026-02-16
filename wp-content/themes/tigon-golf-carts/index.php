<?php get_header(); ?>

<section class="tigon-hero">
    <div class="tigon-hero__overlay"></div>
    <div class="tigon-hero__content">
        <h1 class="tigon-hero__title">
            Premium <span>Electric Golf Carts</span><br>
            Sales, Service &amp; Rentals
        </h1>
        <p class="tigon-hero__subtitle">
            Over 25 years of industry expertise. Aircraft-grade aluminum frames,
            lithium batteries, and regenerative braking. Nationwide delivery.
        </p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="tigon-btn tigon-btn--primary">Browse Inventory</a>
            <a href="<?php echo esc_url(home_url('/brands-manufacturers/')); ?>" class="tigon-btn tigon-btn--outline">View Brands</a>
        </div>
    </div>
</section>

<?php if (have_posts()) : ?>
<section class="tigon-section">
    <div class="tigon-container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="entry-content"><?php the_content(); ?></div>
            </article>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
