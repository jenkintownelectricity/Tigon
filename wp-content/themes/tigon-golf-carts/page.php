<?php get_header(); ?>

<section class="tigon-section">
    <div class="tigon-container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h1 style="font-family:var(--tigon-font-heading);font-size:2.5rem;font-weight:800;margin-bottom:1.5rem;">
                    <?php the_title(); ?>
                </h1>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</section>

<?php get_footer(); ?>
