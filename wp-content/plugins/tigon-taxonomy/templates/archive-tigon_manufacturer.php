<?php
/**
 * Template: Manufacturer Archive
 *
 * Displays all manufacturers in a grid layout.
 * This template can be overridden by copying it to your theme:
 * yourtheme/archive-tigon_manufacturer.php
 *
 * @package Tigon_Taxonomy
 */

get_header();
?>

<div id="primary" class="content-area tigon-manufacturer-archive">
    <main id="main" class="site-main">

        <header class="page-header">
            <h1 class="page-title">Our Manufacturers</h1>
            <p class="archive-description">
                Browse our complete lineup of golf cart and electric vehicle manufacturers.
                Click on a manufacturer to view their models and available inventory.
            </p>
        </header>

        <?php if ( have_posts() ) : ?>

            <div class="tigon-manufacturer-grid">

                <?php while ( have_posts() ) : the_post(); ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'tigon-manufacturer-card' ); ?>>

                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="tigon-card-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                </a>
                            </div>
                        <?php else : ?>
                            <?php
                            $brand_logo = get_post_meta( get_the_ID(), '_tigon_brand_logo', true );
                            if ( $brand_logo ) :
                            ?>
                                <div class="tigon-card-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <img src="<?php echo esc_url( $brand_logo ); ?>"
                                             alt="<?php the_title_attribute(); ?>" />
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="tigon-card-content">
                            <h2 class="tigon-card-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <?php
                            $tagline = get_post_meta( get_the_ID(), '_tigon_brand_tagline', true );
                            if ( $tagline ) :
                            ?>
                                <p class="tigon-card-tagline"><?php echo esc_html( $tagline ); ?></p>
                            <?php endif; ?>

                            <?php if ( has_excerpt() ) : ?>
                                <p class="tigon-card-excerpt"><?php the_excerpt(); ?></p>
                            <?php endif; ?>

                            <?php
                            // Show model count if linked to a WC category.
                            $wc_cat_id = get_post_meta( get_the_ID(), '_tigon_wc_category_id', true );
                            if ( $wc_cat_id ) :
                                $models = get_terms( array(
                                    'taxonomy'   => 'product_cat',
                                    'parent'     => (int) $wc_cat_id,
                                    'hide_empty' => false,
                                ) );
                                if ( ! is_wp_error( $models ) ) :
                            ?>
                                <p class="tigon-card-models">
                                    <?php
                                    printf(
                                        '%d %s available',
                                        count( $models ),
                                        _n( 'model', 'models', count( $models ), 'tigon-taxonomy' )
                                    );
                                    ?>
                                </p>
                            <?php
                                endif;
                            endif;
                            ?>

                            <a href="<?php the_permalink(); ?>" class="tigon-card-link">
                                View Models &rarr;
                            </a>
                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

            <?php the_posts_pagination(); ?>

        <?php else : ?>

            <p>No manufacturers found.</p>

        <?php endif; ?>

    </main>
</div>

<?php
get_sidebar();
get_footer();
