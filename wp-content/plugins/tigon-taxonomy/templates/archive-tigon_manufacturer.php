<?php
/**
 * Archive Manufacturer Template.
 *
 * Displays a grid of all manufacturers with logos,
 * descriptions, and links to individual manufacturer pages.
 *
 * @package Tigon_Taxonomy
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <header class="page-header">
            <h1 class="page-title">Our Manufacturers</h1>
            <p class="archive-description">Browse our selection of premium golf cart and electric vehicle manufacturers.</p>
        </header>

        <?php if ( have_posts() ) : ?>

            <div class="tigon-manufacturers-grid">

                <?php while ( have_posts() ) : the_post(); ?>

                    <div class="tigon-manufacturer-card">
                        <?php
                        $brand_logo = get_post_meta( get_the_ID(), '_tigon_brand_logo', true );
                        if ( $brand_logo ) {
                            echo '<div class="tigon-card-logo">';
                            echo '<a href="' . esc_url( get_permalink() ) . '">';
                            echo wp_get_attachment_image( $brand_logo, 'thumbnail' );
                            echo '</a></div>';
                        } elseif ( has_post_thumbnail() ) {
                            echo '<div class="tigon-card-logo">';
                            echo '<a href="' . esc_url( get_permalink() ) . '">';
                            the_post_thumbnail( 'thumbnail' );
                            echo '</a></div>';
                        }
                        ?>

                        <h2 class="tigon-card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <?php
                        $tagline = get_post_meta( get_the_ID(), '_tigon_brand_tagline', true );
                        if ( $tagline ) {
                            echo '<p class="tigon-card-tagline">' . esc_html( $tagline ) . '</p>';
                        }
                        ?>

                        <?php if ( has_excerpt() ) : ?>
                            <div class="tigon-card-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        // Show model count from linked WC category.
                        $wc_cat_id = get_post_meta( get_the_ID(), '_tigon_wc_category_id', true );
                        if ( $wc_cat_id ) {
                            $models = get_terms( array(
                                'taxonomy'   => 'product_cat',
                                'parent'     => (int) $wc_cat_id,
                                'hide_empty' => false,
                            ) );
                            if ( ! empty( $models ) && ! is_wp_error( $models ) ) {
                                echo '<p class="tigon-card-model-count">' . count( $models ) . ' model(s) available</p>';
                            }
                        }
                        ?>

                        <a href="<?php the_permalink(); ?>" class="tigon-card-link">View Models &rarr;</a>
                    </div>

                <?php endwhile; ?>

            </div>

            <?php the_posts_pagination( array(
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
            ) ); ?>

        <?php else : ?>

            <p>No manufacturers found. Please check back later.</p>

        <?php endif; ?>

    </main>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
