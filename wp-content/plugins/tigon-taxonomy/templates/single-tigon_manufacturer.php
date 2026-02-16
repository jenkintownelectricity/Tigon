<?php
/**
 * Single Manufacturer Template.
 *
 * Displays a single manufacturer profile with brand info,
 * logo, description, and linked WooCommerce products.
 *
 * @package Tigon_Taxonomy
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class( 'tigon-manufacturer-single' ); ?>>

                <header class="entry-header tigon-manufacturer-header">
                    <?php
                    $brand_logo = get_post_meta( get_the_ID(), '_tigon_brand_logo', true );
                    if ( $brand_logo ) {
                        echo '<div class="tigon-brand-logo">';
                        echo wp_get_attachment_image( $brand_logo, 'medium' );
                        echo '</div>';
                    } elseif ( has_post_thumbnail() ) {
                        echo '<div class="tigon-brand-logo">';
                        the_post_thumbnail( 'medium' );
                        echo '</div>';
                    }
                    ?>
                    <h1 class="entry-title"><?php the_title(); ?></h1>

                    <?php
                    $tagline = get_post_meta( get_the_ID(), '_tigon_brand_tagline', true );
                    if ( $tagline ) {
                        echo '<p class="tigon-brand-tagline">' . esc_html( $tagline ) . '</p>';
                    }

                    $brand_url = get_post_meta( get_the_ID(), '_tigon_brand_url', true );
                    if ( $brand_url ) {
                        echo '<p class="tigon-brand-website"><a href="' . esc_url( $brand_url ) . '" target="_blank" rel="noopener noreferrer">Visit Official Website</a></p>';
                    }
                    ?>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>

                <?php
                // Display linked WooCommerce products.
                $wc_cat_id = get_post_meta( get_the_ID(), '_tigon_wc_category_id', true );
                if ( $wc_cat_id && class_exists( 'WooCommerce' ) ) :
                    $term = get_term( (int) $wc_cat_id, 'product_cat' );
                    if ( $term && ! is_wp_error( $term ) ) :
                ?>
                    <section class="tigon-manufacturer-products">
                        <h2>Models from <?php the_title(); ?></h2>

                        <?php
                        // List child model categories.
                        $models = get_terms( array(
                            'taxonomy'   => 'product_cat',
                            'parent'     => $term->term_id,
                            'hide_empty' => false,
                            'orderby'    => 'name',
                        ) );

                        if ( ! empty( $models ) && ! is_wp_error( $models ) ) :
                        ?>
                            <div class="tigon-model-grid">
                                <?php foreach ( $models as $model ) : ?>
                                    <div class="tigon-model-card">
                                        <h3><a href="<?php echo esc_url( get_term_link( $model ) ); ?>"><?php echo esc_html( $model->name ); ?></a></h3>
                                        <?php if ( $model->description ) : ?>
                                            <p><?php echo esc_html( $model->description ); ?></p>
                                        <?php endif; ?>
                                        <p class="tigon-product-count"><?php echo (int) $model->count; ?> product(s) available</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        // Recent products from this manufacturer.
                        $products = new WP_Query( array(
                            'post_type'      => 'product',
                            'posts_per_page' => 8,
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'product_cat',
                                    'field'    => 'term_id',
                                    'terms'    => $term->term_id,
                                    'include_children' => true,
                                ),
                            ),
                        ) );

                        if ( $products->have_posts() ) :
                        ?>
                            <h3>Featured Inventory</h3>
                            <ul class="products columns-4">
                                <?php
                                while ( $products->have_posts() ) :
                                    $products->the_post();
                                    wc_get_template_part( 'content', 'product' );
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                <?php
                    endif;
                endif;
                ?>

            </article>

        <?php endwhile; ?>

    </main>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
