<?php
/**
 * Template: Single Manufacturer
 *
 * Displays a single manufacturer profile with brand info and linked products.
 * This template can be overridden by copying it to your theme:
 * yourtheme/single-tigon_manufacturer.php
 *
 * @package Tigon_Taxonomy
 */

get_header();
?>

<div id="primary" class="content-area tigon-manufacturer-single">
    <main id="main" class="site-main">

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                <header class="entry-header tigon-manufacturer-header">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="tigon-manufacturer-logo">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </div>
                    <?php endif; ?>

                    <h1 class="entry-title"><?php the_title(); ?></h1>

                    <?php
                    $tagline = get_post_meta( get_the_ID(), '_tigon_brand_tagline', true );
                    if ( $tagline ) :
                    ?>
                        <p class="tigon-manufacturer-tagline"><?php echo esc_html( $tagline ); ?></p>
                    <?php endif; ?>
                </header>

                <div class="entry-content tigon-manufacturer-content">
                    <?php the_content(); ?>

                    <?php
                    $brand_url = get_post_meta( get_the_ID(), '_tigon_brand_url', true );
                    if ( $brand_url ) :
                    ?>
                        <p class="tigon-manufacturer-website">
                            <strong>Official Website:</strong>
                            <a href="<?php echo esc_url( $brand_url ); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html( $brand_url ); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>

                <?php
                // Display linked products if a WC category is linked.
                $wc_cat_id = get_post_meta( get_the_ID(), '_tigon_wc_category_id', true );
                if ( $wc_cat_id ) :
                    $cat_term = get_term( (int) $wc_cat_id, 'product_cat' );
                    if ( $cat_term && ! is_wp_error( $cat_term ) ) :
                ?>
                    <section class="tigon-manufacturer-products">
                        <h2>Models by <?php the_title(); ?></h2>

                        <?php
                        // Get child model categories.
                        $models = get_terms( array(
                            'taxonomy'   => 'product_cat',
                            'parent'     => $cat_term->term_id,
                            'hide_empty' => false,
                            'orderby'    => 'name',
                            'order'      => 'ASC',
                        ) );

                        if ( ! is_wp_error( $models ) && ! empty( $models ) ) :
                        ?>
                            <div class="tigon-model-grid">
                                <?php foreach ( $models as $model ) : ?>
                                    <div class="tigon-model-card">
                                        <h3>
                                            <a href="<?php echo esc_url( get_term_link( $model ) ); ?>">
                                                <?php echo esc_html( $model->name ); ?>
                                            </a>
                                        </h3>
                                        <?php if ( $model->description ) : ?>
                                            <p><?php echo esc_html( $model->description ); ?></p>
                                        <?php endif; ?>
                                        <span class="tigon-product-count">
                                            <?php
                                            printf(
                                                '%d %s',
                                                (int) $model->count,
                                                _n( 'product', 'products', $model->count, 'tigon-taxonomy' )
                                            );
                                            ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <p class="tigon-view-all">
                            <a href="<?php echo esc_url( get_term_link( $cat_term ) ); ?>" class="button">
                                View All <?php the_title(); ?> Products
                            </a>
                        </p>
                    </section>
                <?php
                    endif;
                endif;
                ?>

            </article>

        <?php endwhile; ?>

    </main>
</div>

<?php
get_sidebar();
get_footer();
