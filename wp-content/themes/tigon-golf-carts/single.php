<?php get_header(); ?>

<section class="tigon-section">
    <div class="tigon-container" style="max-width:800px;">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h1 style="font-family:var(--tigon-font-heading);font-size:2rem;font-weight:800;margin-bottom:1rem;">
                    <?php the_title(); ?>
                </h1>
                <div style="color:var(--tigon-gray);font-size:0.85rem;margin-bottom:2rem;">
                    <?php echo get_the_date(); ?> | <?php the_author(); ?>
                </div>
                <?php if (has_post_thumbnail()) : ?>
                    <div style="margin-bottom:2rem;border-radius:8px;overflow:hidden;">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</section>

<?php get_footer(); ?>
