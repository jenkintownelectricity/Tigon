<?php get_header(); ?>

<section class="tigon-section" style="text-align:center;min-height:50vh;display:flex;align-items:center;">
    <div class="tigon-container">
        <h1 style="font-family:var(--tigon-font-heading);font-size:4rem;font-weight:800;color:var(--tigon-gold);">404</h1>
        <p style="font-size:1.2rem;color:var(--tigon-gray);margin-bottom:2rem;">
            The page you're looking for has driven off the lot.
        </p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="tigon-btn tigon-btn--primary">Go Home</a>
            <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="tigon-btn tigon-btn--dark">Browse Inventory</a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
